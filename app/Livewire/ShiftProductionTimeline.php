<?php

namespace App\Livewire;

use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ShiftProductionTimeline extends Component
{
    public string $chartId;
    public $workCenter;

    /** Etiquetas del eje Y: número de parte por barra */
    public array $labels = [];

    /** Rango de cada barra en horas-desde-el-inicio-del-turno: [[inicio, fin], ...] */
    public array $ranges = [];

    /** Cantidad producida por barra (se pinta sobre la barra) */
    public array $quantities = [];

    /** Texto "HH:mm - HH:mm" por barra para el tooltip */
    public array $timeLabels = [];

    /** Duración del turno en horas (ancho del eje X) */
    public float $durationHours = 12;

    /** Inicio del turno en ISO, para calcular las etiquetas de hora en el eje X */
    public ?string $shiftStartIso = null;

    /** Nombre del turno actual */
    public ?string $shiftName = null;

    /** Fecha del turno actual */
    public ?string $plannedDate = null;

    public function mount($workCenter): void
    {
        $this->chartId = 'timeline_' . Str::random(10);
        $this->workCenter = $workCenter;
        $this->refreshTimeline();
    }

    #[On('refresh-timeline')]
    public function refreshTimeline(): void
    {
        $now = Carbon::now();
        $current = Shift::getCurrentShiftInfo($now);

        $this->labels = [];
        $this->ranges = [];
        $this->quantities = [];
        $this->timeLabels = [];

        // Sin turno activo: mandar todo vacío
        if (!$current->shift || !$current->timeRange) {
            $this->shiftName = null;
            $this->plannedDate = null;
            $this->dispatchTimeline();
            return;
        }

        $shiftStart = $current->timeRange->startDateTime->copy();
        $shiftEnd = $current->timeRange->endDateTime->copy();

        $this->shiftStartIso = $shiftStart->toIso8601String();
        $this->durationHours = round(abs($shiftStart->diffInMinutes($shiftEnd)) / 60, 2);
        $this->shiftName = $current->shift->name;
        $this->plannedDate = $shiftStart->format('d-m-Y');

        $records = ProductionRecord::getShiftProductionTimeline(
            $this->workCenter,
            $current->shift->id,
            $current->date
        );

        foreach ($records as $record) {
            $start = Carbon::parse($record->production_start);
            // Si aún no termina, la barra llega hasta "ahora"
            $inProgress = empty($record->production_end);
            $end = $inProgress ? $now->copy() : Carbon::parse($record->production_end);

            // Acotar la barra al horario del turno actual
            if ($start->lt($shiftStart)) {
                $start = $shiftStart->copy();
            }
            if ($end->gt($shiftEnd)) {
                $end = $shiftEnd->copy();
            }

            // Descartar barras sin duración válida dentro del turno
            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $startOffset = round(abs($shiftStart->diffInMinutes($start)) / 60, 3);
            $endOffset = round(abs($shiftStart->diffInMinutes($end)) / 60, 3);

            $this->labels[] = $record->part_number;
            $this->ranges[] = [$startOffset, $endOffset];
            $this->quantities[] = (int) $record->produced_quantity;
            $this->timeLabels[] = $start->format('H:i') . ' - '
                . ($inProgress ? 'en proceso' : $end->format('H:i'));
        }

        $this->dispatchTimeline();
    }

    private function dispatchTimeline(): void
    {
        $this->dispatch('update-timeline', [
            'labels' => $this->labels,
            'ranges' => $this->ranges,
            'quantities' => $this->quantities,
            'timeLabels' => $this->timeLabels,
            'durationHours' => $this->durationHours,
            'shiftStartIso' => $this->shiftStartIso,
        ]);
    }

    public function render()
    {
        return view('livewire.shift-production-timeline');
    }
}
