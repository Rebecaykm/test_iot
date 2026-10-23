<?php

namespace App\Livewire\Guest;

use App\Exports\PressEfficiencyExport;
use App\Models\PartNumber;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reporte de eficiencia de las prensas del área de Estampado por troquel (MDI) y turno.
 *
 *  - SPM estándar: production_rate (golpes/hora) / 60, promedio de los parts del troquel.
 *  - SPM real: promedio de histories.production_per_cycle * 100.
 *  - Tiempo trabajado: suma de las corridas del troquel en su prensa. Una corrida es un
 *    bloque continuo de registros del mismo troquel; solo se corta cuando otro troquel
 *    produce en medio. Los huecos sin registros dentro de la corrida sí cuentan.
 *  - Piezas esperadas: SPM estándar * minutos trabajados * piezas por golpe del troquel
 *    (troquel doble LH/RH = 2 piezas por golpe).
 *  - Eficiencia: piezas producidas / piezas esperadas.
 */
class PressEfficiency extends Component
{
    /** Área cuyas estaciones se pueden seleccionar */
    private const AREA_NAME = 'Estampado';

    /** Caché en archivo (ver LineProgress): compartido entre todas las pantallas */
    private const CACHE_STORE = 'file';

    /** Turno en curso: se recalcula como máximo una vez por refresco */
    private const LIVE_TTL = 55;

    /** Turnos cerrados: sus datos ya no cambian */
    private const PAST_TTL = 3600;

    /** Umbrales de eficiencia (%) para el color: >= GOOD verde, >= WARN ámbar, debajo rojo */
    public const GOOD_THRESHOLD = 95;
    public const WARN_THRESHOLD = 80;

    /** Cantidad de troqueles en cada gráfica */
    private const TOP = 10;

    /** Renglones de la tabla por página */
    private const PER_PAGE = 5;

    /**
     * Fecha en que inicia el turno (Y-m-d) y turno. No van en la URL: si quedaran
     * fijos ahí, al recargar o abrir un marcador la vista se quedaría en un turno
     * ya cerrado, que no se refresca.
     */
    public string $date = '';

    public ?int $shiftId = null;

    /**
     * true mientras se ve el turno en curso: cuando ese turno termina, el refresco
     * pasa solo al turno nuevo. Se apaga al elegir un turno pasado.
     */
    public bool $followLive = true;

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
        $current = Shift::getCurrentShiftInfo(now());

        $this->shiftId = $current->shift?->id ?? $this->shifts()->first()?->id;
        $this->date = $current->date->toDateString();

        $this->normalizeWorkCenters();
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

