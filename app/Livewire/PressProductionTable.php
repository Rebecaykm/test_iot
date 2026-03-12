<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class PressProductionTable extends Component
{
    public $workCenter;
    public array $data = [];

    // Métricas del resumen
    public int $planTurno     = 0;
    public int $planActual    = 0;
    public int $totalProducido = 0;
    public int $diferencia    = 0;

    // Información de turno (para mostrar en la cabecera)
    public string $shiftName  = '';
    public string $plannedDate = '';
    public string $shiftStart = '';
    public string $shiftEnd   = '';
    public string $nowTime    = '';

    public bool $realTime = false;

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

        $this->shiftName   = $shift->name ?? $shift->abbreviation;
        $this->plannedDate = $now->toDateString();
        $this->shiftStart  = $timeRange->startDateTime->format('H:i');
        $this->shiftEnd    = $timeRange->endDateTime->format('H:i');
        $this->nowTime     = $now->format('H:i');

        // ── Plan Turno ────────────────────────────────────────────────────
        $plannedRecord = ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shift->id)
            ->where('work_centers.name', 'LIKE', $this->workCenter)
            ->orderBy('part_numbers.production_order', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('production_records.production_end', 'desc')
            ->sum('production_records.planned_quantity');

        $this->planTurno = (int) $plannedRecord;

        // ── Plan Actual ───────────────────────────────────────────────────
        $shiftStartDt = $timeRange->startDateTime;
        $shiftEndDt = $timeRange->endDateTime;
        $totalShiftMinutes = max(1, $shiftStartDt->diffInMinutes($shiftEndDt));
        $elapsedMinutes = min($shiftStartDt->diffInMinutes($now), $totalShiftMinutes);

        $this->planActual = (int) round(($this->planTurno / $totalShiftMinutes) * $elapsedMinutes);

        // ── Total Producido ───────────────────────────────────────────────

        $histories = History::getProductionHistory(
            $this->workCenter,
            $shiftStartDt,
            $now
        );

        $this->totalProducido = (int) $histories->sum('quantity');

        // ── Diferencia ────────────────────────────────────────────────────
        // Positivo = adelantado  |  Negativo = atrasado
        $this->diferencia = $this->totalProducido - $this->planActual;

        // ── Datos de la tabla inferior ────────────────────────────────────

        $records = ProductionRecord::query()
            ->select([
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift_name',
                'production_records.produced_quantity AS produced_quantity',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shift->id)
            ->where('work_centers.name', 'LIKE', $this->workCenter)
            ->where('statuses.id', 7)
            ->get();


        $this->data = $records
            ->groupBy('work_name')
            ->map(
                fn($wcGroup) =>
                $wcGroup->groupBy('planned_date')
                    ->map(
                        fn($dateGroup) =>
                        $dateGroup->groupBy('shift_name')
                            ->map(
                                fn($shiftGroup) =>
                                $shiftGroup->map(fn($record) => [
                                    'part_number'       => $record->part_number,
                                    'produced_quantity' => $record->produced_quantity,
                                ])->values()->toArray()
                            )->toArray()
                    )->toArray()
            )->toArray();

        dd($this->data);

        $this->dispatch('table-updated');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function resetMetrics(): void
    {
        $this->data          = [];
        $this->planTurno     = 0;
        $this->planActual    = 0;
        $this->totalProducido = 0;
        $this->diferencia    = 0;
        $this->shiftName     = '';
        $this->plannedDate   = '';
        $this->shiftStart    = '';
        $this->shiftEnd      = '';
        $this->nowTime       = '';
    }

    public function render()
    {
        return view('livewire.press-production-table');
    }
}
