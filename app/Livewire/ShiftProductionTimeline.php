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

    /** Inicio de la línea de tiempo en ISO (inicio del turno seleccionado) */
    public ?string $shiftStartIso = null;

    /** Fecha seleccionada (Y-m-d). Por defecto la del turno actual */
    public string $selectedDate = '';

    /** Turno seleccionado. Por defecto el turno actual */
    public ?int $selectedShiftId = null;

    /** Opciones del select de turnos [['id'=>, 'label'=>], ...] */
    public array $shiftOptions = [];

    /** True solo cuando se está viendo el turno actual en vivo (controla el auto-refresco) */
    public bool $isLive = true;

    /** Hora (HH:MM:SS) de la última actualización de los datos */
    public ?string $lastUpdated = null;

    public function mount($workCenter): void
    {
        $this->chartId = 'timeline_' . Str::random(10);
        $this->workCenter = $workCenter;

        // Catálogo de turnos para el select
        $this->shiftOptions = Shift::getAllShifts()
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->name . ' (' . substr($s->start_time, 0, 5) . ' - ' . substr($s->end_time, 0, 5) . ')',
            ])
            ->values()
            ->all();

        // Por defecto: turno actual y su fecha
        $now = Carbon::now();
        $current = Shift::getCurrentShiftInfo($now);
        $this->selectedShiftId = $current->shift?->id ?? ($this->shiftOptions[0]['id'] ?? null);
        $this->selectedDate = ($current->date ?? $now)->toDateString();

        $this->refreshTimeline();
    }

    /** Recargar al cambiar la fecha o el turno desde la vista */
    public function updatedSelectedDate(): void
    {
        $this->refreshTimeline();
    }

    public function updatedSelectedShiftId(): void
    {
        $this->refreshTimeline();
    }

    #[On('refresh-timeline')]
    public function refreshTimeline(): void
    {
        $now = Carbon::now();
        $this->lastUpdated = $now->format('H:i:s');

        $this->labels = [];
        $this->ranges = [];
        $this->quantities = [];
        $this->timeLabels = [];

        $shift = $this->selectedShiftId ? Shift::find($this->selectedShiftId) : null;
        $date = $this->selectedDate ? Carbon::parse($this->selectedDate) : null;
        $range = ($shift && $date) ? Shift::getShiftDateTimeRange($shift, $date) : null;

        // Selección inválida: mandar todo vacío
        if (!$range) {
            $this->isLive = false;
            $this->dispatchTimeline();
            return;
        }

        $baseStart = $range->startDateTime->copy();
        $timelineEnd = $range->endDateTime->copy();

        $this->shiftStartIso = $baseStart->toIso8601String();
        $this->durationHours = round(abs($baseStart->diffInMinutes($timelineEnd)) / 60, 2);

        // ¿Es el turno actual en vivo? (controla si se sigue auto-refrescando)
        $current = Shift::getCurrentShiftInfo($now);
        $this->isLive = $current->shift
            && $current->shift->id === $shift->id
            && $current->date->toDateString() === $date->toDateString();

        $records = ProductionRecord::getShiftProductionTimeline(
            $this->workCenter,
            $shift->id,
            $date
        );

        $this->buildBars($records, $baseStart, $timelineEnd, $now);

        $this->dispatchTimeline();
    }

    /**
     * Construye los segmentos de la línea de tiempo. Cada número de parte ocupa
     * UNA sola fila (no se repite), pero conserva un segmento por cada registro con
     * su propio inicio y fin. Así un material producido de 01:00 a 02:00 y luego de
     * 10:00 a 13:00 se muestra como dos barras en la misma fila.
     */
    private function buildBars($records, Carbon $baseStart, Carbon $timelineEnd, Carbon $now): void
    {
        $segments = [];      // un elemento por registro (cada barra)
        $firstStart = [];    // número de parte => timestamp del inicio más temprano

        foreach ($records as $record) {
            $start = Carbon::parse($record->production_start);
            $inProgress = empty($record->production_end);
            $end = $inProgress ? $now->copy() : Carbon::parse($record->production_end);

            // Acotar a la ventana visible de la línea de tiempo
            if ($start->lt($baseStart)) {
                $start = $baseStart->copy();
            }
            if ($end->gt($timelineEnd)) {
                $end = $timelineEnd->copy();
            }

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $partNumber = $record->part_number;

            $segments[] = [
                'part' => $partNumber,
                'start' => $start,
                'end' => $end,
                'quantity' => (int) $record->produced_quantity,
                'inProgress' => $inProgress,
            ];

            if (!isset($firstStart[$partNumber]) || $start->getTimestamp() < $firstStart[$partNumber]) {
                $firstStart[$partNumber] = $start->getTimestamp();
            }
        }

        // Categorías (eje Y): un número de parte por fila, ordenadas por su inicio más temprano
        asort($firstStart);
        $this->labels = array_keys($firstStart);

        // Segmentos ordenados cronológicamente
        usort($segments, fn ($a, $b) => $a['start']->getTimestamp() <=> $b['start']->getTimestamp());

        foreach ($segments as $segment) {
            $startOffset = round(abs($baseStart->diffInMinutes($segment['start'])) / 60, 3);
            $endOffset = round(abs($baseStart->diffInMinutes($segment['end'])) / 60, 3);

            // {x:[inicio,fin], y:número de parte} => varios segmentos pueden ir en la misma fila
            $this->ranges[] = ['x' => [$startOffset, $endOffset], 'y' => $segment['part']];
            $this->quantities[] = $segment['quantity'];
            $this->timeLabels[] = $segment['start']->format('d-m H:i') . ' - '
                . ($segment['inProgress'] ? 'en proceso' : $segment['end']->format('d-m H:i'));
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
            'isLive' => $this->isLive,
        ]);
    }

    public function render()
    {
        return view('livewire.shift-production-timeline');
    }
}
