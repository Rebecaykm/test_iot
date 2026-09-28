<?php

namespace App\Livewire\Guest;

use App\Models\Line;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;

class LineProgress extends Component
{
    /**
     * Caché en archivo: para este volumen (toda la planta, ~1,100 grupos por semana)
     * leer del store "database" tardaba casi lo mismo que la consulta; en archivo ~20 ms.
     */
    private const CACHE_STORE = 'file';

    /**
     * Semana actual y futuras: 10 s. Caché simple a propósito: con Cache::flexible
     * (entregar el dato viejo y renovarlo después de responder) cada refresco de 30 s
     * recibía el valor del refresco anterior y la vista iba siempre una vuelta atrás.
     */
    private const LIVE_TTL = 10;

    /** Semanas pasadas: sus datos casi no cambian */
    private const PAST_TTL = 3600;

    /** Catálogos (líneas, estaciones, turnos) */
    private const CATALOG_TTL = 600;

    /** Lunes de la semana mostrada (Y-m-d) */
    public string $weekStart = '';

    /** Líneas seleccionadas (vacío = todas). Va en la URL (?lines=) y se guarda en el navegador. */
    #[Url(as: 'lines', except: [])]
    public array $selectedLines = [];

    /** Estaciones seleccionadas (vacío = todas las de las líneas). Va en la URL (?wcs=) y se guarda en el navegador. */
    #[Url(as: 'wcs', except: [])]
    public array $selectedWorkCenters = [];

    /** Modal "Cant. Sin Orden": renglón abierto (work center + turno); null = cerrado */
    public ?int $noOrderWorkCenterId = null;
    public ?string $noOrderShift = null;

