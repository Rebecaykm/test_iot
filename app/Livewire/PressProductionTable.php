<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class PressProductionTable extends Component
{
    public $workCenter;
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
        $this->workCenter = $workCenter;
        $this->realTime   = $realTime;
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

        // Divisor por troquel: [part_number_id => suma de pieces_per_shot del troquel],
        // calculado SOLO con los parts del turno (no todo el work center), para no
        // inflar el divisor con revisiones viejas que comparten 'mid'. Convierte
        // piezas -> golpes. Ver PartNumber::buildShotDivisorsForPartIds.
        $divisors = PartNumber::buildShotDivisorsForPartIds(
            $planRecords->pluck('part_number_id')->all()
        );

        $this->planTurno = (int) round(
            $planRecords->sum(function ($record) use ($divisors) {
                $divisor = $divisors[$record->part_number_id] ?? 1;
                return $record->planned_quantity / $divisor;
            })
        );

        $shiftStartDt      = $timeRange->startDateTime;
        $shiftEndDt        = $timeRange->endDateTime;
        $totalShiftMinutes = max(1, $shiftStartDt->diffInMinutes($shiftEndDt));
        $elapsedMinutes    = min($shiftStartDt->diffInMinutes($now), $totalShiftMinutes);

        $this->planActual = (int) round(
            ($this->planTurno / $totalShiftMinutes) * $elapsedMinutes
        );

        // Total producido en golpes: piezas por part / divisor de su troquel.
        $producedByPart = History::getProducedQuantityByPart(
            $this->workCenter,
            $shiftStartDt,
            $now
        );

        $totalShots = 0.0;
        foreach ($producedByPart as $partId => $pieces) {
            $totalShots += $pieces / ($divisors[$partId] ?? 1);
        }
        $this->totalProducido = (int) round($totalShots);

        $this->diferencia = $this->totalProducido - $this->planActual;

        $this->data = ProductionRecord::query()
            ->select([
                'part_numbers.number AS part_number',
                'production_records.produced_quantity',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers',  'part_numbers.work_center_id',       '=', 'work_centers.id')
            ->join('shifts',        'production_records.shift_id',        '=', 'shifts.id')
            ->join('statuses',      'production_records.status_id',       '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shift->id)
            ->where('work_centers.name', 'LIKE', $this->workCenter)
            ->where('statuses.id', 7)
            ->orderBy('part_numbers.production_order')
            ->get()
            ->map(fn($r) => [
                'part_number'       => $r->part_number,
                'produced_quantity' => (int) $r->produced_quantity,
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
