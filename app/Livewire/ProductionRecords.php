<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;

class ProductionRecords extends Component
{
    public $line;
    public $now;
    public $shift;

    public $groupedByWorkCenter;

    public function mount($line): void
    {
        $this->line = $line;

        $this->refreshProductionRecords();
    }

    public function refreshProductionRecords()
    {
        $this->now = Carbon::now();
        // $this->now = Carbon::parse('2025-02-07 12:00:00');

        $this->shift = Shift::getShift($this->now);

        $this->fetchProductionRecords();
    }

    public function fetchProductionRecords()
    {
        $yesterday = $this->now->copy()->subDay()->toDateString();
        $today = $this->now->copy()->toDateString();

        $productionRecords = ProductionRecord::join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('statuses.id', 7)
            ->where('lines.name', $this->line)
            ->whereBetween('production_records.planned_date', [$yesterday, $today])
            ->orderBy('work_centers.name', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->select([
                'work_centers.name AS work_center_name',
                'part_numbers.number AS part_number',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift_name',
            ])
            ->get();

        $this->groupedByWorkCenter = $productionRecords->groupBy('work_center_name')
            ->map(function ($workCenterGroup) {
                return $workCenterGroup->groupBy('planned_date')
                    ->map(function ($dateGroup) {
                        return $dateGroup->groupBy('shift_name')
                            ->map(function ($shiftGroup) {
                                return $shiftGroup->map(function ($record) {
                                    return [
                                        'part_number' => $record->part_number,
                                        'planned_quantity' => $record->planned_quantity,
                                        'produced_quantity' => $record->produced_quantity,
                                        'difference' => $record->produced_quantity - $record->planned_quantity
                                    ];
                                });
                            });
                    });
            });
    }

    public function render()
    {
        return view('livewire.production-records');
    }
}
