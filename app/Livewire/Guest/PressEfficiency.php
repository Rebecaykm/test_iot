<?php

namespace App\Livewire\Guest;

use App\Exports\PressEfficiencyExport;
use App\Models\PartNumber;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reporte de eficiencia de las prensas del área de Estampado por troquel (MDI), por semana, mes o año.
 *
 *  - SPM estándar: production_rate (golpes/hora) / 60, promedio de los parts del troquel.
 *  - SPM real: promedio de histories.production_per_cycle * 100.
 *  - Tiempo trabajado: suma de las corridas del troquel en su prensa. Una corrida es un
 *    bloque continuo de registros del mismo troquel; se corta cuando otro troquel produce
 *    en medio o cuando cambia el turno. Los huecos sin registros dentro de la corrida sí cuentan.
 *  - Un día de producción va del inicio del primer turno (08:00) a la misma hora del día siguiente.
 *  - Piezas esperadas: SPM estándar * minutos trabajados * piezas por golpe del troquel
 *    (troquel doble LH/RH = 2 piezas por golpe).
 *  - Eficiencia: piezas producidas / piezas esperadas.
 *
 * Carga diferida: la página abre al instante con un esqueleto y el reporte llega después.
 */
#[Lazy]
class PressEfficiency extends Component
{
    /** Área cuyas estaciones se pueden seleccionar */
    private const AREA_NAME = 'Estampado';

    /** Caché en archivo (ver LineProgress): compartido entre todas las pantallas */
    private const CACHE_STORE = 'file';

    /** Semana o mes en curso: pesados (un mes ~8 s), se recalculan cada 5 minutos */
    private const LIVE_TTL = 300;

    /** Semanas y meses cerrados: sus datos ya no cambian */
    private const PAST_TTL = 3600;

    /** Meses cerrados dentro de un año: un día (iot:warm-press-efficiency los vuelve a llenar) */
    private const CLOSED_MONTH_TTL = 86400;

    /** Periodos del filtro */
    public const PERIODS = ['week' => 'Semana', 'month' => 'Mes', 'year' => 'Año'];

    /** Umbrales de eficiencia (%) para el color: >= GOOD (meta, 90 %) verde, >= WARN ámbar, debajo rojo */
    public const GOOD_THRESHOLD = 90;
    public const WARN_THRESHOLD = 80;

    /** Cantidad de troqueles en cada gráfica */
    private const TOP = 10;

    /** Renglones de la tabla por página */
    private const PER_PAGE = 5;

    /** Periodo: 'week' o 'month'. No va en la URL: al abrir, siempre la semana en curso. */
    public string $period = 'week';

    /** Primer y último día de producción del periodo (Y-m-d) */
    public string $from = '';
    public string $to = '';

    /**
     * Estaciones seleccionadas (vacío = todas las del área). Se guardan como texto
     * porque así las entrega wire:model de los checkboxes.
     */
    #[Url(as: 'estaciones', except: [])]
    public array $selectedWorkCenters = [];

    /** Página actual de la tabla (se conserva en cada refresco) */
    public int $page = 1;

    /**
     * Estaciones del área de Estampado para el multi select, ordenadas por línea y número.
     */
    public static function stations()
    {
        return Cache::store(self::CACHE_STORE)->remember('press_efficiency:stations', 600, function () {
            return WorkCenter::query()
                ->select('work_centers.id', 'work_centers.number', 'work_centers.name', 'lines.name as line_name')
                ->join('lines', 'lines.id', '=', 'work_centers.line_id')
                ->join('areas', 'areas.id', '=', 'lines.area_id')
                ->where('areas.name', self::AREA_NAME)
                ->orderBy('lines.name')
                ->orderBy('work_centers.number')
                ->get()
                ->map(fn ($wc) => (object) [
                    'id' => (string) $wc->id,
                    'number' => $wc->number,
                    'name' => trim(preg_replace('/\s+/', ' ', $wc->name)),
                    'line' => $wc->line_name,
                ]);
        });
    }

    public function mount(): void
    {
        $this->currentPeriod();
        $this->normalizeWorkCenters();
    }

