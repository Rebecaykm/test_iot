<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class PressProductionGraph extends Component
{
    public string $chartId;
    public array $labels = [];
    public array $plannedData = [];
    public array $producedData = [];
    public array $differenceData = [];
    public $workCenter;

    public function mount($workCenter): void
    {
        $this->chartId = 'chart_' . Str::random(10);
        $this->workCenter = $workCenter;
        $this->refreshGraph();
    }

    #[On('refresh-graph')]
    public function refreshGraph(): void
    {
        $now = Carbon::now();
        $current = Shift::getCurrentShiftInfo($now);
        $previous = Shift::getPreviousShiftInfo($now);

        // Generar bloques separados para cada turno
        $previousBlocks = $this->generateTimeBlocks(
            $previous->timeRange->startDateTime,
            $previous->timeRange->endDateTime
        );

        $currentBlocks = $this->generateTimeBlocks(
            $current->timeRange->startDateTime,
            $current->timeRange->endDateTime
        );

        // Combinar labels en orden cronológico
        $this->labels = array_merge(array_keys($previousBlocks), array_keys($currentBlocks));

        // Calcular plan para cada turno por separado
        $previousPlanned = $this->calculatePlannedForShift($previous, $previousBlocks);
        $currentPlanned = $this->calculatePlannedForShift($current, $currentBlocks);
        $this->plannedData = array_merge($previousPlanned, $currentPlanned);

        // Calcular producción real para cada turno por separado
        $previousProduced = $this->calculateActualForShift(
            $previous->timeRange->startDateTime,
            $previous->timeRange->endDateTime,
            $previousBlocks,
            $now
        );

        $currentProduced = $this->calculateActualForShift(
            $current->timeRange->startDateTime,
            $current->timeRange->endDateTime,
            $currentBlocks,
            $now
        );

        $this->producedData = array_merge($previousProduced, $currentProduced);

        // Calcular diferencias
        $this->calculateDifference();

        $this->dispatch('update-chart', [
            'labels' => $this->labels,
            'planned' => $this->plannedData,
            'produced' => $this->producedData,
            'difference' => $this->differenceData,
        ]);
    }

    private function generateTimeBlocks(Carbon $start, Carbon $end): array
    {
        $blocks = [];
        $currentBlock = $start->copy()->floorHour();
        if ($currentBlock->hour % 2 !== 0) $currentBlock->subHour();

        while ($currentBlock < $end) {
            $startRange = $currentBlock->copy();
            $endRange = $currentBlock->copy()->addHours(2);
            $label = $startRange->format('d-m H:i') . ' a ' . $endRange->format('H:i');
            $blocks[$label] = 0;
            $currentBlock->addHours(2);
        }
        return $blocks;
    }

    /**
     * Calcula el plan acumulado para un turno específico
     */
    private function calculatePlannedForShift($shiftInfo, $timeBlocks): array
    {
        // Obtener el total planeado para este turno
        $totalPlanned = ProductionRecord::getProductionRecords(
            $this->workCenter,
            $shiftInfo->shift->id,
            $shiftInfo->date
        )->sum('planned_quantity');

        // Calcular cantidad de bloques (cada bloque son 2 horas)
        $hoursInShift = $shiftInfo->timeRange->startDateTime->diffInHours($shiftInfo->timeRange->endDateTime);
        $blocksCount = max(1, $hoursInShift / 2);

        // Cantidad promedio por bloque
        $avgPerBlock = $totalPlanned / $blocksCount;

        // Crear el acumulado para este turno
        $accumulated = 0;
        $result = [];

        foreach ($timeBlocks as $label => $val) {
            $accumulated += $avgPerBlock;
            $result[] = round($accumulated);
        }

        return $result;
    }

    /**
     * Calcula la producción real acumulada para un turno específico
     */
    private function calculateActualForShift(Carbon $start, Carbon $end, $timeBlocks, $now): array
    {
        // Obtener el historial de producción para este turno
        $histories = History::getProductionHistory($this->workCenter, $start, $end);

        // Agrupar por bloques de tiempo
        $grouped = $histories->groupBy(function ($item) {
            $dt = $item->created_at->copy()->floorHour();
            if ($dt->hour % 2 !== 0) $dt->subHour();
            return $dt->format('d-m H:i') . ' a ' . $dt->addHours(2)->format('H:i');
        });

        // Calcular acumulado para este turno
        $accumulated = 0;
        $result = [];

        foreach ($timeBlocks as $label => $val) {
            $labelParts = explode(' a ', $label);
            $labelStart = Carbon::createFromFormat('d-m H:i', $labelParts[0]);

            // Si el bloque es futuro, poner null
            if ($labelStart > $now) {
                $result[] = null;
                continue;
            }

            // Sumar directamente todas las cantidades del bloque
            $blockSum = isset($grouped[$label])
                ? $grouped[$label]->sum('quantity')
                : 0;

            $accumulated += $blockSum;
            $result[] = $accumulated;
        }

        return $result;
    }

    private function calculateDifference(): void
    {
        $this->differenceData = [];
        foreach ($this->plannedData as $index => $planned) {
            $produced = $this->producedData[$index];
            if ($produced === null) {
                $this->differenceData[] = 0 - $planned;
            } else {
                $this->differenceData[] = $produced - $planned;
            }
        }
    }

    public function render()
    {
        return view('livewire.press-production-graph');
    }
}
