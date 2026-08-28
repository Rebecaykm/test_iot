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
    public $graphRoute;

    public $previousData;
    public $currentData;

    public ?int $lastShiftId = null;

    public function mount($workCenterId): void
    {
        $this->workCenterId = $workCenterId;
        $this->workCenter = WorkCenter::with('line.area')->findOrFail($workCenterId);

        $this->determineGraphRoute();

        $this->refreshTable();
    }

    protected function determineGraphRoute(): void
    {
        if ($this->workCenter->line && $this->workCenter->line->area) {
            $areaName = strtolower($this->workCenter->line->area->name);
            if ($areaName === 'estampado') {
                $this->graphRoute = route('press-production', $this->workCenter->name);
            } else {
                $this->graphRoute = route('production-dashboard', $this->workCenter->name);
            }
        } else {
            $this->graphRoute = route('production-dashboard', $this->workCenter->name);
        }
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

        $previousProductionRecords = ProductionRecord::getCachedProductionRecords(
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

        $currentProductionRecord = ProductionRecord::getCachedProductionRecords(
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
                        // Cada registro de producción es independiente: no se fusionan
                        // registros del mismo número de parte, para no perder su estatus individual.
                        return $shiftGroup->map(function ($record) {
                            $plannedQuantity = (int)$record->planned_quantity;
                            $producedQuantity = (int)$record->produced_quantity;
                            $statusStyle = $this->getStatusStyle($record->status_name);

                            return [
                                'order_number' => $record->order_number ?: '-',
                                'part_number' => $record->part_number,
                                'planned_quantity' => $plannedQuantity,
                                'produced_quantity' => $producedQuantity,
                                'difference' => $producedQuantity - $plannedQuantity,
                                'status' => $record->status_name,
                                'status_row_class' => $statusStyle['row'],
                                'status_dot_class' => $statusStyle['dot'],
                            ];
                        })->values();
                    });
            });
    }

    /**
     * Estilos por estatus: color de fondo para pintar todo el renglón
     * y color sólido para el punto/círculo de la leyenda.
     */
    public static function statusStyleMap(): array
    {
        return [
            'pendiente' => [
                'label' => 'Pendiente',
                'row' => 'bg-orange-50 dark:bg-orange-900/30 hover:bg-orange-100 dark:hover:bg-orange-900/50',
                'dot' => 'bg-orange-500',
            ],
            'en progreso' => [
                'label' => 'En Progreso',
                'row' => 'bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50',
                'dot' => 'bg-blue-500',
            ],
            'completado' => [
                'label' => 'Completado',
                'row' => 'bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/50',
                'dot' => 'bg-green-500',
            ],
            'detenido' => [
                'label' => 'Detenido',
                'row' => 'bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50',
                'dot' => 'bg-red-500',
            ],
        ];
    }

    protected function getStatusStyle($status): array
    {
        // Normalizar el texto: quitar espacios, acentos y convertir a minúsculas
        $normalizedStatus = trim(strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $status)));

        return static::statusStyleMap()[$normalizedStatus] ?? [
            'label' => $status,
            'row' => 'bg-gray-50 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700/60',
            'dot' => 'bg-gray-400',
        ];
    }

    public function render()
    {
        return view('livewire.guest.production-record-view');
    }
}
