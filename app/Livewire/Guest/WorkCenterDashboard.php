<?php

namespace App\Livewire\Guest;

use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Component;

class WorkCenterDashboard extends Component
{
    public $now;
    public $productionRecordData;

    public function mount()
    {
        $this->freshProductionRecords();
    }

    public function freshProductionRecords()
    {
        $this->now = Carbon::now();
        $this->fetchProductionRecords();
    }

    public function fetchProductionRecords()
    {
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
            ->where('production_records.planned_date', $this->now->toDateString())
            ->where('shifts.abbreviation', Shift::getShift($this->now)->abbreviation)
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->orderBy('lines.name', 'asc')
            ->get();

        $this->productionRecordData = $productionRecords->groupBy([
            'area_name',
            'line_name',
            'work_name'
        ])->map(function ($areaGroups) {
            return $areaGroups->map(function ($lineGroups) {
                return $lineGroups->map(function ($workCenterGroups) {
                    return [
                        'id' => $workCenterGroups->first()->work_center_id,
                        'work_name' => $workCenterGroups->first()->work_name,
                        'color' => $workCenterGroups->first()->line_color,
                        'total_planned' => $workCenterGroups->sum('planned_quantity'),
                        'total_produced' => $workCenterGroups->sum('produced_quantity'),
                        'planned_percentage' => 100,
                        'produced_percentage' => $workCenterGroups->sum('planned_quantity') > 0
                            ? round(($workCenterGroups->sum('produced_quantity') / $workCenterGroups->sum('planned_quantity')) * 100)
                            : 0,
                    ];
                });
            });
        });
    }

    public function render()
    {
        return view('livewire.guest.work-center-dashboard');
    }
}