    /** Regresa a la vista por defecto: todas las estaciones y el turno en curso */
    public function clearFilters(): void
    {
        $this->selectedWorkCenters = [];
        $this->currentShift();
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

    public function updatedDate(): void
    {
        if (!$this->isValidDate($this->date)) {
            $this->date = Shift::getCurrentShiftInfo(now())->date->toDateString();
        }

        $this->page = 1;
        $this->syncFollowLive();
    }

    public function updatedShiftId(): void
    {
        $this->page = 1;
        $this->syncFollowLive();
    }

    /** Tras un cambio manual de fecha/turno: se sigue en vivo solo si quedó en el turno en curso */
    private function syncFollowLive(): void
    {
        $this->followLive = (bool) $this->range()?->isLive;
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Refresco periódico: solo vuelve a pintar si se está viendo el turno en curso.
     * Si ese turno ya terminó y la pantalla lo venía siguiendo, pasa al turno nuevo.
     */
    public function tick(): void
    {
        if ($this->range()?->isLive) {
            return;
        }

        if ($this->followLive) {
            $this->currentShift();

            return;
        }

        $this->skipRender();
    }

    public function currentShift(): void
    {
        $current = Shift::getCurrentShiftInfo(now());
        $this->shiftId = $current->shift?->id ?? $this->shiftId;
        $this->date = $current->date->toDateString();
        $this->page = 1;
        $this->followLive = true;
    }

    public function prevShift(): void
    {
        $this->moveShift(-1);
    }

    public function nextShift(): void
    {
        $this->moveShift(1);
    }

    /**
     * Recorre los turnos en orden de inicio; al pasar del último al primero cambia el día.
     */
    private function moveShift(int $step): void
    {
        $shifts = $this->shifts()->values();
        $index = $shifts->search(fn ($shift) => $shift->id === $this->shiftId);
        $date = Carbon::parse($this->date);

        if ($index === false) {
            return;
        }

        $index += $step;
        if ($index >= $shifts->count()) {
            $index = 0;
            $date->addDay();
        } elseif ($index < 0) {
            $index = $shifts->count() - 1;
            $date->subDay();
        }

        $this->shiftId = $shifts[$index]->id;
        $this->date = $date->toDateString();
        $this->page = 1;
        $this->syncFollowLive();
    }

    private function isValidDate(?string $date): bool
    {
        return (bool) $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false;
    }

    private function shifts()
    {
        return Cache::store(self::CACHE_STORE)->remember('press_efficiency:shifts', 600, fn () => Shift::getAllShifts());
    }

    /**
     * Ventana del turno seleccionado. En el turno en curso el fin es la hora actual.
     */
    private function range(): ?object
    {
        $shift = $this->shifts()->firstWhere('id', $this->shiftId);
        $range = $shift ? Shift::getShiftDateTimeRange($shift, $this->date) : null;

        if (!$range) {
            return null;
        }

        $now = now();

        return (object) [
            'shift' => $shift,
            'start' => $range->startDateTime,
            'end' => $range->endDateTime,
            'isLive' => $now->between($range->startDateTime, $range->endDateTime),
            'isFuture' => $now->lt($range->startDateTime),
        ];
    }

    /**
     * Reporte del turno, cacheado y compartido entre pantallas.
     */
    public static function report(Carbon $start, Carbon $end, bool $isLive, array $workCenterIds): array
    {
        sort($workCenterIds);

        $key = 'press_efficiency:report:' . $start->format('YmdHi') . '-' . $end->format('YmdHi')
            . ':' . md5(implode(',', $workCenterIds));

        return Cache::store(self::CACHE_STORE)->remember(
            $key,
            $isLive ? self::LIVE_TTL : self::PAST_TTL,
            fn () => self::buildReport($start, $end->lt(now()) ? $end : now(), $workCenterIds)
        );
    }

    private static function buildReport(Carbon $start, Carbon $end, array $workCenterIds): array
    {
        $workCenters = WorkCenter::query()
            ->select('id', 'number', 'name')
            ->whereIn('id', $workCenterIds)
            ->orderBy('number')
            ->get()
            ->keyBy('id');

        $empty = ['rows' => [], 'deviation' => [], 'efficiency' => [], 'summary' => null, 'generatedAt' => now()->toIso8601String()];

        if ($workCenters->isEmpty()) {
            return $empty;
        }

        $window = [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];

        // Troquel (MDI) de cada part: atributo 'mid'; sin MDI el troquel es el propio número de parte.
        $mids = DB::table('attributes')
            ->select('attributable_id')
            ->selectRaw('MAX(LTRIM(RTRIM(value))) AS mid')
            ->where('attributable_type', PartNumber::class)
            ->where('key', 'mid')
            ->where('value', '<>', '')
            ->groupBy('attributable_id');

        $die = 'COALESCE(m.mid, pn.number)';

        $base = DB::table('histories as h')
            ->join('part_numbers as pn', 'pn.id', '=', 'h.part_number_id')
            ->leftJoinSub($mids, 'm', 'm.attributable_id', '=', 'pn.id')
            ->whereIn('pn.work_center_id', $workCenters->keys())
            ->whereBetween('h.created_at', $window);

        // Corridas: renglones consecutivos (por prensa) del mismo troquel. LAG marca dónde
        // cambia de troquel y la suma acumulada numera cada corrida.
        $changes = (clone $base)->selectRaw(
            "h.id, h.created_at, pn.work_center_id AS wc_id, {$die} AS die,
             CASE WHEN LAG({$die}) OVER (PARTITION BY pn.work_center_id ORDER BY h.created_at, h.id) = {$die} THEN 0 ELSE 1 END AS changed"
        );

        $numbered = DB::query()->fromSub($changes, 's')->selectRaw(
            's.wc_id, s.die, s.created_at,
             SUM(s.changed) OVER (PARTITION BY s.wc_id ORDER BY s.created_at, s.id ROWS UNBOUNDED PRECEDING) AS run'
        );

        $runs = DB::query()->fromSub($numbered, 'r')
            ->selectRaw('r.wc_id, r.die, r.run, MIN(r.created_at) AS started_at, MAX(r.created_at) AS ended_at')
            ->groupBy('r.wc_id', 'r.die', 'r.run')
            ->get();

        // Totales por part.
        $parts = (clone $base)
            ->selectRaw(
                "pn.work_center_id AS wc_id, {$die} AS die, pn.id, pn.number, pn.name, pn.production_rate,
                 SUM(h.quantity) AS qty, SUM(h.production_per_cycle) AS ppc_sum, COUNT(h.production_per_cycle) AS ppc_count"
            )
            ->groupBy('pn.work_center_id', DB::raw($die), 'pn.id', 'pn.number', 'pn.name', 'pn.production_rate')
            ->get();

        if ($parts->isEmpty()) {
            return $empty;
        }

        $divisors = PartNumber::buildShotDivisorsForPartIds($parts->pluck('id')->all());

        $runsByDie = $runs->groupBy(fn ($run) => $run->wc_id . '|' . $run->die);

        $rows = $parts
            ->groupBy(fn ($part) => $part->wc_id . '|' . $part->die)
            ->map(function ($dieParts, $key) use ($runsByDie, $divisors, $workCenters) {
                $first = $dieParts->first();
                $dieRuns = $runsByDie->get($key, collect());

                $seconds = $dieRuns->sum(fn ($run) => Carbon::parse($run->started_at)->diffInSeconds(Carbon::parse($run->ended_at)));
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
                    'runs' => $dieRuns->count(),
                    'piecesPerShot' => $piecesPerShot,
                    'expected' => $expected !== null ? (int) round($expected) : null,
                    'produced' => $produced,
                    'efficiency' => $efficiency !== null ? round($efficiency, 1) : null,
                    'startedAt' => $dieRuns->min('started_at'),
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

    /** Reporte del turno y estaciones seleccionados (vacío si el turno aún no inicia) */
    private function currentReport(?object $range): array
    {
        $workCenterIds = array_map('intval', $this->selectedWorkCenters ?: self::stations()->pluck('id')->all());

        return $range && !$range->isFuture
            ? self::report($range->start, $range->end, $range->isLive, $workCenterIds)
            : ['rows' => [], 'deviation' => [], 'efficiency' => [], 'summary' => null, 'generatedAt' => now()->toIso8601String()];
    }

    /** Descarga en Excel la tabla completa (todas las páginas) y las gráficas del turno */
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

        $charts = ['deviation' => $report['deviation'], 'efficiency' => $report['efficiency']];

        $this->dispatch('press-efficiency-charts', charts: $charts);

        // Si en un refresco hay menos troqueles, la página se ajusta a la última disponible.
        $total = count($report['rows']);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $this->page = min(max(1, $this->page), $pages);

        return view('livewire.guest.press-efficiency', [
            'range' => $range,
            'shifts' => $this->shifts(),
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
        ]);
    }
}
