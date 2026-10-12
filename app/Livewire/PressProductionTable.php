<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class PressProductionTable extends Component
{
    public $workCenter;
    public ?int $workCenterId = null;
    public array $data = [];

    public int $planTurno      = 0;
    public int $planActual     = 0;
    public int $totalProducido = 0;
    public int $diferencia     = 0;

    public string $shiftName    = '';
    public string $plannedDate  = '';
    public string $shiftStart   = '';
    public string $shiftEnd     = '';
    public string $nowTime      = '';

    public bool $realTime    = false;
    public bool $shiftActive = false;

    public function mount($workCenter, $realTime = false): void
    {
        $this->workCenter   = $workCenter;
        $this->workCenterId = WorkCenter::where('name', $workCenter)->value('id');
        $this->realTime     = $realTime;
        $this->refreshTable();
    }

    #[On('refresh-table')]
    public function refreshTable(): void
    {
        $now              = Carbon::now();
        $currentShiftInfo = Shift::getCurrentShiftInfo($now);

        if (! $currentShiftInfo->shift) {
            $this->resetMetrics();
            return;
        }

        $shift     = $currentShiftInfo->shift;
        $timeRange = $currentShiftInfo->timeRange;

        $this->shiftActive = true;
        $this->shiftName   = $shift->name ?? $shift->abbreviation;
        $this->plannedDate = $now->toDateString();
        $this->shiftStart  = $timeRange->startDateTime->format('H:i');
        $this->shiftEnd    = $timeRange->endDateTime->format('H:i');
        $this->nowTime     = $now->format('H:i');

        // Plan del turno por troquel (MDI), en golpes. Mismo cálculo que la gráfica.
        $dies = ProductionRecord::getPressShiftPlanByDie($this->workCenter, $shift->id, $currentShiftInfo->date);

        $this->planTurno = (int) round(array_sum(array_column($dies, 'shots')));

        $shiftStartDt      = $timeRange->startDateTime;
        $shiftEndDt        = $timeRange->endDateTime;
        $totalShiftMinutes = max(1, $shiftStartDt->diffInMinutes($shiftEndDt));
        $elapsedMinutes    = min($shiftStartDt->diffInMinutes($now), $totalShiftMinutes);

        // Plan Actual: golpes que el plan debería llevar a esta hora, según el SPM
        // estándar de cada troquel. Ver ProductionRecord::expectedPressShots.
        $this->planActual = min(
            $this->planTurno,
            (int) round(ProductionRecord::expectedPressShots($dies, $elapsedMinutes, $totalShiftMinutes))
        );

        // Total producido en golpes: piezas de histories de TODOS los parts de la prensa
        // en el turno / divisor de su troquel. El divisor se calcula con los parts que
        // realmente se produjeron (no solo los del plan): un part fuera del plan del
        // turno se quedaba sin divisor y sus piezas se contaban como golpes.
        $producedByPart = History::getProducedQuantityByPart(
            $this->workCenter,
            $shiftStartDt,
            $now
        );

        $producedDivisors = PartNumber::buildShotDivisorsForPartIds(array_keys($producedByPart));

        $totalShots = 0.0;
        foreach ($producedByPart as $partId => $pieces) {
            $totalShots += $pieces / ($producedDivisors[$partId] ?? 1);
        }
        $this->totalProducido = (int) round($totalShots);

        $this->diferencia = $this->totalProducido - $this->planActual;

        // Lo que se está produciendo ahora: parts con orden en estatus "En progreso"
        // (id 7), sin importar el día ni el turno en que se planearon, agrupados por
        // troquel (MDI, atributo 'mid'). La cantidad NO es la de la orden: son los
        // golpes que histories lleva registrados en el turno actual.
        $inProgressPartIds = ProductionRecord::query()
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers',  'part_numbers.work_center_id',       '=', 'work_centers.id')
            ->where('work_centers.name', 'LIKE', $this->workCenter)
            ->where('production_records.status_id', 7)
            ->distinct()
            ->pluck('part_numbers.id');

        $inProgressParts = PartNumber::query()
            ->whereIn('id', $inProgressPartIds)
            ->with(['customAttributes' => fn ($q) => $q->whereIn('key', ['mid', 'pieces_per_shot'])])
            ->orderBy('production_order')
            ->orderBy('number') // desempate estable cuando dos partes comparten orden
            ->get();

        // Divisor por troquel calculado solo con los parts en progreso:
        // golpes = piezas del part / suma de pieces_per_shot del troquel.
        $inProgressDivisors = PartNumber::buildShotDivisors($inProgressParts);

        $this->data = $inProgressParts
            ->groupBy(function ($part) {
                $mid = trim((string) ($part->customAttributes->firstWhere('key', 'mid')->value ?? ''));

                // Sin MDI registrado se muestra el propio número de parte.
                return $mid !== '' ? $mid : $part->number;
            })
            ->map(fn ($parts, $mdi) => [
                'mdi'   => (string) $mdi,
                'shots' => (int) round($parts->sum(
                    fn ($part) => ($producedByPart[$part->id] ?? 0) / ($inProgressDivisors[$part->id] ?? 1)
                )),
            ])
            ->values()
            ->toArray();

        $this->dispatch('table-updated');
    }

    private function resetMetrics(): void
    {
        $this->shiftActive    = false;
        $this->data           = [];
        $this->planTurno      = 0;
        $this->planActual     = 0;
        $this->totalProducido = 0;
        $this->diferencia     = 0;
        $this->shiftName      = '';
        $this->plannedDate    = '';
        $this->shiftStart     = '';
        $this->shiftEnd       = '';
        $this->nowTime        = '';
    }

    public function render()
    {
        return view('livewire.press-production-table');
    }
}
