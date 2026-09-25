<?php

namespace App\Livewire\Guest;

use App\Models\Line;
use App\Models\ProductionRecord;
use App\Models\Shift;
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

            $map[$area][$lineId]['workCenters'][$wc]['inProgress'] =
                ($map[$area][$lineId]['workCenters'][$wc]['inProgress'] ?? false) || $inProgress;
            $map[$area][$lineId]['workCenters'][$wc]['cells'][$r->shift][$date] = [
                'plan' => (int) $r->plan,
                'withOrder' => $withOrder,
                'noOrder' => $noOrder,
                'real' => $withOrder + $noOrder,
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
                        foreach ($wc['cells'][$shift['abbr']] ?? [] as $date => $c) {
                            // Plan y real comparten escala: la barra más larga de la celda ocupa el ancho completo
                            $max = max($c['plan'], $c['real']);
                            $c['planPct'] = $max > 0 ? round($c['plan'] / $max * 100, 1) : 0;
                            $c['withOrderPct'] = $max > 0 ? round($c['withOrder'] / $max * 100, 1) : 0;
                            $c['noOrderPct'] = $max > 0 ? round($c['noOrder'] / $max * 100, 1) : 0;
                            $cells[$date] = $c;
                        }
                        $shiftRows[] = ['shift' => $shift['abbr'], 'shift_name' => $shift['name'], 'cells' => $cells];
                    }

                    $wcRows[] = [
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

        $currentWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        return view('livewire.guest.line-progress', [
            'days' => $days,
            'areas' => $areas,
            'weekLabel' => $start->day . ' ' . $meses[$start->month - 1] . ' – ' . $end->day . ' ' . $meses[$end->month - 1] . ' ' . $end->year,
            'isCurrentWeek' => $this->weekStart === $currentWeekStart,
            'lastUpdated' => Carbon::now()->format('H:i:s'),
        ]);
    }
}
