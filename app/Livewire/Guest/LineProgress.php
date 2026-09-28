<?php

namespace App\Livewire\Guest;

use App\Models\Line;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

class LineProgress extends Component
{
    /** Lunes de la semana mostrada (Y-m-d) */
    public string $weekStart = '';

    /** Líneas seleccionadas (vacío = todas). Va en la URL (?lines=) y se guarda en el navegador. */
    #[Url(as: 'lines', except: [])]
    public array $selectedLines = [];

    /** Catálogo de líneas para el select múltiple */
    public array $allLines = [];

    /** Modal "Cant. Sin Orden": renglón abierto (work center + turno); null = cerrado */
    public ?int $noOrderWorkCenterId = null;
    public ?string $noOrderShift = null;

    public function mount(): void
    {
        $this->weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        $this->allLines = Line::query()
            ->select('lines.id', 'lines.name', 'lines.color', 'areas.name as area_name')
            ->leftJoin('areas', 'lines.area_id', '=', 'areas.id')
            ->whereHas('workCenters')
            ->orderBy('areas.name')
            ->orderBy('lines.name')
            ->get()
            ->map(fn ($line) => [
                'id' => (int) $line->id,
                'name' => $line->name,
                'color' => $line->color,
                'full_name' => ($line->area_name ? $line->area_name . ' - ' : '') . $line->name,
            ])
            ->all();

        $this->selectedLines = array_values(array_map('intval', $this->selectedLines));
    }

    public function updatedSelectedLines(): void
    {
        $this->selectedLines = array_values(array_map('intval', $this->selectedLines));
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
     */
    private function ringSector(float $from, float $to): string
    {
        $outer = 47;
        $inner = 37;
        $point = function (float $pos, float $r) {
            $angle = M_PI * (1 - $pos / 100);
            return round(50 + $r * cos($angle), 3) . ' ' . round(50 - $r * sin($angle), 3);
        };

        return 'M ' . $point($from, $outer)
            . " A {$outer} {$outer} 0 0 1 " . $point($to, $outer)
            . ' L ' . $point($to, $inner)
            . " A {$inner} {$inner} 0 0 0 " . $point($from, $inner)
            . ' Z';
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

        // Se muestran siempre todos los turnos de cada estación: se puede producir
        // material de otro turno (o de otra semana) y debe verse contra qué plan.
        $shifts = Shift::getAllShifts()
            ->map(fn ($s) => ['abbr' => $s->abbreviation, 'name' => $s->name])
            ->values()
            ->all();

        $rows = ProductionRecord::getLineWeeklyProgress(
            $this->selectedLines,
            $start->toDateString(),
            $end->toDateString()
        );

        // Agrupar: área => línea => work center => turno => fecha => totales
        $map = [];
        foreach ($rows as $r) {
            $area = $r->area_name ?: 'Sin área';
            $lineId = (int) $r->line_id;
            $wc = $r->work_center_name;
            $date = Carbon::parse($r->planned_date)->toDateString();

            $map[$area][$lineId]['name'] = $r->line_name;
            $map[$area][$lineId]['color'] = $r->line_color;

            $withOrder = (int) $r->with_order;
            $noOrder = (int) $r->no_order;
            $inProgress = (int) $r->in_progress > 0;

            $map[$area][$lineId]['workCenters'][$wc]['id'] = (int) $r->work_center_id;
            $map[$area][$lineId]['workCenters'][$wc]['inProgress'] =
                ($map[$area][$lineId]['workCenters'][$wc]['inProgress'] ?? false) || $inProgress;
            $map[$area][$lineId]['workCenters'][$wc]['cells'][$r->shift][$date] = [
                'plan' => (int) $r->plan,
                'real' => $withOrder,      // real = producido con orden
                'noOrder' => $noOrder,     // sin orden: va en la columna "Cant. Sin Orden"
                'inProgress' => $inProgress,
            ];
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
            $parts = ProductionRecord::getNoOrderPartsByWorkCenter(
                $this->noOrderWorkCenterId,
                $this->noOrderShift,
                $start->toDateString(),
                $end->toDateString()
            );
            $noOrderDetail = [
                'workCenter' => WorkCenter::whereKey($this->noOrderWorkCenterId)->value('name'),
                'shift' => collect($shifts)->firstWhere('abbr', $this->noOrderShift)['name'] ?? $this->noOrderShift,
                'parts' => $parts->map(fn ($p) => [
                    'number' => $p->part_number,
                    'name' => $p->part_name,
                    'quantity' => (int) $p->quantity,
                ])->all(),
                'total' => (int) $parts->sum('quantity'),
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
