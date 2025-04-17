<?php

namespace App\Livewire\Guest;

use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Livewire\Component;

class ProductionRecordView extends Component
{
    public $now;
    public $previousShift;
    public $currentShift;
    public $workCenterId;
    public $workCenter;

    public $previousData;
    public $currentData;

    protected $lastShiftId = null;

    public function mount($workCenterId): void
    {
        $this->workCenterId = $workCenterId;
        $this->workCenter = WorkCenter::findOrFail($workCenterId);
        $this->refreshTable();
    }

    #[On('refresh')]
    public function refreshTable()
    {
        $this->now = Carbon::now();
        $this->currentShift = Shift::getShift($this->now);

        // Solo actualizar datos del turno anterior si cambió el turno actual
        if ($this->lastShiftId !== $this->currentShift->id) {
            $this->previousShift = Shift::findPreviousShift($this->currentShift);
            $this->previousProductionRecords();
            $this->lastShiftId = $this->currentShift->id;
        }

        // Siempre actualizar datos del turno actual
        $this->currentProductionRecord();
    }

    public function previousProductionRecords()
    {
        if (!$this->previousShift) return;

        $previousTimeRange = Shift::getShiftDateTimeRange($this->previousShift, $this->now);

        $previousProductionRecords = ProductionRecord::getProductionRecords(
            $this->workCenter->name,
            $this->previousShift->id,
            $previousTimeRange->startDateTime
        );

        $this->previousData = $this->formatProductionData($previousProductionRecords);
    }

    public function currentProductionRecord()
    {
        if (!$this->currentShift) return;

        $currentTimeRange = Shift::getShiftDateTimeRange($this->currentShift, $this->now);

        $currentProductionRecord = ProductionRecord::getProductionRecords(
            $this->workCenter->name,
            $this->currentShift->id,
            $currentTimeRange->startDateTime
        );

        $this->currentData = $this->formatProductionData($currentProductionRecord);
    }

    // Método helper para formatear los datos de producción
    protected function formatProductionData($records)
    {
        return $records->groupBy('work_name')
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
                                        'difference' => $record->produced_quantity - $record->planned_quantity,
                                        'status' => $record->status_name,
                                    ];
                                });
                            });
                    });
            });
    }

    public function render()
    {
        return view('livewire.guest.production-record-view');
    }
}
