<?php

namespace App\Jobs;

use App\Models\FSO;
use App\Models\InforSyncSetting;
use App\Models\InforSyncWorkCenter;
use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
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
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::today();

        $windowStart = ProductionRecord::applyHistoryFloor($this->from ? Carbon::parse($this->from) : $today->copy()->subWeek())->toDateString();
        $windowEnd = ($this->to ? Carbon::parse($this->to) : $today->copy()->addWeek())->toDateString();

        Log::info("[GetProductionPlanJob] Rango de plan: {$windowStart} - {$windowEnd}");

        $syncFromByWorkCenter = InforSyncWorkCenter::query()
            ->whereHas('setting', fn ($query) => $query->where('environment', InforSyncSetting::ENVIRONMENT_LIVE))
            ->get(['work_center_id', 'sync_from'])
            ->mapWithKeys(fn (InforSyncWorkCenter $workCenter) => [$workCenter->work_center_id => $workCenter->sync_from->toDateString()]);

        // Si un número de parte está duplicado, manda la estación del id más reciente.
        $workCenterByPartNumber = PartNumber::query()
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->orderBy('part_numbers.id')
            ->get(['part_numbers.number', 'part_numbers.work_center_id'])
            ->mapWithKeys(fn (PartNumber $partNumber) => [trim($partNumber->number) => $partNumber->work_center_id]);

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

        // D antes que N, para llenar en el orden real de los turnos.
        $shiftPosition = Shift::query()->orderBy('start_time')->pluck('abbreviation')->flip();

        // Un job por número de parte con sus órdenes en orden de fecha y turno: el
        // acumulador solo llena hacia adelante, así que el orden importa.
        foreach ($productionPlans->groupBy(fn ($productionPlan) => trim($productionPlan->PART_NUMBER)) as $partNumber => $plans) {
            $syncFrom = $syncFromByWorkCenter->get($workCenterByPartNumber->get($partNumber));
            $partStart = $syncFrom !== null && $syncFrom > $windowStart ? $syncFrom : $windowStart;

            $orders = $plans
                ->filter(fn ($productionPlan) => $productionPlan->PLANNED_DATE >= $partStart)
                ->sortBy(fn ($productionPlan) => $productionPlan->PLANNED_DATE . '|' . ($shiftPosition[$productionPlan->PLANNED_SHIFT] ?? PHP_INT_MAX))
                ->map(fn ($productionPlan) => [
                    'shop_order_number' => $productionPlan->shop_order_number,
                    'planned_quantity' => $productionPlan->planned_quantity,
                    'planned_date' => $productionPlan->PLANNED_DATE,
                    'planned_shift' => $productionPlan->PLANNED_SHIFT,
                ])
                ->values()
                ->all();

            if (empty($orders)) {
                continue;
            }

            StoreProductionPlanJob::dispatch($partNumber, $orders, $syncFrom, $windowEnd);
        }
    }
}
