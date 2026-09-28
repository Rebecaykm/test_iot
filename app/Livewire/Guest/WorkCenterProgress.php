<?php

namespace App\Livewire\Guest;

use App\Models\ProductionRecord;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WorkCenterProgress extends Component
{
    public $workCenter;

    /** Lunes de la semana mostrada (Y-m-d) */
    public string $weekStart = '';

    /** Búsqueda por número de parte */
    public string $search = '';

    /**
     * Estación de estampado: solo a estas se les muestra el botón de "Tiempo de producción"
     * (/press-production-timeline), con la misma condición que /guest/production-records.
     */
    #[Locked]
    public bool $isStamping = false;

    public function mount($workCenter): void
    {
        $this->workCenter = $workCenter;
        $this->weekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        $wc = WorkCenter::with('line.area')->where('name', $workCenter)->first();
        $this->isStamping = strtolower((string) $wc?->line?->area?->name) === 'estampado';
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

        $records = ProductionRecord::getWorkCenterWeeklyProgress(
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
                $map[$part][$shift] = ['shift_name' => $r->shift_name, 'cells' => []];
            }

            $plan = (int) $r->planned_quantity;
            $real = (int) $r->produced_quantity;

            $map[$part][$shift]['cells'][$date][] = [
                'order' => $r->order_number,
                'plan' => $plan,
                'real' => $real,
                'percent' => $plan > 0 ? min(100, round($real / $plan * 100, 1)) : 0,
                'inProgress' => strcasecmp((string) $r->status_name, 'En progreso') === 0,
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
            $inProgress = false;
            foreach ($shifts as $abbr => $info) {
                $inProgress = $inProgress || collect($info['cells'])->flatten(1)->contains('inProgress', true);
                $shiftRows[] = [
                    'shift' => $abbr,
                    'shift_name' => $info['shift_name'],
                    'cells' => $info['cells'],
                ];
            }
            // inProgress: alguna orden de la parte está "En progreso" (etiqueta bajo el número de parte)
            $parts[] = ['part_number' => $partNumber, 'inProgress' => $inProgress, 'shifts' => $shiftRows];
        }

        $currentWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        return view('livewire.guest.work-center-progress', [
            'days' => $days,
            'parts' => $parts,
            'weekLabel' => $start->day . ' ' . $meses[$start->month - 1] . ' – ' . $end->day . ' ' . $meses[$end->month - 1] . ' ' . $end->year,
            'isCurrentWeek' => $this->weekStart === $currentWeekStart,
            'lastUpdated' => Carbon::now()->format('H:i:s'),
        ]);
    }
}
