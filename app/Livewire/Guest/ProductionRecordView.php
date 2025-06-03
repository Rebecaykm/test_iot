<?php

namespace App\Livewire\Guest;

use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Livewire\Attributes\On;
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

        if ($this->lastShiftId !== $this->currentShift?->id) {
            $this->previousShift = Shift::getPreviousShift($this->currentShift);
            $this->previousProductionRecords();
            $this->lastShiftId = $this->currentShift?->id;
        }

        $this->currentProductionRecord();
    }

    public function previousProductionRecords()
    {
        if (!$this->previousShift || !$this->currentShift) return;

        // Usar el método centralizado del modelo
        $previousShiftInfo = Shift::getPreviousShiftInfo($this->now);

        if (!$previousShiftInfo->timeRange) return;

        $previousProductionRecords = ProductionRecord::getProductionRecords(
            $this->workCenter->name,
            $previousShiftInfo->shift->id,
            $previousShiftInfo->timeRange->startDateTime
        );

        $this->previousData = $this->formatProductionData($previousProductionRecords);
    }

    public function currentProductionRecord()
    {
        if (!$this->currentShift) return;

        // Usar el método centralizado del modelo
        $currentShiftInfo = Shift::getCurrentShiftInfo($this->now);

        if (!$currentShiftInfo->timeRange) return;

        $currentProductionRecord = ProductionRecord::getProductionRecords(
            $this->workCenter->name,
            $currentShiftInfo->shift->id,
            $currentShiftInfo->timeRange->startDateTime
        );

        $this->currentData = $this->formatProductionData($currentProductionRecord);
    }

    protected function formatProductionData($records)
    {
        return $records->groupBy('planned_date')
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
                                'status_class' => $this->getStatusClass($record->status_name),
                            ];
                        });
                    });
            });
    }

    protected function getStatusClass($status)
    {
        $status = preg_replace('/\s+/', ' ', mb_strtolower(trim($status), 'UTF-8'));

        return match ($status) {
            'pendiente' => 'text-orange-700 bg-orange-100',  // Corregido a 'amber' (ámbar)
            'en progreso' => 'text-blue-700 bg-blue-100',   // Azul para operaciones en curso
            'completado' => 'text-green-700 bg-green-100',  // Verde para completado
            'detenido' => 'text-red-700 bg-red-100',        // Rojo para detenido/error
            default => 'text-gray-700 bg-gray-200'          // Gris por defecto
        };
    }

    public function render()
    {
        return view('livewire.guest.production-record-view');
    }
}
