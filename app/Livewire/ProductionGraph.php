<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductionGraph extends Component
{
    public string|null $chartId = null;
    public bool $hasData = false;
    public array $realBgColors = [];
    public array $realBorderColors = [];

    public array $labels = [];
    public array $plannedData = [];
    public array $producedData = [];
    public array $productionRate = [];
    public array $efficiency = [];
    public array $productionStart = [];

    public bool $realTime = false;
    public $workCenter;

    public $now;
    public $shift;

    private array $cachedCalculations = [];
    private ?Carbon $lastRefresh = null;

    public function mount($workCenter, $realTime): void
    {
        $this->chartId = Str::ulid();
        $this->workCenter = $workCenter;
        $this->realTime = $realTime === 'true' || $realTime === true;

        $this->refreshGraph();
    }

    #[On('refresh-graph')]
    public function refreshGraph()
    {
        $now = Carbon::now();

        // Permitir actualización si nunca se ha refrescado o si pasaron +9s
        if (!$this->lastRefresh || $this->lastRefresh->diffInSeconds($now) >= 9) {
            $this->now = $now;
            $this->shift = Shift::getShift($this->now);

            if (!$this->shift) {
                $this->hasData = false;
                return;
            }

            $this->lastRefresh = $now;
            $this->fetchGraphData();
        }
    }

    public function fetchGraphData(): void
    {
        $this->realBgColors = [];
        $this->realBorderColors = [];

        if (!$this->shift) {
            $this->hasData = false;
            return;
        }

        $productionRecords = ProductionRecord::getWorkCenterProductionRecord(
            $this->workCenter,
            $this->shift->id,
            $this->now
        );

        // Limpiar arrays antes de llenarlos
        $this->labels = [];
        $this->plannedData = [];
        $this->producedData = [];
        $this->productionRate = [];
        $this->efficiency = [];
        $this->productionStart = [];

        foreach ($productionRecords as $record) {
            $this->labels[] = $record->part_number;
            $this->plannedData[] = (int) $record->planned_quantity;
            $this->producedData[] = (int) $record->produced_quantity;
            $this->productionRate[] = (float) $record->production_rate;
            $this->efficiency[] = (float) ($record->efficiency ?? 100);

            // Asegurar que production_start sea válido
            $this->productionStart[] = $record->production_start ?
                Carbon::parse($record->production_start)->toISOString() :
                Carbon::now()->toISOString();

            if ($record->planned_quantity == 0) {
                // Naranja - Fondo con opacidad, borde sólido
                $this->realBgColors[] = 'rgba(255, 159, 64, 0.2)';
                $this->realBorderColors[] = 'rgb(255, 159, 64)';
            } else {
                // Verde - Fondo con opacidad, borde sólido
                $this->realBgColors[] = 'rgba(22, 163, 74, 0.2)';
                $this->realBorderColors[] = 'rgb(22, 163, 74)';
            }
        }

        $this->hasData = !empty($this->labels);

        // Limpiar cache cuando hay nuevos datos
        $this->cachedCalculations = [];
    }

    /**
     * Calcular el progreso esperado basado en tiempo ciclo y eficiencia
     */
    public function calculateExpectedProgress(int $index): int
    {
        if (!isset($this->plannedData[$index])) {
            return 0;
        }

        // Cache para evitar recálculos
        $cacheKey = "progress_{$index}_" . $this->now->timestamp;

        if (isset($this->cachedCalculations[$cacheKey])) {
            return $this->cachedCalculations[$cacheKey];
        }

        $plannedQuantity = $this->plannedData[$index] ?? 0;
        $productionRate = $this->productionRate[$index] ?? 0;
        $efficiency = $this->efficiency[$index] ?? 100;
        $productionStart = $this->productionStart[$index] ?? null;

        // Si no hay tasa de producción válida, retornar cantidad planificada
        if ($productionRate <= 0 || !$productionStart) {
            $this->cachedCalculations[$cacheKey] = $plannedQuantity;
            return $plannedQuantity;
        }

        try {
            $startTime = Carbon::parse($productionStart);
            $currentTime = Carbon::now();

            // Tiempo transcurrido en segundos
            $elapsedSeconds = abs($currentTime->diffInSeconds($startTime, false));

            // Aplicar eficiencia al tiempo ciclo
            $effectiveProductionRate = $productionRate * (max($efficiency, 1) / 100);

            // Calcular tiempo ciclo por pieza en segundos
            $cycleTimePerPiece = 3600 / $effectiveProductionRate; // 3600 segundos = 1 hora

            // Calcular cantidad esperada
            $expectedQuantity = intval($elapsedSeconds / $cycleTimePerPiece);

            // No puede exceder la cantidad planificada
            $result = min($expectedQuantity, $plannedQuantity);

            $this->cachedCalculations[$cacheKey] = $result;

            return $result;
        } catch (\Exception $e) {
            // En caso de error, retornar cantidad planificada
            $this->cachedCalculations[$cacheKey] = $plannedQuantity;
            return $plannedQuantity;
        }
    }

    /**
     * Obtener datos para JavaScript
     */
    public function getChartData(): array
    {
        if (!$this->hasData) {
            return [
                'labels' => [],
                'plannedData' => [],
                'producedData' => [],
                'planProgress' => [],
                'productionRate' => [],
                'efficiency' => [],
                'productionStart' => [],
            ];
        }

        $planProgress = [];

        for ($i = 0; $i < count($this->plannedData); $i++) {
            $planProgress[] = $this->calculateExpectedProgress($i);
        }

        return [
            'labels' => $this->labels,
            'plannedData' => $this->plannedData,
            'producedData' => $this->producedData,
            'planProgress' => $planProgress,
            'productionRate' => $this->productionRate,
            'efficiency' => $this->efficiency,
            'productionStart' => $this->productionStart,
            'realBgColors' => $this->realBgColors,
            'realBorderColors' => $this->realBorderColors,
        ];
    }

    public function render()
    {
        return view('livewire.production-graph');
    }
}
