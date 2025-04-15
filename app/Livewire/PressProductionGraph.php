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
    public string|null $chartId = null;
    public array $labels = [];
    public array $plannedData = [];
    public array $producedData = [];

    public bool $realTime = false;
    public $workCenter;

    public $now;
    public $previousShift;
    public $currentShift;

    public function __construct()
    {
        $this->chartId = Str::ulid();
    }

    public function mount($workCenter, $realTime): void
    {
        $this->workCenter = $workCenter;
        $this->realTime = $realTime;

        $this->refreshGraph();
    }

    #[On('refresh-graph')]
    public function refreshGraph()
    {
        $this->now = Carbon::now();

        $this->currentShift = Shift::getShift($this->now);
        $this->previousShift = Shift::findPreviousShift($this->currentShift);

        $this->plannedProduction();

        $this->currentProduction();
    }


    public function currentProduction(): void
    {
        $previous = (object)[
            'model' => $this->previousShift,
            'timeRange' => Shift::getShiftDateTimeRange($this->previousShift, $this->now)
        ];

        $current = (object)[
            'model' => $this->currentShift,
            'timeRange' => Shift::getShiftDateTimeRange($this->currentShift, $this->now)
        ];

        $previousHistories = History::getProductionHistory($this->workCenter, $previous->timeRange->startDateTime, $previous->timeRange->endDateTime);
        $currentHistories = History::getProductionHistory($this->workCenter, $current->timeRange->startDateTime, $current->timeRange->endDateTime);

        $twoHourGroups = $previousHistories->groupBy(function ($item) {
            $hour = $item->created_at->format('H');
            $block = floor($hour / 2) * 2;
            return $item->created_at->format('Y-m-d ') . str_pad($block, 2, '0', STR_PAD_LEFT) . ':00';
        })->map(function ($twoHourGroup, $blockStartHour) {
            // Calcular la suma para este bloque de 2 horas
            $partNumbers = $twoHourGroup->groupBy('part_number')->map(function ($partNumberGroup) {
                $minQuantity = $partNumberGroup->min('quantity');
                $adjustedMin = ($minQuantity > 0) ? $minQuantity - 1 : $minQuantity;
                return $partNumberGroup->max('quantity') - $adjustedMin;
            });

            return [
                'hour_block' => $blockStartHour,
                'block_quantity' => $partNumbers->sum(),
                'accumulated' => 0
            ];
        })->sortBy('hour_block')->values();

        // 1. Primero obtenemos el resultado acumulado como lo tenías
        $accumulated = 0;
        $previousResult = $twoHourGroups->map(function ($block) use (&$accumulated) {
            $accumulated += $block['block_quantity'];
            $startHour = Carbon::parse($block['hour_block']);
            return [
                'hour' => $startHour->addHours(2)->format('Y-m-d H:00'),
                'quantity' => $accumulated
            ];
        })->keyBy('hour');

        $allBlocks = [];
        $previousBlock = Carbon::parse($previous->timeRange->startDateTime)->copy()->setTime(floor(Carbon::parse($previous->timeRange->startDateTime)->hour / 2) * 2, 0);

        while ($previousBlock <= Carbon::parse($previous->timeRange->endDateTime)) {
            $blockHour = $previousBlock->format('Y-m-d H:00');
            $allBlocks[$blockHour] = true;
            $previousBlock->addHours(2);
        }

        $previousCompletedResult = [];
        $lastValue = 0;

        foreach ($allBlocks as $blockHour => $_) {
            if (isset($previousResult[$blockHour])) {
                $lastValue = $previousResult[$blockHour]['quantity'];
                $previousCompletedResult[] = [
                    'hour' => $blockHour,
                    'quantity' => $lastValue
                ];
            } else {
                $previousCompletedResult[] = [
                    'hour' => $blockHour,
                    'quantity' => $lastValue
                ];
            }
        }

        usort($previousCompletedResult, function ($a, $b) {
            return strcmp($a['hour'], $b['hour']);
        });

        $twoHourGroups = $currentHistories->groupBy(function ($item) {
            $hour = $item->created_at->format('H');
            $block = floor($hour / 2) * 2;
            return $item->created_at->format('Y-m-d ') . str_pad($block, 2, '0', STR_PAD_LEFT) . ':00';
        })->map(function ($twoHourGroup, $blockStartHour) {
            $partNumbers = $twoHourGroup->groupBy('part_number')->map(function ($partNumberGroup) {
                $minQuantity = $partNumberGroup->min('quantity');
                $adjustedMin = ($minQuantity > 0) ? $minQuantity - 1 : $minQuantity;
                return $partNumberGroup->max('quantity') - $adjustedMin;
            });

            return [
                'hour_block' => $blockStartHour,
                'block_quantity' => $partNumbers->sum(),
                'accumulated' => 0
            ];
        })->sortBy('hour_block')->values();

        $accumulated = 0;
        $currentResult = $twoHourGroups->map(function ($block) use (&$accumulated) {
            $accumulated += $block['block_quantity'];
            $startHour = Carbon::parse($block['hour_block']);
            return [
                'hour' => $startHour->addHours(2)->format('Y-m-d H:00'),
                'quantity' => $accumulated
            ];
        })->keyBy('hour');

        $allBlocks = [];
        $currentBlock = Carbon::parse($current->timeRange->startDateTime)->copy()->setTime(floor(Carbon::parse($current->timeRange->startDateTime)->hour / 2) * 2, 0);

        while ($currentBlock <= Carbon::parse($current->timeRange->endDateTime)) {
            $blockHour = $currentBlock->format('Y-m-d H:00');
            $allBlocks[$blockHour] = true;
            $currentBlock->addHours(2);
        }

        $currentCompletedResult = [];
        $lastValue = 0;

        foreach ($allBlocks as $blockHour => $_) {
            if (isset($currentResult[$blockHour])) {
                $lastValue = $currentResult[$blockHour]['quantity'];
                $currentCompletedResult[] = [
                    'hour' => $blockHour,
                    'quantity' => $lastValue
                ];
            } else {
                $currentCompletedResult[] = [
                    'hour' => $blockHour,
                    'quantity' => $lastValue
                ];
            }
        }

        usort($currentCompletedResult, function ($a, $b) {
            return strcmp($a['hour'], $b['hour']);
        });

        $previous = collect($previousCompletedResult);
        $current = collect($currentCompletedResult);

        $combined = $previous->concat($current)
            ->groupBy('hour') // Agrupar por hora
            ->map(function ($items) {
                return [
                    'hour' => $items->first()['hour'],
                    'quantity' => $items->sum('quantity')
                ];
            })
            ->values()
            ->sortBy('hour')
            ->toArray();

        $this->producedData = collect($combined)->pluck('quantity')->toArray();
    }

    public function plannedProduction()
    {
        $previous = (object)[
            'model' => $this->previousShift,
            'timeRange' => Shift::getShiftDateTimeRange($this->previousShift, $this->now)
        ];

        $current = (object)[
            'model' => $this->currentShift,
            'timeRange' => Shift::getShiftDateTimeRange($this->currentShift, $this->now)
        ];

        $previousHoursDifference = Carbon::parse($previous->timeRange->startDateTime)
            ->diffInHours($previous->timeRange->endDateTime);

        $currentHoursDifference = Carbon::parse($current->timeRange->startDateTime)
            ->diffInHours($current->timeRange->endDateTime);

        $previousProductionRecords = ProductionRecord::getPressProductionRecord(
            $this->workCenter,
            $previous->model->id,
            $previous->timeRange->startDateTime
        );

        $currentProductionRecords = ProductionRecord::getPressProductionRecord(
            $this->workCenter,
            $current->model->id,
            $this->now
        );

        $previousQuantityPerTwoHours = $previousProductionRecords->sum('planned_quantity') / ($previousHoursDifference / 2);
        $currentQuantityPerTwoHours = $currentProductionRecords->sum('planned_quantity') / ($currentHoursDifference / 2);

        $hoursAndQuantities = [];

        $startDateTime = Carbon::parse($previous->timeRange->startDateTime);
        $endDateTime = Carbon::parse($previous->timeRange->endDateTime);
        $totalQuantity = 0;

        while ($startDateTime <= $endDateTime) {
            if (!$startDateTime->equalTo($previous->timeRange->startDateTime)) {
                $totalQuantity += $previousQuantityPerTwoHours;
            }

            $hourKey = $startDateTime->format('Y-m-d H:i:s');
            $hoursAndQuantities[$hourKey] = [
                'hour' => $hourKey,
                'quantity' => round($totalQuantity)
            ];

            $startDateTime->addHours(2);
        }

        $startDateTime = Carbon::parse($current->timeRange->startDateTime);
        $endDateTime = Carbon::parse($current->timeRange->endDateTime);
        $totalQuantity = 0;

        while ($startDateTime <= $endDateTime) {
            $hourKey = $startDateTime->format('Y-m-d H:i:s');

            if (isset($hoursAndQuantities[$hourKey]) && $hoursAndQuantities[$hourKey]['quantity'] > 0) {
                $startDateTime->addHours(2);
                continue;
            }

            if (!$startDateTime->equalTo($current->timeRange->startDateTime)) {
                $totalQuantity += $currentQuantityPerTwoHours;
            }

            $hoursAndQuantities[$hourKey] = [
                'hour' => $hourKey,
                'quantity' => round($totalQuantity)
            ];

            $startDateTime->addHours(2);
        }

        $startDateTime = Carbon::parse(min(
            $previous->timeRange->startDateTime,
            $current->timeRange->startDateTime
        ));

        $endDateTime = Carbon::parse(max(
            $previous->timeRange->endDateTime,
            $current->timeRange->endDateTime
        ));

        $finalHoursAndQuantities = [];

        while ($startDateTime <= $endDateTime) {
            $hourKey = $startDateTime->format('Y-m-d H:i:s');

            $finalHoursAndQuantities[] = $hoursAndQuantities[$hourKey] ?? [
                'hour' => $hourKey,
                'quantity' => 0
            ];

            $startDateTime->addHours(2);
        }

        $this->plannedData = collect($finalHoursAndQuantities)->pluck('quantity')->toArray();
        $this->labels = collect($finalHoursAndQuantities)->pluck('hour')->toArray();
    }

    public function render()
    {
        return view('livewire.press-production-graph');
    }
}
