<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Component;

class ProductionTable extends Component
{
    public $workCenter;
    public $interval;
    public $shift;
    public $startDate;
    public $endDate;
    public $data;

    public bool $realTime = false;

    public function mount($workCenter, $interval = '1 minute'): void
    {
        $this->workCenter = $workCenter;
        $this->interval = $interval;
        $this->refresTable();  // Llamamos para refrescar la tabla al inicio
    }

    // Método para refrescar los datos
    public function refresTable()
    {
        $now = Carbon::now();

        $this->shift = Shift::getCurrentShift($now);

        $this->startDate = $now->copy()->setTimeFromTimeString($this->shift->start_time);
        $this->endDate = $this->shift->abbreviation === 'N'
            ? $now->copy()->addDay()->setTimeFromTimeString($this->shift->end_time)
            : $now->copy()->setTimeFromTimeString($this->shift->end_time);

        $this->fetchChartData();  // Cargar los datos
    }

    // Método para obtener los datos agrupados
    public function fetchChartData(): void
    {
        // Obtener los registros
        $productionRecords = History::getWorkCenterProductionRecord($this->workCenter, $this->shift->id, $this->startDate, $this->endDate);

        // Agrupar los registros por WorkCenter, PlannedDate y Shift
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
