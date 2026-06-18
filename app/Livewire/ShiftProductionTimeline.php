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

    /** Rango de cada barra en horas-desde-el-inicio-de-la-línea: [[inicio, fin], ...] */
    public array $ranges = [];

    /** Cantidad producida por barra (se pinta sobre la barra) */
    public array $quantities = [];

    /** Texto "HH:mm - HH:mm" por barra para el tooltip */
    public array $timeLabels = [];

    /** Ancho total del eje X en horas (turno anterior + actual) */
    public float $durationHours = 12;

    /** Inicio de la línea de tiempo en ISO (inicio del turno anterior si existe) */
    public ?string $shiftStartIso = null;

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
        $previous = Shift::getPreviousShiftInfo($now);

        $this->labels = [];
        $this->ranges = [];
        $this->quantities = [];
        $this->timeLabels = [];

        // Sin turno activo: mandar todo vacío
        if (!$current->shift || !$current->timeRange) {
            $this->dispatchTimeline();
            return;
        }

        $currentStart = $current->timeRange->startDateTime->copy();
        $currentEnd = $current->timeRange->endDateTime->copy();

        // La línea de tiempo arranca en el turno anterior (si existe) y termina al final del actual
        $hasPrevious = $previous->shift && $previous->timeRange;
        $baseStart = $hasPrevious ? $previous->timeRange->startDateTime->copy() : $currentStart->copy();
        $timelineEnd = $currentEnd->copy();

        $this->shiftStartIso = $baseStart->toIso8601String();
        $this->durationHours = round(abs($baseStart->diffInMinutes($timelineEnd)) / 60, 2);

        // Turno anterior primero (queda abajo), luego el actual
        if ($hasPrevious) {
            $previousRecords = ProductionRecord::getShiftProductionTimeline(
                $this->workCenter,
                $previous->shift->id,
                $previous->date
            );
            $this->appendRecords($previousRecords, $baseStart, $timelineEnd, $now);
        }

        $currentRecords = ProductionRecord::getShiftProductionTimeline(
            $this->workCenter,
            $current->shift->id,
            $current->date
        );
        $this->appendRecords($currentRecords, $baseStart, $timelineEnd, $now);

        $this->dispatchTimeline();
    }

    /**
     * Agrega las barras de una colección de registros, acotadas a [$baseStart, $timelineEnd]
     * y con offsets en horas desde $baseStart.
     */
    private function appendRecords($records, Carbon $baseStart, Carbon $timelineEnd, Carbon $now): void
    {
        foreach ($records as $record) {
            $start = Carbon::parse($record->production_start);
            $inProgress = empty($record->production_end);
            $end = $inProgress ? $now->copy() : Carbon::parse($record->production_end);

            // Acotar la barra a la ventana visible de la línea de tiempo
            if ($start->lt($baseStart)) {
                $start = $baseStart->copy();
            }
            if ($end->gt($timelineEnd)) {
                $end = $timelineEnd->copy();
            }

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $startOffset = round(abs($baseStart->diffInMinutes($start)) / 60, 3);
            $endOffset = round(abs($baseStart->diffInMinutes($end)) / 60, 3);

            $this->labels[] = $record->part_number;
            $this->ranges[] = [$startOffset, $endOffset];
            $this->quantities[] = (int) $record->produced_quantity;
            $this->timeLabels[] = $start->format('d-m H:i') . ' - '
                . ($inProgress ? 'en proceso' : $end->format('d-m H:i'));
        }
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
