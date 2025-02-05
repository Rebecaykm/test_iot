<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductionGraph extends Component
{
    public string|null $chartId = null;
    public array $labels = [];
    public array $datasets = [];

    public bool $realTime = false;
    public $workCenter;

    public $shift;
    public $startDateTime;
    public $endDateTime;

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
        $now = Carbon::now();

        $this->shift = Shift::getShift($now);
        $range = Shift::getShiftDateTimeRange($this->shift, $now);

        $this->startDateTime = $range->startDateTime;
        $this->endDateTime = $range->endDateTime;

        $this->fetchGraphData();
    }


    public function fetchGraphData(): void
    {
        $histories = History::getProductionHistory($this->workCenter, $this->shift, $this->startDateTime, $this->endDateTime);

        $historyData = [];

        foreach ($histories as $record) {
            $dateTime = Carbon::parse($record->created_at)->format('Y-m-d H:00');

            if (!isset($historyData[$record->production_id])) {
                $historyData[$record->production_id] = [];
            }

            if (!isset($historyData[$record->production_id][$record->work_name])) {
                $historyData[$record->production_id][$record->work_name] = [];
            }

            if (!isset($historyData[$record->production_id][$record->work_name][$record->part_number])) {
                $historyData[$record->production_id][$record->work_name][$record->part_number] = [
                    'planned_quantity' => $record->planned_quantity,
                    'production_per_hour' => []
                ];
            }

            if (!isset($historyData[$record->production_id][$record->work_name][$record->part_number]['production_per_hour'][$dateTime])) {
                $historyData[$record->production_id][$record->work_name][$record->part_number]['production_per_hour'][$dateTime] = 0;
            }

            $historyData[$record->production_id][$record->work_name][$record->part_number]['production_per_hour'][$dateTime] = max(
                $historyData[$record->production_id][$record->work_name][$record->part_number]['production_per_hour'][$dateTime],
                $record->quantity
            );
        }

        $groupedData = [
            'work_center_name' => '',
            'planned_quantity' => 0,
            'production_per_hour' => []
        ];

        foreach ($historyData as $history) {
            foreach ($history as $workName =>  $record) {
                foreach ($record as $data) {
                    $groupedData['work_center_name'] = $workName;
                    $groupedData['planned_quantity'] += $data['planned_quantity'] ?? 0;
                    foreach ($data['production_per_hour'] as $hourKey => $hour) {
                        $groupedData['production_per_hour'][$hourKey] = ($groupedData['production_per_hour'][$hourKey] ?? 0) + ($hour ?? 0);
                    }
                }
            }
        }

        $hours = $this->startDateTime->diffInHours($this->endDateTime);

        $plannedPerHour = round($groupedData['planned_quantity'] / $hours, 3);
        $plannedData = [];
        $accumulatedPlanned = 0;

        foreach ($groupedData['production_per_hour'] as $hour => $production) {
            $accumulatedPlanned += $plannedPerHour;
            $plannedData[] = $accumulatedPlanned;
        }

        $productionData = array_values($groupedData['production_per_hour']);
        $labels = array_keys($groupedData['production_per_hour']);

        $this->labels = $labels;
        $this->datasets =  [
            [
                'label' => 'Cantidad Planeada Por Hora',
                'data' => $plannedData,
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                'borderColor' => 'rgb(255, 99, 132)',
                'borderWidth' => 2
            ],
            [
                'label' => 'Cantidad Producida por Hora',
                'data' => $productionData,
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'borderColor' => 'rgb(75, 192, 192)',
                'borderWidth' => 2
            ]
        ];
    }

    public function render()
    {
        return view('livewire.production-graph');
    }
}