    /** Esqueleto mientras llega el reporte (carga diferida) */
    public function placeholder()
    {
        return view('livewire.guest.press-efficiency-placeholder');
    }

    /**
     * Deja en caché la semana, el mes y el año en curso de todas las estaciones (lo que se abre
     * por defecto). Lo corre iot:warm-press-efficiency cada hora. Regresa los segundos de cada uno.
     */
    public static function warm(): array
    {
        $times = [];

        foreach (array_keys(self::PERIODS) as $period) {
            $component = new self();
            $component->period = $period;
            $component->currentPeriod();

            $t = microtime(true);
            $component->currentReport($component->range());
            $times[$period] = round(microtime(true) - $t, 1);
        }

        return $times;
    }

    public function updatedSelectedWorkCenters(): void
    {
        $this->normalizeWorkCenters();
        $this->page = 1;
    }

    public function clearWorkCenters(): void
    {
        $this->selectedWorkCenters = [];
        $this->page = 1;
    }

    /** Regresa a la vista por defecto: todas las estaciones y la semana en curso */
    public function clearFilters(): void
    {
        $this->selectedWorkCenters = [];
        $this->period = 'week';
        $this->currentPeriod();
    }

    /** Solo ids válidos del área y sin duplicados */
    private function normalizeWorkCenters(): void
    {
        $valid = self::stations()->pluck('id')->all();

        $this->selectedWorkCenters = array_values(array_intersect(
            $valid,
            array_map('strval', $this->selectedWorkCenters)
        ));
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /** Refresco periódico: solo vuelve a pintar si el periodo está en curso */
    public function tick(): void
    {
        if (!$this->range()?->isLive) {
            $this->skipRender();
        }
    }

    /** Día de producción en curso (el turno nocturno después de medianoche cuenta al día anterior) */
    private function productionToday(): Carbon
    {
        return Shift::getCurrentShiftInfo(now())->date->copy()->startOfDay();
    }

    /** Cambio de periodo: cada uno abre en el actual (la semana o el mes en curso) */
    public function setPeriod(string $period): void
    {
        if (array_key_exists($period, self::PERIODS)) {
            $this->period = $period;
            $this->currentPeriod();
        }
    }

    /** Botón "Semana actual" / "Mes actual" */
    public function currentPeriod(): void
    {
        $today = $this->productionToday();

        match ($this->period) {
            'year' => $this->setDays($today->copy()->startOfYear(), $today->copy()->endOfYear()),
            'month' => $this->setDays($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            default => $this->setDays($today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfWeek(Carbon::SUNDAY)),
        };
    }

    /** Calendario del año */
    public function setYear(int $year): void
    {
        if ($year >= 2000 && $year <= (int) now()->year) {
            $this->period = 'year';
            $d = Carbon::create($year);
            $this->setDays($d->copy()->startOfYear(), $d->copy()->endOfYear());
        }
    }

    /** Calendario de la semana: cualquier día lleva a su semana (lunes a domingo) */
    public function setWeek(string $day): void
    {
        if ($this->isValidDate($day)) {
            $this->period = 'week';
            $d = Carbon::parse($day);
            $this->setDays($d->copy()->startOfWeek(Carbon::MONDAY), $d->copy()->endOfWeek(Carbon::SUNDAY));
        }
    }

    /** Calendario del mes: 'Y-m' */
    public function setMonth(string $month): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->period = 'month';
            $d = Carbon::parse($month . '-01');
            $this->setDays($d->copy()->startOfMonth(), $d->copy()->endOfMonth());
        }
    }

    private function setDays(Carbon $from, Carbon $to): void
    {
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
        $this->page = 1;
    }

    /** Flechas: semana o mes anterior/siguiente */
    public function prevPeriod(): void
    {
        $this->movePeriod(-1);
    }

    public function nextPeriod(): void
    {
        $this->movePeriod(1);
    }

