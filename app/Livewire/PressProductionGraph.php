<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class PressProductionGraph extends Component
{
    public string $chartId;
    public array $labels = [];
    public array $plannedData = [];
    public array $producedData = [];
    public array $differenceData = [];
    public $workCenter;

    public function mount($workCenter): void
    {
        $this->chartId = 'chart_' . Str::random(10);
        $this->workCenter = $workCenter;
        $this->refreshGraph();
    }

    #[On('refresh-graph')]
    public function refreshGraph(): void
    {
        $now = Carbon::now();
        $current = Shift::getCurrentShiftInfo($now);
        $previous = Shift::getPreviousShiftInfo($now);

        // Generar bloques separados para cada turno
        $previousBlocks = $this->generateTimeBlocks(
            $previous->timeRange->startDateTime,
            $previous->timeRange->endDateTime
        );

        $currentBlocks = $this->generateTimeBlocks(
            $current->timeRange->startDateTime,
            $current->timeRange->endDateTime
        );

        // Combinar labels en orden cronológico
        $this->labels = array_merge(array_keys($previousBlocks), array_keys($currentBlocks));

        // Divisor por troquel POR TURNO: cada turno tiene su propio conjunto de
        // parts programados, así que el divisor se calcula solo con esos parts
        // (no todo el work center). Ver PartNumber::buildShotDivisorsForPartIds.
        $previousDivisors = $this->divisorsForShift($previous);
        $currentDivisors  = $this->divisorsForShift($current);

        // Calcular plan para cada turno por separado
        $previousPlanned = $this->calculatePlannedForShift($previous, $previousBlocks, $previousDivisors);
        $currentPlanned = $this->calculatePlannedForShift($current, $currentBlocks, $currentDivisors);
        $this->plannedData = array_merge($previousPlanned, $currentPlanned);

        // Calcular producción real para cada turno por separado
        $previousProduced = $this->calculateActualForShift(
            $previous->timeRange->startDateTime,
            $previous->timeRange->endDateTime,
            $previousBlocks,
            $now,
            $previousDivisors
        );

        $currentProduced = $this->calculateActualForShift(
            $current->timeRange->startDateTime,
            $current->timeRange->endDateTime,
            $currentBlocks,
            $now,
            $currentDivisors
        );

        $this->producedData = array_merge($previousProduced, $currentProduced);

        // Calcular diferencias
        $this->calculateDifference();

        $this->dispatch('update-chart', [
            'labels' => $this->labels,
            'planned' => $this->plannedData,
            'produced' => $this->producedData,
            'difference' => $this->differenceData,
        ]);
    }

    /**
     * Construye el divisor por troquel ([part_number_id => divisor]) usando solo
     * los part numbers programados en ese turno, para no inflar el divisor con
     * revisiones viejas que comparten 'mid'.
     */
    private function divisorsForShift($shiftInfo): array
    {
        $partIds = ProductionRecord::getCachedProductionRecords(
            $this->workCenter,
            $shiftInfo->shift->id,
            $shiftInfo->date
        )->pluck('part_number_id')->all();

        return PartNumber::buildShotDivisorsForPartIds($partIds);
    }

    private function generateTimeBlocks(Carbon $start, Carbon $end): array
    {
        $blocks = [];
        $currentBlock = $start->copy()->floorHour();
        if ($currentBlock->hour % 2 !== 0) $currentBlock->subHour();

        while ($currentBlock < $end) {
            $startRange = $currentBlock->copy();
            $endRange = $currentBlock->copy()->addHours(2);
            $label = $startRange->format('d-m H:i') . ' a ' . $endRange->format('H:i');
            $blocks[$label] = 0;
            $currentBlock->addHours(2);
        }
        return $blocks;
    }

    /**
     * Calcula el plan acumulado para un turno específico
     */
    private function calculatePlannedForShift($shiftInfo, $timeBlocks, array $divisors): array
    {
        // Obtener el total planeado para este turno
        $records = ProductionRecord::getCachedProductionRecords(
            $this->workCenter,
            $shiftInfo->shift->id,
            $shiftInfo->date
        );

        // Plan en golpes: piezas planeadas por part / divisor de su troquel.
        $totalPlanned = $records->sum(function ($record) use ($divisors) {
            $divisor = $divisors[$record->part_number_id] ?? 1;

            return $record->planned_quantity / $divisor;
        });

        // Calcular cantidad de bloques
        $hoursInShift = $shiftInfo->timeRange->startDateTime->diffInHours($shiftInfo->timeRange->endDateTime);
        $blocksCount = max(1, $hoursInShift / 2);

        // Cantidad promedio por bloque
        $avgPerBlock = $totalPlanned / $blocksCount;

        // Crear el acumulado para este turno
        $accumulated = 0;
        $result = [];

        foreach ($timeBlocks as $label => $val) {
            $accumulated += $avgPerBlock;
            $result[] = round($accumulated);
        }

        return $result;
    }

    /**
     * Calcula la producción real acumulada para un turno específico
     */
    private function calculateActualForShift(Carbon $start, Carbon $end, $timeBlocks, $now, array $divisors): array
    {
        // Obtener el historial de producción para este turno (sólo quantity + created_at)
        $histories = History::getProducedTimeline($this->workCenter, $start, $end);

        // Agrupar por bloques de tiempo
        $grouped = $histories->groupBy(function ($item) {
            $dt = $item->created_at->copy()->floorHour();
            if ($dt->hour % 2 !== 0) $dt->subHour();
            return $dt->format('d-m H:i') . ' a ' . $dt->addHours(2)->format('H:i');
        });

        // Calcular acumulado para este turno
        $accumulated = 0;
        $result = [];

        foreach ($timeBlocks as $label => $val) {
            $labelParts = explode(' a ', $label);
            $labelStart = Carbon::createFromFormat('d-m H:i', $labelParts[0]);

            // Si el bloque es futuro, poner null
            if ($labelStart > $now) {
                $result[] = null;
                continue;
            }

            // Sumar el bloque en golpes: cada pieza / divisor del troquel de su part.
            $blockSum = isset($grouped[$label])
                ? $grouped[$label]->sum(fn ($item) => $item->quantity / ($divisors[$item->part_number_id] ?? 1))
                : 0;

            $accumulated += $blockSum;
            $result[] = round($accumulated);
        }

        return $result;
    }

    private function calculateDifference(): void
    {
        $this->differenceData = [];
        foreach ($this->plannedData as $index => $planned) {
            $produced = $this->producedData[$index];
            if ($produced === null) {
                $this->differenceData[] = 0 - $planned;
            } else {
                $this->differenceData[] = $produced - $planned;
            }
        }
    }

    public function render()
    {
        return view('livewire.press-production-graph');
    }
}
