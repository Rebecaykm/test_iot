<?php

namespace App\Livewire;

use App\Models\ProductionRecord;
use Carbon\Carbon;
use Livewire\Component;

class ProductionWeekTable extends Component
{
    public $workCenter;

    /** Domingo de la semana mostrada (Y-m-d) */
    public string $weekStart = '';

    /** Búsqueda por número de parte */
    public string $search = '';

    public function mount($workCenter): void
    {
        $this->workCenter = $workCenter;
        $this->weekStart = Carbon::today()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function prevWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $next = Carbon::parse($this->weekStart)->addWeek();
        $currentWeekStart = Carbon::today()->startOfWeek(Carbon::SUNDAY);

        // No avanzar más allá de la semana actual
        if ($next->lessThanOrEqualTo($currentWeekStart)) {
            $this->weekStart = $next->toDateString();
        }
    }

    public function render()
    {
        $start = Carbon::parse($this->weekStart)->startOfDay();
        $end = $start->copy()->addDays(6); // sábado

        $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        // Encabezados de los 7 días (domingo a sábado)
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

        $records = ProductionRecord::getWeeklyProductionRecords(
            $this->workCenter,
            $start->toDateString(),
            $end->toDateString()
        );

        // Agrupar: parte => turno => fecha => [ {plan, real}, ... ]
        $map = [];
        foreach ($records as $r) {
            $part = $r->part_number;
            $shift = $r->shift;
            $date = Carbon::parse($r->planned_date)->toDateString();

            if (!isset($map[$part][$shift])) {
                $map[$part][$shift] = ['shift_name' => $r->shift, 'cells' => []];
            }

            $map[$part][$shift]['cells'][$date][] = [
                'plan' => (int) $r->planned_quantity,
                'real' => (int) $r->produced_quantity,
            ];
        }

        // Filtro por número de parte
        $search = trim($this->search);
        if ($search !== '') {
            $map = array_filter(
                $map,
                fn ($part) => stripos($part, $search) !== false,
                ARRAY_FILTER_USE_KEY
            );
        }

        // Orden natural ascendente por número de parte
        uksort($map, 'strnatcasecmp');

        // Transformar a filas para la vista (un número de parte agrupa sus turnos)
        $parts = [];
        foreach ($map as $partNumber => $shifts) {
            ksort($shifts); // D antes que N
            $shiftRows = [];
            foreach ($shifts as $abbr => $info) {
                $shiftRows[] = [
                    'shift' => $abbr,
                    'shift_name' => $info['shift_name'],
                    'cells' => $info['cells'],
                ];
            }
            $parts[] = ['part_number' => $partNumber, 'shifts' => $shiftRows];
        }

        $currentWeekStart = Carbon::today()->startOfWeek(Carbon::SUNDAY)->toDateString();

        return view('livewire.production-week-table', [
            'days' => $days,
            'parts' => $parts,
            'weekLabel' => $start->format('d') . ' – ' . $end->format('d') . ' ' . $meses[$end->month - 1] . ' ' . $end->year,
            'isCurrentWeek' => $this->weekStart === $currentWeekStart,
        ]);
    }
}