    private function movePeriod(int $step): void
    {
        if (!$this->isValidDate($this->from)) {
            $this->currentPeriod();

            return;
        }

        $from = Carbon::parse($this->from);

        match ($this->period) {
            'year' => $this->setDays($from->copy()->addYears($step)->startOfYear(), $from->copy()->addYears($step)->endOfYear()),
            'month' => $this->setDays($from->copy()->addMonthsNoOverflow($step)->startOfMonth(), $from->copy()->addMonthsNoOverflow($step)->endOfMonth()),
            default => $this->setDays($from->copy()->addWeeks($step), $from->copy()->addWeeks($step)->addDays(6)),
        };
    }

    private function isValidDate(?string $date): bool
    {
        return (bool) $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false;
    }

    private static function shifts()
    {
        return Cache::store(self::CACHE_STORE)->remember('press_efficiency:shifts', 600, fn () => Shift::getAllShifts());
    }

    /**
     * Ventana del periodo: del inicio del primer turno del primer día al inicio del primer
     * turno del día siguiente al último (incluye el nocturno del último día).
     */
    private function range(): ?object
    {
        if (!$this->isValidDate($this->from) || !$this->isValidDate($this->to)) {
            return null;
        }

        $dayStart = substr(self::shifts()->sortBy('start_time')->first()?->start_time ?? '00:00:00', 0, 8);
        $start = Carbon::parse($this->from . ' ' . $dayStart);
        $end = Carbon::parse($this->to . ' ' . $dayStart)->addDay();
        $now = now();

        return (object) [
            'start' => $start,
            'end' => $end,
            'isLive' => $now->between($start, $end),
            'isFuture' => $now->lt($start),
        ];
    }

    /**
     * Reporte del periodo, cacheado y compartido entre pantallas.
     */
    public static function report(Carbon $start, Carbon $end, bool $isLive, array $workCenterIds): array
    {
        sort($workCenterIds);

        $key = 'press_efficiency:v6:report:' . $start->format('YmdHi') . '-' . $end->format('YmdHi')
            . ':' . md5(implode(',', $workCenterIds));

        return Cache::store(self::CACHE_STORE)->remember(
            $key,
            $isLive ? self::LIVE_TTL : self::PAST_TTL,
            fn () => self::buildReport(self::rawData($start, $end), $workCenterIds)
        );
    }

    /**
     * Agregados crudos de TODAS las estaciones del área: corridas por "prensa|troquel" y totales
     * por part. Se pueden sumar: las corridas se cortan en cada cambio de turno y los periodos
     * empiezan al inicio del primer turno, así que un año es la suma exacta de sus meses.
     * Elegir estaciones solo filtra estos agregados (no vuelve a leer histories).
     * Más de un mes (el año) se arma por meses cacheados: los cerrados duran un día y solo el
     * mes en curso se recalcula cada 5 minutos.
     */
    private static function rawData(Carbon $start, Carbon $end): array
    {
        $chunks = [];
        if ($start->diffInDays($end) > 31) {
            for ($from = $start->copy(); $from->lt($end); $from = $to) {
                $to = $from->copy()->startOfMonth()->addMonthNoOverflow()->setTimeFrom($start);
                $chunks[] = [$from, $to->lt($end) ? $to : $end->copy()];
            }
            // Un año en frío son ~10 meses de ~10 s cada uno; el comando programado lo deja caliente.
            set_time_limit(300);
        } else {
            $chunks[] = [$start, $end];
        }

        $now = now();
        $raw = ['dies' => [], 'parts' => []];

        foreach ($chunks as [$from, $to]) {
            if ($from->gte($now)) {
                break;
            }

            $isLive = $to->gt($now);
            $chunk = Cache::store(self::CACHE_STORE)->remember(
                'press_efficiency:v6:raw:' . $from->format('YmdHi') . '-' . $to->format('YmdHi'),
                $isLive ? self::LIVE_TTL : self::CLOSED_MONTH_TTL,
                fn () => self::collectRaw($from, $isLive ? $now : $to)
            );

            foreach ($chunk['dies'] as $key => $die) {
                $acc = $raw['dies'][$key] ?? ['seconds' => 0, 'runs' => 0, 'start' => $die['start']];
                $raw['dies'][$key] = [
                    'seconds' => $acc['seconds'] + $die['seconds'],
                    'runs' => $acc['runs'] + $die['runs'],
                    'start' => min($acc['start'], $die['start']),
                ];
            }
            foreach ($chunk['parts'] as $partId => $t) {
                foreach (['qty', 'ppc_sum', 'ppc_count'] as $field) {
                    $raw['parts'][$partId][$field] = ($raw['parts'][$partId][$field] ?? 0) + $t[$field];
                }
            }
        }

        return $raw;
    }

