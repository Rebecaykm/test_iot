<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ProductionRecord;

class ProductionRecords extends Component
{
    public $groupedByWorkCenter;

    // Este método solo se ejecuta al inicio
    public function mount()
    {
        $this->getProductionRecords();
    }

    // Este método se encargará de traer los registros de producción
    public function getProductionRecords()
    {
        $productionRecords = ProductionRecord::join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
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

        // Agrupar los registros por WorkCenter, PlannedDate y Shift
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
                                        'difference' => $record->planned_quantity - $record->produced_quantity
                                    ];
                                });
                            });
                    });
            });
    }

    // Este método será llamado por el `wire:poll`
    public function pollUpdate()
    {
        $this->getProductionRecords();
    }

    public function render()
    {
        return view('livewire.production-records');
    }
}
