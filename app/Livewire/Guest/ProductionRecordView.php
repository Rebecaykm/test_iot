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
            $this->previousShift = $this->findPreviousShift($this->currentShift);
            $this->previousProductionRecords();
            $this->lastShiftId = $this->currentShift?->id;
        }

        $this->currentProductionRecord();
    }

    /**
     * Función mejorada para encontrar el turno anterior
     */
    private function findPreviousShift($currentShift)
    {
        if (!$currentShift) return null;

        // Si el turno actual es diurno (D), el anterior es nocturno (N)
        if ($currentShift->abbreviation === 'D') {
            return Shift::where('abbreviation', 'N')->first();
        }

        // Si el turno actual es nocturno (N), el anterior es diurno (D)
        if ($currentShift->abbreviation === 'N') {
            return Shift::where('abbreviation', 'D')->first();
        }

        return null;
    }

    public function previousProductionRecords()
    {
        if (!$this->previousShift || !$this->currentShift) return;

        // Calcular la fecha correcta para el turno anterior
        $previousDate = $this->calculatePreviousShiftDate();

        $previousTimeRange = Shift::getShiftDateTimeRange($this->previousShift, $previousDate);

        $previousProductionRecords = ProductionRecord::getProductionRecords(
            $this->workCenter->name,
            $this->previousShift->id,
            $previousTimeRange->startDateTime
        );

        $this->previousData = $this->formatProductionData($previousProductionRecords);
    }

    /**
     * Calcular la fecha correcta para el turno anterior
     */
    private function calculatePreviousShiftDate()
    {
        $now = $this->now->copy();

        // Si el turno actual es diurno (08:00-20:00)
        if ($this->currentShift->abbreviation === 'D') {
            // El turno anterior es nocturno del día anterior (20:00 del día anterior - 08:00 del día actual)
            return $now->subDay();
        }

        // Si el turno actual es nocturno (20:00-08:00 del siguiente día)
        if ($this->currentShift->abbreviation === 'N') {
            // Verificar si estamos en la primera parte (20:00-23:59) o segunda parte (00:00-08:00) del turno nocturno
            $currentTime = $now->format('H:i');

            if ($currentTime >= '20:00') {
                // Estamos en la primera parte del turno nocturno (20:00-23:59)
                // El turno anterior es diurno del mismo día (08:00-20:00)
                return $now;
            } else {
                // Estamos en la segunda parte del turno nocturno (00:00-08:00)
                // El turno anterior es diurno del día anterior (08:00-20:00 del día anterior)
                return $now->subDay();
            }
        }

        return $now;
    }

    /**
     * Calcular la fecha correcta para el turno actual
     */
    private function calculateCurrentShiftDate()
    {
        $now = $this->now->copy();

        // Si el turno actual es diurno (08:00-20:00)
        if ($this->currentShift->abbreviation === 'D') {
            // Usar la fecha actual
            return $now;
        }

        // Si el turno actual es nocturno (20:00-08:00 del siguiente día)
        if ($this->currentShift->abbreviation === 'N') {
            // Verificar si estamos en la primera parte (20:00-23:59) o segunda parte (00:00-08:00) del turno nocturno
            $currentTime = $now->format('H:i');

            if ($currentTime >= '20:00') {
                // Estamos en la primera parte del turno nocturno (20:00-23:59)
                // Usar la fecha actual (el turno comenzó hoy)
                return $now;
            } else {
                // Estamos en la segunda parte del turno nocturno (00:00-08:00)
                // El turno comenzó el día anterior (restar un día para obtener la fecha de inicio)
                return $now->subDay();
            }
        }

        return $now;
    }

    public function currentProductionRecord()
    {
        if (!$this->currentShift) return;

        // Calcular la fecha correcta para el turno actual
        $currentDate = $this->calculateCurrentShiftDate();

        $currentTimeRange = Shift::getShiftDateTimeRange($this->currentShift, $currentDate);
        $currentProductionRecord = ProductionRecord::getProductionRecords(
            $this->workCenter->name,
            $this->currentShift->id,
            $currentTimeRange->startDateTime
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
            'pendiente' => 'text-red-700 bg-red-100',
            'en progreso' => 'text-orenage-700 bg-orenage-100',
            'completado' => 'text-green-700 bg-green-100',
            default => 'text-gray-700 bg-gray-200'
        };
    }

    public function render()
    {
        return view('livewire.guest.production-record-view');
    }
}