    /**
     * Parts de las estaciones y su troquel (atributo 'mid'; sin MDI el troquel es el propio número de parte).
     */
    private static function partCatalog(array $workCenterIds): array
    {
        $partInfo = PartNumber::query()
            ->select('id', 'number', 'name', 'production_rate', 'work_center_id')
            ->whereIn('work_center_id', $workCenterIds)
            ->get()
            ->keyBy('id');

        $mids = DB::table('attributes')
            ->selectRaw('attributable_id, MAX(LTRIM(RTRIM(value))) AS mid')
            ->where('attributable_type', PartNumber::class)
            ->where('key', 'mid')
            ->where('value', '<>', '')
            ->whereIn('attributable_id', PartNumber::query()->select('id')->whereIn('work_center_id', $workCenterIds))
            ->groupBy('attributable_id')
            ->pluck('mid', 'attributable_id');

        $dieOf = $partInfo->map(fn ($part) => trim((string) ($mids[$part->id] ?? '')) ?: $part->number)->all();

        return [$partInfo, $dieOf];
    }

    /** Lee histories de un tramo para todas las estaciones del área (ver rawData) */
    private static function collectRaw(Carbon $start, Carbon $end): array
    {
        $allIds = array_map('intval', self::stations()->pluck('id')->all());
        [, $dieOf] = self::partCatalog($allIds);

        // Piezas agrupadas por estación, part y minuto: con esto la consulta pesa ~20 veces menos
        // que recorrer cada registro de histories (un mes: ~3.5 millones de renglones → ~180 mil).
        // Los segundos (desde 2000-01-01) evitan convertir fechas en PHP renglón por renglón.
        $epoch = "'2000-01-01'";
        $minute = DB::raw("DATEDIFF(minute, {$epoch}, h.created_at)");
        $minuteRows = DB::table('histories as h')
            ->join('part_numbers as pn', 'pn.id', '=', 'h.part_number_id')
            ->whereIn('pn.work_center_id', $allIds)
            // Fin exclusivo: un registro justo en el borde entre dos meses no se cuenta dos veces
            ->where('h.created_at', '>=', $start->format('Y-m-d H:i:s'))
            ->where('h.created_at', '<', $end->format('Y-m-d H:i:s'))
            ->selectRaw(
                "pn.work_center_id AS wc_id, h.part_number_id AS part_id, DATEDIFF(minute, {$epoch}, h.created_at) AS m,
                 DATEDIFF(second, {$epoch}, MIN(h.created_at)) AS first_s, DATEDIFF(second, {$epoch}, MAX(h.created_at)) AS last_s,
                 SUM(h.quantity) AS qty, SUM(h.production_per_cycle) AS ppc_sum, COUNT(h.production_per_cycle) AS ppc_count"
            )
            ->groupBy('pn.work_center_id', 'h.part_number_id', $minute)
            ->orderBy('wc_id')
            ->orderBy('m')
            ->orderBy('first_s');

        // Corridas: bloque continuo del mismo troquel en su prensa. Se corta cuando otro troquel
        // produce en medio o cuando cambia el turno (en rangos de varios días, sin este corte, un
        // troquel que trabajó el lunes y el miércoles sumaría como trabajados los días de en medio).
        // Los huecos sin registros dentro de la corrida sí cuentan, como antes.
        $shiftOf = self::shiftBucketResolver();
        $dies = [];   // "wc|troquel" => seconds, runs, start (primer segundo)
        $totals = []; // part id => qty, ppc_sum, ppc_count
        $current = null;

        $closeRun = function () use (&$current, &$dies) {
            $die = $dies[$current['key']] ?? ['seconds' => 0, 'runs' => 0, 'start' => $current['start']];
            $dies[$current['key']] = [
                'seconds' => $die['seconds'] + $current['end'] - $current['start'],
                'runs' => $die['runs'] + 1,
                'start' => min($die['start'], $current['start']),
            ];
        };

        foreach ($minuteRows->cursor() as $row) {
            $partId = (int) $row->part_id;
            $key = $row->wc_id . '|' . ($dieOf[$partId] ?? $partId);
            $bucket = $shiftOf((int) $row->m);

            if (!$current || $current['key'] !== $key || $current['bucket'] !== $bucket) {
                if ($current) {
                    $closeRun();
                }
                $current = ['key' => $key, 'bucket' => $bucket, 'start' => (int) $row->first_s, 'end' => (int) $row->last_s];
            } else {
                $current['end'] = max($current['end'], (int) $row->last_s);
            }

            $totals[$partId]['qty'] = ($totals[$partId]['qty'] ?? 0) + (int) $row->qty;
            $totals[$partId]['ppc_sum'] = ($totals[$partId]['ppc_sum'] ?? 0) + (float) $row->ppc_sum;
            $totals[$partId]['ppc_count'] = ($totals[$partId]['ppc_count'] ?? 0) + (int) $row->ppc_count;
        }
        if ($current) {
            $closeRun();
        }

        return ['dies' => $dies, 'parts' => $totals];
    }