    /**
     * Catálogos para los filtros. Se entregan una sola vez en la página (no viajan
     * con el componente en cada refresco).
     */
    public static function catalog(): array
    {
        return Cache::store(self::CACHE_STORE)->remember('line_progress:catalog', self::CATALOG_TTL, function () {
            $lines = Line::query()
                ->select('lines.id', 'lines.name', 'lines.color', 'areas.name as area_name')
                ->leftJoin('areas', 'lines.area_id', '=', 'areas.id')
                ->whereHas('workCenters')
                ->orderBy('areas.name')
                ->orderBy('lines.name')
                ->get();

            $workCenters = WorkCenter::query()
                ->select('id', 'name', 'line_id')
                ->whereIn('line_id', $lines->pluck('id'))
                ->get()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);

            $lineNames = $lines->pluck('name', 'id');

            return [
                'lines' => $lines->map(fn ($line) => [
                    'id' => (int) $line->id,
                    'name' => $line->name,
                    'color' => $line->color,
                    'full_name' => ($line->area_name ? $line->area_name . ' - ' : '') . $line->name,
                ])->values()->all(),
                'workCenters' => $workCenters->map(fn ($wc) => [
                    'id' => (int) $wc->id,
                    'name' => $wc->name,
                    'line_id' => (int) $wc->line_id,
                    'line_name' => $lineNames[$wc->line_id] ?? '',
                ])->values()->all(),
            ];
        });
    }

    public function mount(): void
    {
        $this->weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->normalizeFilters();
    }

    public function updatedSelectedLines(): void
    {
        $this->normalizeFilters();
    }

    public function updatedSelectedWorkCenters(): void
    {
        $this->normalizeFilters();
    }

    /**
     * Enteros y sin duplicados. Si hay líneas elegidas, las estaciones que no
     * pertenecen a ellas se quitan (al quitar una línea, se van sus estaciones).
     */
    private function normalizeFilters(): void
    {
        $this->selectedLines = array_values(array_unique(array_map('intval', $this->selectedLines)));
        $this->selectedWorkCenters = array_values(array_unique(array_map('intval', $this->selectedWorkCenters)));

        if (!empty($this->selectedLines) && !empty($this->selectedWorkCenters)) {
            $allowed = collect(self::catalog()['workCenters'])
                ->whereIn('line_id', $this->selectedLines)
                ->pluck('id')
                ->all();
            $this->selectedWorkCenters = array_values(array_intersect($this->selectedWorkCenters, $allowed));
        }
    }

    /** Botón "Limpiar filtros": vuelve a toda la planta (el navegador borra también la selección guardada) */
    public function clearFilters(): void
    {
        $this->selectedLines = [];
        $this->selectedWorkCenters = [];
    }

    public function showNoOrder(int $workCenterId, string $shift): void
    {
        $this->noOrderWorkCenterId = $workCenterId;
        $this->noOrderShift = $shift;
    }

    public function closeNoOrder(): void
    {
        $this->noOrderWorkCenterId = null;
        $this->noOrderShift = null;
    }

    public function prevWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /**
     * Cachea con el TTL según la semana: las pasadas por 1 hora; la actual y las
     * futuras por 10 s (compartido entre todas las pantallas, a lo más 10 s de retraso).
     */
    private function cached(string $key, Carbon $weekEnd, callable $callback): mixed
    {
        $ttl = $weekEnd->lt(Carbon::today()) ? self::PAST_TTL : self::LIVE_TTL;

        return Cache::store(self::CACHE_STORE)->remember($key, $ttl, $callback);
    }

    /**
     * Totales de TODA la planta para la semana, como arreglos simples (no modelos:
     * serializados pesan ~10 veces menos). Una sola entrada de caché compartida
     * por todas las pantallas; cada una filtra sus líneas/estaciones después.
     * "v2": llaves nuevas al agregar el filtro de clases (M1–M4 y F1), para no
     * servir cachés guardados antes de ese filtro.
     */
    private function weekRows(Carbon $start, Carbon $end): array
    {
        return $this->cached("line_progress:v2:week:{$start->toDateString()}", $end, function () use ($start, $end) {
            return ProductionRecord::getLineWeeklyProgress([], $start->toDateString(), $end->toDateString())
                ->map(fn ($r) => [
                    'area' => $r->area_name ?: 'Sin área',
                    'line_id' => (int) $r->line_id,
                    'line_name' => $r->line_name,
                    'line_color' => $r->line_color,
                    'wc_id' => (int) $r->work_center_id,
                    'wc_name' => $r->work_center_name,
                    'shift' => $r->shift,
                    'date' => Carbon::parse($r->planned_date)->toDateString(),
                    'plan' => (int) $r->plan,
                    'real' => (int) $r->with_order,   // real = producido con orden
                    'no_order' => (int) $r->no_order, // sin orden: columna "Cant. Sin Orden"
                    'in_progress' => (int) $r->in_progress > 0,
                ])
                ->all();
        });
    }

    private function shifts(): array
    {
        // Se muestran siempre todos los turnos de cada estación: se puede producir
        // material de otro turno (o de otra semana) y debe verse contra qué plan.
        return Cache::store(self::CACHE_STORE)->remember('line_progress:shifts', self::CATALOG_TTL, fn () => Shift::getAllShifts()
            ->map(fn ($s) => ['abbr' => $s->abbreviation, 'name' => $s->name])
            ->values()
            ->all());
    }

    /**
     * Tramos del medidor semicircular. El 100 % del arco es máx(plan, real):
     *  - plan (azul): lo que hay que cubrir
     *  - real (verde, producido con orden): va cubriendo el plan
     * Lo producido sin orden no va en el medidor: tiene su propia columna.
     */
    private function gaugeArc(int $plan, int $real): array
    {
        $total = max($plan, $real);

        if ($total <= 0) {
            return ['plan' => null, 'real' => null];
        }

        // Cada tramo es un sector cerrado del anillo: así lleva contorno fuerte y relleno claro, como las barras
        return [
            'plan' => $plan > 0 ? $this->ringSector(0, $plan / $total * 100) : null,
            'real' => $real > 0 ? $this->ringSector(0, $real / $total * 100) : null,
        ];
    }

    /**
     * Path SVG de un sector del anillo semicircular (centro 50,50; radios 37–47),
     * entre las posiciones $from y $to (0 = extremo izquierdo, 100 = extremo derecho).
     * Un decimal basta: el medidor mide ~128 px sobre un viewBox de 100.
     */
    private function ringSector(float $from, float $to): string
    {
        $outer = 47;
        $inner = 37;
        $point = function (float $pos, float $r) {
            $angle = M_PI * (1 - $pos / 100);
            return round(50 + $r * cos($angle), 1) . ' ' . round(50 - $r * sin($angle), 1);
        };

        return 'M' . $point($from, $outer)
            . "A{$outer} {$outer} 0 0 1 " . $point($to, $outer)
            . 'L' . $point($to, $inner)
            . "A{$inner} {$inner} 0 0 0 " . $point($from, $inner)
            . 'Z';
    }

    public function render()
    {
        $start = Carbon::parse($this->weekStart)->startOfDay();
        $end = $start->copy()->addDays(6); // domingo

        $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        // Encabezados de los 7 días (lunes a domingo)
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = [
                'name' => $dayNames[$i],
                'label' => $d->day . ' ' . $meses[$d->month - 1],
                'date' => $d->toDateString(),
                'isToday' => $d->isToday(),
            ];
        }

        $shifts = $this->shifts();

        // Filtro: las estaciones mandan; si no hay, las líneas; si no hay ninguno, toda la planta
        $rows = collect($this->weekRows($start, $end));
        if (!empty($this->selectedWorkCenters)) {
            $rows = $rows->whereIn('wc_id', $this->selectedWorkCenters);
        } elseif (!empty($this->selectedLines)) {
            $rows = $rows->whereIn('line_id', $this->selectedLines);
        }

        // Agrupar: área => línea => work center => turno => fecha => totales
        $map = [];
        foreach ($rows as $r) {
            $line = &$map[$r['area']][$r['line_id']];
            $line['name'] = $r['line_name'];
            $line['color'] = $r['line_color'];

            $wc = &$line['workCenters'][$r['wc_name']];
            $wc['id'] = $r['wc_id'];
            $wc['inProgress'] = ($wc['inProgress'] ?? false) || $r['in_progress'];
            $wc['cells'][$r['shift']][$r['date']] = [
                'plan' => $r['plan'],
                'real' => $r['real'],
                'noOrder' => $r['no_order'],
                'inProgress' => $r['in_progress'],
            ];
            unset($line, $wc);
        }

        // Transformar a estructura ordenada para la vista
        ksort($map, SORT_NATURAL | SORT_FLAG_CASE);
        $areas = [];
        foreach ($map as $areaName => $lines) {
            uasort($lines, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

            $areaLines = [];
            foreach ($lines as $lineId => $line) {
                $workCenters = $line['workCenters'];
                uksort($workCenters, 'strnatcasecmp');

                $wcRows = [];
                foreach ($workCenters as $wcName => $wc) {
                    $shiftRows = [];
                    foreach ($shifts as $shift) {
                        $cells = [];
                        $noOrderTotal = 0;
                        foreach ($wc['cells'][$shift['abbr']] ?? [] as $date => $c) {
                            $c['arc'] = $this->gaugeArc($c['plan'], $c['real']);
                            $c['percent'] = $c['plan'] > 0 ? (int) round($c['real'] / $c['plan'] * 100) : null;
                            $cells[$date] = $c;
                            $noOrderTotal += $c['noOrder'];
                        }
                        $shiftRows[] = [
                            'shift' => $shift['abbr'],
                            'shift_name' => $shift['name'],
                            'cells' => $cells,
                            'noOrderTotal' => $noOrderTotal, // acumulado de la semana en registros sin orden
                        ];
                    }

                    $wcRows[] = [
                        'id' => $wc['id'],
                        'name' => $wcName,
                        'inProgress' => $wc['inProgress'],
                        'shifts' => $shiftRows,
                    ];
                }

                $areaLines[] = [
                    'id' => $lineId,
                    'name' => $line['name'],
                    'color' => $line['color'],
                    'workCenters' => $wcRows,
                ];
            }

            $areas[] = ['name' => $areaName, 'lines' => $areaLines];
        }

        // Modal "Cant. Sin Orden": números de parte del renglón abierto (se recalcula en cada refresco)
        $noOrderDetail = null;
        if ($this->noOrderWorkCenterId && $this->noOrderShift) {
            $wcId = $this->noOrderWorkCenterId;
            $shift = $this->noOrderShift;
            $parts = $this->cached("line_progress:v2:no_order:{$start->toDateString()}:{$wcId}:{$shift}", $end, fn () =>
                ProductionRecord::getNoOrderPartsByWorkCenter($wcId, $shift, $start->toDateString(), $end->toDateString())
                    ->map(fn ($p) => ['number' => $p->part_number, 'name' => $p->part_name, 'quantity' => (int) $p->quantity])
                    ->all()
            );
            $noOrderDetail = [
                'workCenter' => collect(self::catalog()['workCenters'])->firstWhere('id', $wcId)['name'] ?? '',
                'shift' => collect($shifts)->firstWhere('abbr', $shift)['name'] ?? $shift,
                'parts' => $parts,
                'total' => array_sum(array_column($parts, 'quantity')),
            ];
        }

        $currentWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        return view('livewire.guest.line-progress', [
            'noOrderDetail' => $noOrderDetail,
            'days' => $days,
            'areas' => $areas,
            'weekLabel' => $start->day . ' ' . $meses[$start->month - 1] . ' – ' . $end->day . ' ' . $meses[$end->month - 1] . ' ' . $end->year,
            'isCurrentWeek' => $this->weekStart === $currentWeekStart,
            'lastUpdated' => Carbon::now()->format('H:i:s'),
        ]);
    }
}
