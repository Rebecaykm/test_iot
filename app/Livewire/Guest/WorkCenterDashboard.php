<?php

namespace App\Livewire\Guest;

use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use App\Models\Line;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class WorkCenterDashboard extends Component
{
    public $now;
    public $shift;
    public $date;
    public array $areasData = [];
    public array $allLines = [];
    public array $selectedLines = [];
    public bool $realTime = true;
    public string $chartId;

    // Cache para evitar recálculos innecesarios
    private ?Carbon $lastRefresh = null;

    public function mount()
    {
        $this->chartId = 'dashboard-' . uniqid();
        $this->updateShiftAndDate();
        $this->loadLines();
        $this->fetchProductionRecords();
    }

    /**
     * Centraliza la obtención del turno y la fecha correcta usando el modelo Shift
     */
    protected function updateShiftAndDate(): void
    {
        $currentTime = Carbon::now();
        $this->now = $currentTime->format('Y-m-d H:i:s');
        $shiftInfo = Shift::getCurrentShiftInfo($currentTime);
        $this->shift = $shiftInfo->shift;
        $this->date = $shiftInfo->date->format('Y-m-d');
    }

    protected function loadLines()
    {
        $this->allLines = Line::query()
            ->select('lines.id', 'lines.name', 'areas.name as area_name')
            ->join('areas', 'lines.area_id', '=', 'areas.id')
            ->orderBy('areas.name')
            ->orderBy('lines.name')
            ->get()
            ->map(function ($line) {
                return [
                    'id' => $line->id,
                    'name' => $line->name,
                    'area' => $line->area_name,
                    'full_name' => $line->area_name . ' - ' . $line->name
                ];
            })
            ->toArray();
    }

    #[On('refresh-production-records')]
    public function refreshProductionRecords()
    {
        $now = Carbon::now();

        // Throttle: solo refrescar si han pasado al menos 5 segundos
        if ($this->lastRefresh && $this->lastRefresh->diffInSeconds($now) < 5) {
            return;
        }

        $this->lastRefresh = $now;
        $this->updateShiftAndDate();
        $this->fetchProductionRecords();
    }

    public function fetchProductionRecords()
    {
        if (!$this->shift) {
            $this->areasData = [];
            return;
        }

        $query = ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'areas.name AS area_name',
                'lines.id AS line_id',
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
            ->where('production_records.planned_date', $this->date)
            ->where('shifts.abbreviation', $this->shift->abbreviation);

        if (!empty($this->selectedLines)) {
            $query->whereIn('lines.id', $this->selectedLines);
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
            $areaLines = [];

            foreach ($lines as $lineName => $workCenters) {
                $lineWorkCenters = [];
                $lineColor = null;
                $lineId = null;

                foreach ($workCenters as $workName => $records) {
                    if ($lineColor === null) {
                        $lineColor = $records->first()->line_color;
                        $lineId = $records->first()->line_id;
                    }

                    $workCenterQuantityPlanned = (int) $records->sum('planned_quantity');
                    $workCenterQuantityUnplannedProduced = (int) $records->where('planned_quantity', 0)->sum('produced_quantity');
                    $workCenterQuantityProduced = (int) ($records->sum('produced_quantity') - $workCenterQuantityUnplannedProduced);
                    $totalProduced = (int) $records->sum('produced_quantity');

                    $percentage = $workCenterQuantityPlanned > 0
                        ? (int) round(($totalProduced / $workCenterQuantityPlanned) * 100)
                        : 0;

                    $lineWorkCenters[] = [
                        'id' => (int) $records->first()->work_center_id,
                        'name' => $workName,
                        'planned' => $workCenterQuantityPlanned,
                        'produced' => $workCenterQuantityProduced,
                        'unplanned' => $workCenterQuantityUnplannedProduced,
                        'total' => $totalProduced,
                        'percentage' => $percentage,
                    ];
                }

                if (!empty($lineWorkCenters)) {
                    $areaLines[] = [
                        'id' => $lineId,
                        'name' => $lineName,
                        'color' => $lineColor,
                        'workCenters' => $lineWorkCenters
                    ];
                }
            }

            if (!empty($areaLines)) {
                $this->areasData[] = [
                    'name' => $areaName,
                    'lines' => $areaLines
                ];
            }
        }
    }

    public function updatedSelectedLines()
    {
        $this->fetchProductionRecords();
    }

    public function render()
    {
        return view('livewire.guest.work-center-dashboard');
    }
}
