<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductionGraph extends Component
{
    public string|null $chartId = null;
    public array $labels = [];
    public array $data = [];

    public $workCenter;
    public $shift;
    public $startDateTime;
    public $endDateTime;

    public function __construct()
    {
        $this->chartId = Str::ulid();
    }

    public function mount($workCenter): void
    {
        $this->workCenter = $workCenter;

        $this->refreshGraph();
    }

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
            // dd($record);
            $hourKey = Carbon::parse($record->created_at)->format('Y-m-d H:00');

            $historyData[$record->production_id][$record->work_name][$record->part_number]['planned_quantity'] = $record->planned_quantity;
            $historyData[$record->production_id][$record->work_name][$record->part_number]['production_per_hour'][$hourKey] =
                ($historyData[$record->production_id][$record->work_name][$record->part_number]['production_per_hour'][$hourKey] ?? 0) + $record->quantity;
        }

        $groupedHistoryData = [];

        foreach ($historyData as $record){
            // $hourKey = Carbon::parse($record->created_at)->format('Y-m-d H:00');

        }

        $hours = $this->startDateTime->diffInHours($this->endDateTime);

    }

    public function render()
    {
        dd($this->shift, $this->startDateTime, $this->endDateTime);
        return view('livewire.production-graph');
    }
}
