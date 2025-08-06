<?php

namespace App\Livewire\Guest;

use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class WorkCenterDashboard extends Component
{
    public $now;
    public array $areasData = [];
    public array $allWorkCenters = [];
    public $selectedWorkCenter = null;
    public bool $realTime = true;
    public string $chartId;

    public function mount()
    {
        $this->chartId = 'dashboard-' . uniqid();
        $this->loadWorkCenters();
        $this->refreshProductionRecords();
    }

    protected function loadWorkCenters()
    {
        $this->allWorkCenters = WorkCenter::query()
            ->select('work_centers.id', 'work_centers.name')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->orderBy('work_centers.name')
            ->get()
            ->toArray();
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

        $query = ProductionRecord::query()
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
            ->where('shifts.abbreviation', Shift::getShift($currentTime)->abbreviation);

        // Filtrar por work center seleccionado si existe
        if ($this->selectedWorkCenter) {
            $query->where('work_centers.id', $this->selectedWorkCenter);
        }

        $productionRecords = $query
            ->orderBy('areas.name', 'asc')
            ->orderBy('lines.name', 'asc')
            ->orderBy('work_centers.name', 'asc')
            ->get();

        $this->prepareAreasData($productionRecords);
    }

    protected function prepareAreasData($productionRecords)
    {
        $this->areasData = [];

        $groupedData = $productionRecords->groupBy([
            'area_name',
            'line_name',
            'work_name'
        ]);

        foreach ($groupedData as $areaName => $lines) {
            $areaWorkCenters = [];

            foreach ($lines as $lineName => $workCenters) {
                foreach ($workCenters as $workName => $records) {
                    $workCenterQuantityPlanned = $records->sum('planned_quantity');
                    $workCenterQuantityUnplannedProduced = $records->where('planned_quantity', 0)->sum('produced_quantity');
                    $workCenterQuantityProduced = $records->sum('produced_quantity') - $workCenterQuantityUnplannedProduced;
                    $totalProduced = $records->sum('produced_quantity');

                    $percentage = $workCenterQuantityPlanned > 0
                        ? round(($totalProduced / $workCenterQuantityPlanned) * 100)
                        : 0;

                    $areaWorkCenters[] = [
                        'id' => $records->first()->work_center_id,
                        'line' => $lineName,
                        'name' => $workName,
                        'planned' => $workCenterQuantityPlanned,
                        'produced' => $workCenterQuantityProduced,
                        'unplanned' => $workCenterQuantityUnplannedProduced,
                        'percentage' => $percentage,
                        'color' => $records->first()->line_color,
                    ];
                }
            }

            if (!empty($areaWorkCenters)) {
                $this->areasData[] = [
                    'name' => $areaName,
                    'workCenters' => $areaWorkCenters
                ];
            }
        }
    }

    public function updatedSelectedWorkCenter()
    {
        $this->refreshProductionRecords();
    }

    public function render()
    {
        return view('livewire.guest.work-center-dashboard');
    }
}
