<?php

namespace App\Livewire\Guest;

use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class WorkCenterDashboard extends Component
{
    public $now;
    public array $workCentersData = [];
    public bool $realTime = true;
    public string $chartId;

    public function mount()
    {
        $this->chartId = 'dashboard-' . uniqid();
        $this->refreshProductionRecords();
    }

    #[On('refresh-production-records')]
    public function refreshProductionRecords()
    {
        $this->now = Carbon::now()->format('Y-m-d H:i:s');
        $this->fetchProductionRecords();
    }

    public function fetchProductionRecords()
    {
        $currentTime = Carbon::now();

        $productionRecords = ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'areas.name AS area_name',
                'lines.name AS line_name',
                'lines.color AS line_color',
                'work_centers.id AS work_center_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->join('areas', 'lines.area_id', '=', 'areas.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $currentTime->toDateString())
            ->where('shifts.abbreviation', Shift::getShift($currentTime)->abbreviation)
            ->orderBy('lines.name', 'asc')
            ->orderBy('work_centers.name', 'asc')
            ->get();

        $this->prepareWorkCentersData($productionRecords);
    }

    protected function prepareWorkCentersData($productionRecords)
    {
        $this->workCentersData = [];

        $groupedData = $productionRecords->groupBy([
            'line_name',
            'work_name'
        ]);

        foreach ($groupedData as $lineName => $workCenters) {
            foreach ($workCenters as $workName => $records) {
                $workCenterPlanned = $records->sum('planned_quantity');
                $workCenterProduced = $records->sum('produced_quantity');
                $percentage = $workCenterPlanned > 0
                    ? round(($workCenterProduced / $workCenterPlanned) * 100)
                    : 0;

                $this->workCentersData[] = [
                    'id' => $records->first()->work_center_id,
                    'line' => $lineName,
                    'name' => $workName,
                    'planned' => $workCenterPlanned,
                    'produced' => $workCenterProduced,
                    'percentage' => $percentage,
                    'color' => $records->first()->line_color,
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.guest.work-center-dashboard');
    }
}
