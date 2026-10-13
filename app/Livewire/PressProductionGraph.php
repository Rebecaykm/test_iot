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

/**
 * Gráfica de press-production: plan vs real acumulados, en GOLPES, por bloques de
 * 2 horas del turno anterior y del turno en curso.
 *
 *  - Plan: el plan total del turno (en golpes) repartido parejo entre sus bloques y
 *    acumulado; el último bloque muestra el plan completo del turno.
 *  - Real: golpes registrados en histories hasta el final del bloque.
 *  - Bloques futuros: sin real; todo el plan del bloque se pinta como faltante
 *    (rojo), que es a lo que se debe llegar.
 */
class PressProductionGraph extends Component
{
    private const BLOCK_HOURS = 2;

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

        $this->labels = [];
        $this->plannedData = [];
        $this->producedData = [];
        $this->differenceData = [];

        // Turno anterior y turno en curso, cada uno con su propio plan y acumulado
        foreach ([Shift::getPreviousShiftInfo($now), Shift::getCurrentShiftInfo($now)] as $shiftInfo) {
            if (! $shiftInfo->shift || ! $shiftInfo->timeRange) {
                continue;
            }

            $this->appendShift($shiftInfo, $now);
        }

        $this->dispatch('update-chart', [
            'labels' => $this->labels,
            'planned' => $this->plannedData,
            'produced' => $this->producedData,
            'difference' => $this->differenceData,
        ]);
    }

    private function appendShift($shiftInfo, Carbon $now): void
    {
        $start = $shiftInfo->timeRange->startDateTime;
        $end = $shiftInfo->timeRange->endDateTime;

        $blocks = $this->generateTimeBlocks($start, $end);
        $shotsByBlock = $this->producedShotsByBlock($start, $end);

        // Plan total del turno en golpes (el "Plan Turno" de la tabla), repartido
        // parejo entre los bloques del turno.
        $dies = ProductionRecord::getPressShiftPlanByDie($this->workCenter, $shiftInfo->shift->id, $shiftInfo->date);
        $planPerBlock = array_sum(array_column($dies, 'shots')) / max(1, count($blocks));

        $plannedAccumulated = 0.0;
        $accumulated = 0.0;

        foreach ($blocks as $label => $blockStart) {
            $plannedAccumulated += $planPerBlock;
            $planned = (int) round($plannedAccumulated);

            $this->labels[] = $label;
            $this->plannedData[] = $planned;

            if ($blockStart->gt($now)) {
                // Aún no llega: sin real; el plan completo del bloque es lo que falta por llegar
                $this->producedData[] = null;
                $this->differenceData[] = 0 - $planned;
                continue;
            }

            $accumulated += $shotsByBlock[$label] ?? 0;
            $produced = (int) round($accumulated);

            $this->producedData[] = $produced;
            $this->differenceData[] = $produced - $planned;
        }
    }

    /**
     * Bloques de 2 horas del turno: [etiqueta => inicio del bloque].
     */
    private function generateTimeBlocks(Carbon $start, Carbon $end): array
    {
        $blocks = [];
        $currentBlock = $this->blockStart($start);

        while ($currentBlock < $end) {
            $blocks[$this->blockLabel($currentBlock)] = $currentBlock->copy();
            $currentBlock->addHours(self::BLOCK_HOURS);
        }

        return $blocks;
    }

    private function blockStart(Carbon $time): Carbon
    {
        $block = $time->copy()->floorHour();
        if ($block->hour % self::BLOCK_HOURS !== 0) {
            $block->subHour();
        }

        return $block;
    }

    private function blockLabel(Carbon $blockStart): string
    {
        return $blockStart->format('d-m H:i') . ' a ' . $blockStart->copy()->addHours(self::BLOCK_HOURS)->format('H:i');
    }

    /**
     * Golpes reales del turno por bloque: [etiqueta => golpes].
     */
    private function producedShotsByBlock(Carbon $start, Carbon $end): array
    {
        // Historial de producción del turno (sólo part, quantity y created_at)
        $histories = History::getProducedTimeline($this->workCenter, $start, $end);

        // Divisor piezas -> golpes con los parts que realmente se produjeron en el
        // turno (igual que PressProductionTable). Con el divisor del plan, un part
        // producido fuera del plan del turno se quedaba sin divisor y sus piezas se
        // contaban como golpes.
        $divisors = PartNumber::buildShotDivisorsForPartIds(
            $histories->pluck('part_number_id')->unique()->all()
        );

        $shots = [];
        foreach ($histories as $item) {
            $label = $this->blockLabel($this->blockStart($item->created_at));
            $shots[$label] = ($shots[$label] ?? 0) + $item->quantity / ($divisors[$item->part_number_id] ?? 1);
        }

        return $shots;
    }

    public function render()
    {
        return view('livewire.press-production-graph');
    }
}
