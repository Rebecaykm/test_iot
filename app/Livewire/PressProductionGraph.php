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

        // 1. Generar bloques de 2 horas
        $timeBlocks = $this->generateTimeBlocks($previous->timeRange->startDateTime, $current->timeRange->endDateTime);
        $this->labels = array_keys($timeBlocks);

        // 2. Plan Acumulado (Dinámico)
        $this->calculatePlanned($previous, $current, $timeBlocks);

        // 3. Real Acumulado
        $this->calculateActual($previous->timeRange->startDateTime, $current->timeRange->endDateTime, $timeBlocks, $now);

        // 4. Diferencia
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

    private function calculatePlanned($prev, $current, $timeBlocks): void
    {
        $prevTotalPlanned = ProductionRecord::getProductionRecords($this->workCenter, $prev->shift->id, $prev->date)->sum('planned_quantity');
        $currTotalPlanned = ProductionRecord::getProductionRecords($this->workCenter, $current->shift->id, $current->date)->sum('planned_quantity');

        $prevHours = $prev->timeRange->startDateTime->diffInHours($prev->timeRange->endDateTime);
        $currHours = $current->timeRange->startDateTime->diffInHours($current->timeRange->endDateTime);

        $prevBlocksCount = max(1, $prevHours / 2);
        $currBlocksCount = max(1, $currHours / 2);

        $prevAvgPerBlock = $prevTotalPlanned / $prevBlocksCount;
        $currAvgPerBlock = $currTotalPlanned / $currBlocksCount;

        $accumulated = 0;
        $totalLabels = count($this->labels);
        $half = (int)($totalLabels / 2);

        $i = 1;
        foreach ($timeBlocks as $label => $val) {
            $accumulated += ($i <= $half) ? $prevAvgPerBlock : $currAvgPerBlock;
            $timeBlocks[$label] = round($accumulated);
            $i++;
        }
        $this->plannedData = array_values($timeBlocks);
    }

    private function calculateActual(Carbon $start, Carbon $end, $timeBlocks, $now): void
    {
        $histories = History::getProductionHistory($this->workCenter, $start, $end);

        $grouped = $histories->groupBy(function ($item) {
            $dt = $item->created_at->copy()->floorHour();
            if ($dt->hour % 2 !== 0) $dt->subHour();
            return $dt->format('d-m H:i') . ' a ' . $dt->addHours(2)->format('H:i');
        });

        $accumulated = 0;
        foreach ($timeBlocks as $label => $val) {
            $labelParts = explode(' a ', $label);
            $labelStart = Carbon::createFromFormat('d-m H:i', $labelParts[0]);

            if ($labelStart > $now) {
                $timeBlocks[$label] = null;
                continue;
            }

            $blockSum = 0;
            if (isset($grouped[$label])) {
                $blockSum = $grouped[$label]->groupBy('part_number')->map(function ($parts) {
                    $min = $parts->min('quantity');
                    return $parts->max('quantity') - (($min > 0) ? $min - 1 : $min);
                })->sum();
            }
            $accumulated += $blockSum;
            $timeBlocks[$label] = $accumulated;
        }
        $this->producedData = array_values($timeBlocks);
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