    /** Reporte final de las estaciones elegidas a partir de los agregados crudos (ver rawData) */
    private static function buildReport(array $raw, array $workCenterIds): array
    {
        $workCenters = WorkCenter::query()
            ->select('id', 'number', 'name')
            ->whereIn('id', $workCenterIds)
            ->orderBy('number')
            ->get()
            ->keyBy('id');

        $empty = ['rows' => [], 'deviation' => [], 'efficiency' => [], 'volume' => [], 'coverage' => null, 'summary' => null, 'generatedAt' => now()->toIso8601String()];

        if ($workCenters->isEmpty()) {
            return $empty;
        }

        [$partInfo, $dieOf] = self::partCatalog($workCenters->keys()->all());
        $runs = $raw['dies'];

        // Totales por part de las estaciones elegidas (los de otras estaciones no están en $partInfo)
        $parts = collect($raw['parts'])->filter(fn ($t, $partId) => isset($partInfo[$partId]))->map(function ($t, $partId) use ($partInfo, $dieOf) {
            $part = $partInfo[$partId];

            return (object) [
                'wc_id' => $part->work_center_id,
                'die' => $dieOf[$partId],
                'id' => $partId,
                'number' => $part->number,
                'name' => $part->name,
                'production_rate' => $part->production_rate,
                'qty' => $t['qty'],
                'ppc_sum' => $t['ppc_sum'],
                'ppc_count' => $t['ppc_count'],
            ];
        })->values();

        if ($parts->isEmpty()) {
            return $empty;
        }

        $divisors = PartNumber::buildShotDivisorsForPartIds($parts->pluck('id')->all());

        $epochTs = Carbon::parse('2000-01-01')->timestamp;

        $rows = $parts
            ->groupBy(fn ($part) => $part->wc_id . '|' . $part->die)
            ->map(function ($dieParts, $key) use ($runs, $divisors, $workCenters, $epochTs) {
                $first = $dieParts->first();
                $die = $runs[$key] ?? null;

                $seconds = $die['seconds'] ?? 0;
                $minutes = $seconds / 60;

                $rates = $dieParts->map(fn ($part) => (float) $part->production_rate)->filter(fn ($rate) => $rate > 0);
                $spmStandard = $rates->isNotEmpty() ? $rates->avg() / 60 : null;

                $ppcCount = $dieParts->sum('ppc_count');
                $spmReal = $ppcCount > 0 ? $dieParts->sum('ppc_sum') / $ppcCount * 100 : null;

                $produced = (int) $dieParts->sum('qty');
                $piecesPerShot = max(1, (int) ($divisors[$first->id] ?? 1));
                $expected = $spmStandard !== null ? $spmStandard * $minutes * $piecesPerShot : null;
                $efficiency = $expected > 0 ? $produced / $expected * 100 : null;

                $workCenter = $workCenters->get($first->wc_id);

                return [
                    'press' => trim(preg_replace('/\s+/', ' ', $workCenter->name)),
                    'pressNumber' => $workCenter->number,
                    'die' => $first->die,
                    'parts' => $dieParts->pluck('number')->unique()->sort()->values()->all(),
                    'names' => $dieParts->pluck('name')->filter()->unique()->values()->all(),
                    'spmStandard' => $spmStandard !== null ? round($spmStandard, 1) : null,
                    'spmReal' => $spmReal !== null ? round($spmReal, 1) : null,
                    'difference' => $spmStandard !== null && $spmReal !== null ? round($spmReal - $spmStandard, 1) : null,
                    'minutes' => round($minutes, 1),
                    // Segundos exactos: la tabla los muestra para que las piezas esperadas cuadren a mano
                    'seconds' => (int) $seconds,
                    'runs' => $die['runs'] ?? 0,
                    'piecesPerShot' => $piecesPerShot,
                    'expected' => $expected !== null ? (int) round($expected) : null,
                    'produced' => $produced,
                    'efficiency' => $efficiency !== null ? round($efficiency, 1) : null,
                    // Con la zona de la app: sin ella Carbon regresa UTC y la hora salía 6 h adelantada
                    'startedAt' => $die ? Carbon::createFromTimestamp($epochTs + $die['start'], config('app.timezone'))->format('Y-m-d H:i:s') : null,
                ];
            })
            ->sortBy([['pressNumber', 'asc'], ['startedAt', 'asc']])
            ->values();

        $withStandard = $rows->whereNotNull('efficiency');
        $expectedTotal = $withStandard->sum('expected');

        return [
            'rows' => $rows->all(),
            'deviation' => $rows->filter(fn ($row) => $row['difference'] !== null && $row['difference'] < 0)
                ->sortBy('difference')->take(self::TOP)->values()
                ->map(fn ($row) => ['label' => $row['die'], 'press' => $row['press'], 'value' => $row['difference']])->all(),
            'efficiency' => $withStandard->sortBy('efficiency')->take(self::TOP)->values()
                ->map(fn ($row) => ['label' => $row['die'], 'press' => $row['press'], 'value' => $row['efficiency']])->all(),
            // Volumen y eficiencia de TODOS los troqueles (MDI) del periodo, sumando sus prensas; los de más
            // piezas primero. Sin SPM estándar no hay eficiencia (value = null): solo se muestra su volumen.
            // Piezas no producidas = esperadas − producidas: junta volumen e ineficiencia (a qué atender primero).
            'volume' => $rows->groupBy('die')
                ->map(function ($dieRows, $die) {
                    $std = $dieRows->whereNotNull('efficiency');
                    $expected = $std->sum('expected');

                    return [
                        'label' => $die,
                        'press' => $dieRows->pluck('press')->unique()->implode(', '),
                        'produced' => $dieRows->sum('produced'),
                        // Piezas que entran en la eficiencia: solo de las prensas donde el troquel tiene estándar
                        'producedStd' => $std->sum('produced'),
                        'expected' => $expected > 0 ? $expected : null,
                        'lost' => $expected > 0 ? max(0, $expected - $std->sum('produced')) : null,
                        'value' => $expected > 0 ? round($std->sum('produced') / $expected * 100, 1) : null,
                    ];
                })
                ->sortByDesc('produced')->values()->all(),
            // Cuántos troqueles (y qué parte de las piezas) tienen SPM estándar: solo esos tienen eficiencia
            'coverage' => [
                'dies' => $withStandard->pluck('die')->unique()->count(),
                'allDies' => $rows->pluck('die')->unique()->count(),
                'piecesPct' => $rows->sum('produced') > 0 ? round($withStandard->sum('produced') / $rows->sum('produced') * 100) : 0,
            ],
            'summary' => [
                'efficiency' => $expectedTotal > 0 ? round($withStandard->sum('produced') / $expectedTotal * 100, 1) : null,
                'produced' => $rows->sum('produced'),
                'expected' => $expectedTotal,
                'dies' => $rows->count(),
                'withoutStandard' => $rows->whereNull('spmStandard')->count(),
                'belowStandard' => $rows->filter(fn ($row) => $row['difference'] !== null && $row['difference'] < 0)->count(),
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Función que da el turno (como número consecutivo) de un minuto contado desde 2000-01-01:
     * día * cantidad de turnos + índice del turno según su hora de inicio. Un minuto antes del
     * primer turno del día pertenece al último turno del día anterior (el nocturno).
     */
    private static function shiftBucketResolver(): \Closure
    {
        $starts = self::shifts()
            ->map(fn ($shift) => (int) substr($shift->start_time, 0, 2) * 60 + (int) substr($shift->start_time, 3, 2))
            ->sort()
            ->values()
            ->all() ?: [0];
        $count = count($starts);

        return function (int $minute) use ($starts, $count): int {
            $day = intdiv($minute, 1440);
            $ofDay = $minute % 1440;

            $index = -1;
            foreach ($starts as $i => $startMinute) {
                if ($ofDay >= $startMinute) {
                    $index = $i;
                }
            }

            return $index < 0 ? ($day - 1) * $count + $count - 1 : $day * $count + $index;
        };
    }

    public static function efficiencyTone(?float $efficiency): string
    {
        if ($efficiency === null) {
            return 'none';
        }

        return match (true) {
            $efficiency >= self::GOOD_THRESHOLD => 'good',
            $efficiency >= self::WARN_THRESHOLD => 'warn',
            default => 'bad',
        };
    }

    /** Reporte del periodo y estaciones seleccionados (vacío si el periodo aún no inicia) */
    private function currentReport(?object $range): array
    {
        $workCenterIds = array_map('intval', $this->selectedWorkCenters ?: self::stations()->pluck('id')->all());

        return $range && !$range->isFuture
            ? self::report($range->start, $range->end, $range->isLive, $workCenterIds)
            : ['rows' => [], 'deviation' => [], 'efficiency' => [], 'volume' => [], 'coverage' => null, 'summary' => null, 'generatedAt' => now()->toIso8601String()];
    }

    /** Descarga en Excel la tabla completa (todas las páginas) y las gráficas del periodo */
    public function export()
    {
        $report = $this->currentReport($this->range());

        return Excel::download(new PressEfficiencyExport($report), 'PressEfficiency_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function render()
    {
        $range = $this->range();

        $stations = self::stations();
        $report = $this->currentReport($range);

        $charts = ['deviation' => $report['deviation'], 'efficiency' => $report['efficiency'], 'volume' => $report['volume'] ?? []];

        $this->dispatch('press-efficiency-charts', charts: $charts);

        // Si en un refresco hay menos troqueles, la página se ajusta a la última disponible.
        $total = count($report['rows']);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $this->page = min(max(1, $this->page), $pages);

        return view('livewire.guest.press-efficiency', [
            'range' => $range,
            'stations' => $stations,
            'report' => $report,
            'pageRows' => array_slice($report['rows'], ($this->page - 1) * self::PER_PAGE, self::PER_PAGE),
            'pagination' => (object) [
                'total' => $total,
                'pages' => $pages,
                'from' => $total ? ($this->page - 1) * self::PER_PAGE + 1 : 0,
                'to' => min($this->page * self::PER_PAGE, $total),
            ],
            'charts' => $charts,
            'canGoNext' => $range && $range->end->lt(now()),
            'periodLabel' => $this->periodLabel(),
        ]);
    }

    /** "19 – 25 Oct 2026", "28 Sep – 4 Oct 2026" u "Octubre 2026" */
    private function periodLabel(): string
    {
        if (!$this->isValidDate($this->from) || !$this->isValidDate($this->to)) {
            return '';
        }

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $mesesLargos = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $from = Carbon::parse($this->from);
        $to = Carbon::parse($this->to);

        if ($this->period === 'year') {
            return (string) $from->year;
        }

        if ($this->period === 'month') {
            return $mesesLargos[$from->month - 1] . ' ' . $from->year;
        }

        return $from->day . ($from->month !== $to->month || $from->year !== $to->year ? ' ' . $meses[$from->month - 1] : '')
            . ($from->year !== $to->year ? ' ' . $from->year : '')
            . ' – ' . $to->day . ' ' . $meses[$to->month - 1] . ' ' . $to->year;
    }
}
