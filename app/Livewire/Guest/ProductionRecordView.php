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
                        return $shiftGroup->groupBy(function ($record) {
                            return $record->part_number . '|' . $record->planned_quantity;
                        })->map(function ($partGroup, $groupKey) {
                            [$partNumber, $plannedQuantity] = explode('|', $groupKey);
                            $totalProduced = $partGroup->sum('produced_quantity');
                            $firstRecord = $partGroup->first();

                            return [
                                'part_number' => $partNumber,
                                'planned_quantity' => (int)$plannedQuantity,
                                'produced_quantity' => $totalProduced,
                                'difference' => $totalProduced - (int)$plannedQuantity,
                                'status' => $firstRecord->status_name,
                                'status_class' => $this->getStatusClass($firstRecord->status_name),
                                'group_count' => $partGroup->count(), // Opcional: para saber cuántos registros se agruparon
                            ];
                        })->values();
                    });
            });
    }

    protected function getStatusClass($status)
    {
        // Normalizar el texto: quitar espacios, acentos y convertir a minúsculas
        $normalizedStatus = trim(strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $status)));

        return match ($normalizedStatus) {
            'pendiente' => 'text-orange-800 bg-orange-100 dark:text-orange-800 dark:bg-orange-900/50',
            'en progreso' => 'text-blue-800 bg-blue-50 dark:text-blue-300 dark:bg-blue-900/50',
            'completado' => 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/50',
            'detenido' => 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/50',
            default => 'text-gray-700 bg-gray-200 dark:text-gray-300 dark:bg-gray-600'
        };
    }

    public function render()
    {
        return view('livewire.guest.production-record-view');
    }
}
