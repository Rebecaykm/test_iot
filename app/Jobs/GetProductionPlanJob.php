<?php

namespace App\Jobs;

use App\Models\FSO;
use App\Models\InforSyncSetting;
use App\Models\InforSyncWorkCenter;
use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetProductionPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?string $from = null,
        protected ?string $to = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $currentShift = Shift::getCurrentShiftInfo(Carbon::now());

        if ($currentShift->shift === null) {
            Log::warning('[Plan] No se pudo determinar el turno actual; se omite la ejecución.');
            return;
        }

        $operatingDate = $currentShift->date->copy()->startOfDay();
        $monthStart = $operatingDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $operatingDate->copy()->endOfMonth()->startOfDay();

        $windowStart = ProductionRecord::applyHistoryFloor($this->from ? Carbon::parse($this->from) : Carbon::parse($monthStart))->toDateString();
        $windowEnd = ($this->to ? Carbon::parse($this->to) : $monthEnd->max($operatingDate->copy()->addWeek()))->toDateString();

        $this->cancelObsoletePartRecords();

        $liveWorkCenters = InforSyncWorkCenter::query()
            ->whereHas('setting', fn($query) => $query->where('environment', InforSyncSetting::ENVIRONMENT_LIVE))
            ->get(['id', 'work_center_id', 'sync_from', 'split_at']);

        $syncFromByWorkCenter = $liveWorkCenters
            ->mapWithKeys(fn(InforSyncWorkCenter $workCenter) => [$workCenter->work_center_id => $workCenter->sync_from->toDateString()]);

        // Estaciones cuyo envío ya arrancó (sync_from desde su primer turno) y
        // todavía no dividen su acumulado: corte = inicio del primer turno.
        $firstShift = Shift::query()->orderBy('start_time')->first();
        $pendingSplits = $liveWorkCenters
            ->whereNull('split_at')
            ->mapWithKeys(fn(InforSyncWorkCenter $workCenter) => [
                $workCenter->work_center_id => $workCenter->sync_from->copy()->setTimeFromTimeString($firstShift->start_time),
            ])
            ->filter(fn(Carbon $cut) => $cut->lte(Carbon::now()));

        $workCenterByPartNumber = PartNumber::query()
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->where(fn($query) => $query->where('part_numbers.is_obsolete', false))
            ->orderBy('part_numbers.id')
            ->get(['part_numbers.number', 'part_numbers.work_center_id'])
            ->mapWithKeys(fn(PartNumber $partNumber) => [trim($partNumber->number) => $partNumber->work_center_id]);

        $productionPlans = FSO::query()
            ->select(
                'SORD as shop_order_number',
                DB::raw('TRIM(SPROD) AS part_number'),
                'SQREQ as planned_quantity',
                DB::raw("VARCHAR(SUBSTR(SRDTE, 1, 4) || '-' || SUBSTR(SRDTE, 5, 2) || '-' || SUBSTR(SRDTE, 7, 2)) AS planned_date"),
                DB::raw("SUBSTR(TRIM(SOCNO), LENGTH(TRIM(SOCNO)), 1) AS planned_shift"),
            )
            ->whereIn(DB::raw('TRIM(SPROD)'), $workCenterByPartNumber->keys()->all())
            ->whereBetween('SRDTE', [Carbon::parse($windowStart)->format('Ymd'), Carbon::parse($windowEnd)->format('Ymd')])
            ->orderBy('SRDTE')
            ->orderBy('SORD')
            ->get();

        $shiftPosition = Shift::query()->orderBy('start_time')->pluck('abbreviation')->flip();

        $ordersByPartNumber = $productionPlans->groupBy(fn($productionPlan) => trim($productionPlan->PART_NUMBER));

        // PHP convierte a int las llaves numéricas ("5724204010"); se regresan a
        // string para que SQL Server compare contra nvarchar.
        $partNumbers = $ordersByPartNumber->keys()
            ->merge($this->partNumbersWithAccumulators($monthStart, $monthEnd->toDateString()))
            ->merge($workCenterByPartNumber->filter(fn($workCenterId) => $pendingSplits->has($workCenterId))->keys())
            ->map(fn($partNumber) => (string) $partNumber)
            ->filter(fn($partNumber) => $workCenterByPartNumber->has($partNumber))
            ->unique();

        foreach ($partNumbers as $partNumber) {
            $syncFrom = $syncFromByWorkCenter->get($workCenterByPartNumber->get($partNumber));
            $partStart = $syncFrom !== null && $syncFrom > $windowStart ? $syncFrom : $windowStart;

            $orders = $ordersByPartNumber->get($partNumber, collect())
                ->filter(fn($productionPlan) => $productionPlan->PLANNED_DATE >= $partStart)
                ->sortBy(fn($productionPlan) => $productionPlan->PLANNED_DATE . '|' . ($shiftPosition[$productionPlan->PLANNED_SHIFT] ?? PHP_INT_MAX))
                ->map(fn($productionPlan) => [
                    'shop_order_number' => $productionPlan->shop_order_number,
                    'planned_quantity' => $productionPlan->planned_quantity,
                    'planned_date' => $productionPlan->PLANNED_DATE,
                    'planned_shift' => $productionPlan->PLANNED_SHIFT,
                ])
                ->values()
                ->all();

            $splitFrom = $pendingSplits->get($workCenterByPartNumber->get($partNumber))?->toDateTimeString();

            StoreProductionPlanJob::dispatch($partNumber, $orders, $syncFrom, $operatingDate->toDateString(), $currentShift->shift->id, $splitFrom);
        }

        // shortcut: se marca al despachar, no al terminar cada job; si uno falla, esa parte no se divide (revisar el log).
        if ($pendingSplits->isNotEmpty()) {
            InforSyncWorkCenter::query()
                ->whereIn('id', $liveWorkCenters->whereIn('work_center_id', $pendingSplits->keys())->pluck('id'))
                ->update(['split_at' => Carbon::now()]);

            Log::info('[Plan] Arranque de envío: se divide el acumulado de las estaciones #' . $pendingSplits->keys()->implode(', #'));
        }

        Log::info("[Plan] Rango {$windowStart} a {$windowEnd} · turno {$operatingDate->toDateString()} {$currentShift->shift->abbreviation} · {$productionPlans->count()} órdenes de FSO · {$partNumbers->count()} partes en cola");
    }

    /**
     * Los registros de partes obsoletas (sin ningún id vigente con ese número)
     * pasan a "Cancelado" para que no se envíen a Infor. Se quedan en su fecha
     * y turno, sin juntarse, para revisar después por qué hubo producción. No se
     * tocan los "En progreso" (el recolector puede estar escribiendo) ni los ya
     * sincronizados. Los ids viejos de partes duplicadas no entran: su número
     * todavía tiene un id vigente.
     */
    protected function cancelObsoletePartRecords(): void
    {
        $cancelledStatus = Status::where('name', 'LIKE', 'Cancelado')->first();
        $inProgressStatus = Status::where('name', 'LIKE', 'En progreso')->first();

        if (!$cancelledStatus) {
            return;
        }

        $recordIds = ProductionRecord::query()
            ->whereIn('part_number_id', fn($query) => $query->select('obsolete.id')
                ->from('part_numbers as obsolete')
                ->where('obsolete.is_obsolete', true)
                ->whereNotExists(fn($query) => $query->from('part_numbers as active')
                    ->whereColumn('active.number', 'obsolete.number')
                    ->where('active.is_obsolete', false)))
            ->where('synced_to_infor', false)
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->where(fn($query) => $query->whereNull('status_id')
                ->orWhereNotIn('status_id', array_filter([$cancelledStatus->id, $inProgressStatus?->id])))
            ->orderBy('id')
            ->pluck('id');

        // SQL Server acepta máximo 2100 parámetros por consulta.
        foreach ($recordIds->chunk(1000) as $chunk) {
            ProductionRecord::query()->whereIn('id', $chunk->all())->update(['status_id' => $cancelledStatus->id]);
        }

        if ($recordIds->isNotEmpty()) {
            $rest = $recordIds->count() - 20;

            Log::info("[Plan] {$recordIds->count()} registros de partes obsoletas pasaron a Cancelado: #" . $recordIds->take(20)->implode(', #') . ($rest > 0 ? " y {$rest} más" : ''));
        }
    }

    /**
     * Números de parte con acumulado "No planeado" o "No verificado" en el mes
     * operativo, incluidos los que quedaron en cero (se descartan al consolidar).
     */
    protected function partNumbersWithAccumulators(string $monthStart, string $monthEnd): array
    {
        $accumulatorStatusIds = Status::whereIn('name', ['No planeado', 'No verificado'])->pluck('id');

        if ($accumulatorStatusIds->isEmpty()) {
            return [];
        }

        return ProductionRecord::query()
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->whereIn('production_records.status_id', $accumulatorStatusIds)
            ->where(fn($query) => $query->whereNull('production_records.shop_order_number')->orWhere('production_records.shop_order_number', ''))
            ->where('production_records.planned_quantity', 0)
            ->whereBetween('production_records.planned_date', [$monthStart, $monthEnd])
            ->distinct()
            ->pluck('part_numbers.number')
            ->map(fn($number) => trim($number))
            ->all();
    }
}
