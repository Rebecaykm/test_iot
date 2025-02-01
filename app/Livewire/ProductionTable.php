<?php

namespace App\Livewire;

use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductionTable extends Component
{
    public $workCenter;
    public $shift;
    public $startDate;
    public $endDate;
    public $data;

    public function mount($workCenter): void
    {
        $this->workCenter = $workCenter;

        $this->refreshTable();
    }

    // Método para refrescar los datos
    public function refreshTable()
    {
        $now = Carbon::now();

        $this->shift = Shift::getShift($now);

        $this->fetchChartData();
    }

    // Método para obtener los datos agrupados
    public function fetchChartData(): void
    {
        $productionRecords = ProductionRecord::getWorkCenterProductionRecord($this->workCenter, $this->shift->id);

        $this->data = $productionRecords->groupBy('work_name')
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
                                        'difference' => $record->planned_quantity - $record->produced_quantity,
                                        'status_name' => $record->status_name
                                    ];
                                });
                            });
                    });
            });
    }

    public function render()
    {
        return view('livewire.production-table');
    }
}
