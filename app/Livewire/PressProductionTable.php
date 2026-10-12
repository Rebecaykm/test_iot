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

        $planRecords = ProductionRecord::query()
            ->select([
                'production_records.part_number_id',
                'production_records.planned_quantity',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers',  'part_numbers.work_center_id',       '=', 'work_centers.id')
            ->join('shifts',        'production_records.shift_id',        '=', 'shifts.id')
            ->where('production_records.planned_date', $currentShiftInfo->date->toDateString())
            ->where('shifts.id', $shift->id)
            ->where('work_centers.name', 'LIKE', $this->workCenter)
            ->get();

        $planParts = PartNumber::query()
            ->whereIn('id', $planRecords->pluck('part_number_id')->unique())
            ->with(['customAttributes' => fn ($q) => $q->whereIn('key', ['mid', 'pieces_per_shot'])])
            ->get()
            ->keyBy('id');

        // Divisor por troquel: [part_number_id => suma de pieces_per_shot del troquel],
        // calculado SOLO con los parts del plan del turno (no todo el work center), para
        // no inflar el divisor con revisiones viejas que comparten 'mid'. Convierte
        // piezas -> golpes. Ver PartNumber::buildShotDivisors.
        $planDivisors = PartNumber::buildShotDivisors($planParts);

        // Plan del turno por troquel (MDI), en golpes.
        $dies = [];
        foreach ($planRecords as $record) {
            $part = $planParts->get($record->part_number_id);
            if (! $part) {
                continue;
            }

            $mid = trim((string) ($part->customAttributes->firstWhere('key', 'mid')->value ?? ''));
            $key = $mid !== '' ? $mid : "part:{$part->id}";

            $dies[$key] ??= ['shots' => 0.0, 'rates' => [], 'order' => PHP_INT_MAX];
            $dies[$key]['shots'] += $record->planned_quantity / ($planDivisors[$part->id] ?? 1);

            // production_rate es el estándar en golpes/hora (SPH) => SPM = SPH / 60.
            if ((float) $part->production_rate > 0) {
                $dies[$key]['rates'][] = (float) $part->production_rate / 60;
            }
            if (! is_null($part->production_order)) {
                $dies[$key]['order'] = min($dies[$key]['order'], (int) $part->production_order);
            }
        }

        $this->planTurno = (int) round(array_sum(array_column($dies, 'shots')));

        $shiftStartDt      = $timeRange->startDateTime;
        $shiftEndDt        = $timeRange->endDateTime;
        $totalShiftMinutes = max(1, $shiftStartDt->diffInMinutes($shiftEndDt));
        $elapsedMinutes    = min($shiftStartDt->diffInMinutes($now), $totalShiftMinutes);

        $this->planActual = min($this->planTurno, $this->expectedShots($dies, $elapsedMinutes, $totalShiftMinutes));

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

    /**
     * Golpes que el plan debería llevar a esta hora del turno.
     *
     * Los troqueles se recorren en su orden de producción, cada uno a su SPM estándar
     * (promedio de los SPH / 60 de sus parts): golpes esperados = SPM * minutos, hasta
     * agotar los golpes planeados del troquel y pasar al siguiente.
     *
     * Un troquel sin estándar (production_rate en 0) no tiene ritmo con qué
     * secuenciarse: sus golpes se reparten parejo en todo el turno.
     */
    private function expectedShots(array $dies, float $elapsedMinutes, float $totalShiftMinutes): int
    {
        uksort($dies, fn ($a, $b) => [$dies[$a]['order'], $a] <=> [$dies[$b]['order'], $b]);

        $expected       = 0.0;
        $remaining      = $elapsedMinutes;
        $withoutStandard = 0.0;

        foreach ($dies as $die) {
            if (empty($die['rates'])) {
                $withoutStandard += $die['shots'];
                continue;
            }

            if ($remaining <= 0 || $die['shots'] <= 0) {
                continue;
            }

            $spm      = array_sum($die['rates']) / count($die['rates']);
            $duration = $die['shots'] / $spm;

            $expected  += min($die['shots'], $spm * $remaining);
            $remaining -= min($duration, $remaining);
        }

        $expected += $withoutStandard * ($elapsedMinutes / $totalShiftMinutes);

        return (int) round($expected);
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
