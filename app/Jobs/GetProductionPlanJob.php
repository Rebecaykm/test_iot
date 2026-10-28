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
            Log::warning('[GetProductionPlanJob] No se pudo determinar el turno actual; se omite la ejecución.');
            return;
        }

        $operatingDate = $currentShift->date->copy()->startOfDay();
        $monthStart = $operatingDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $operatingDate->copy()->endOfMonth()->startOfDay();

        $windowStart = ProductionRecord::applyHistoryFloor($this->from ? Carbon::parse($this->from) : Carbon::parse($monthStart))->toDateString();
        $windowEnd = ($this->to ? Carbon::parse($this->to) : $monthEnd->max($operatingDate->copy()->addWeek()))->toDateString();

        Log::info("[GetProductionPlanJob] Rango de plan: {$windowStart} - {$windowEnd}; turno operativo {$operatingDate->toDateString()} {$currentShift->shift->abbreviation}");

        $syncFromByWorkCenter = InforSyncWorkCenter::query()
            ->whereHas('setting', fn($query) => $query->where('environment', InforSyncSetting::ENVIRONMENT_LIVE))
            ->get(['work_center_id', 'sync_from'])
            ->mapWithKeys(fn(InforSyncWorkCenter $workCenter) => [$workCenter->work_center_id => $workCenter->sync_from->toDateString()]);

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

            StoreProductionPlanJob::dispatch($partNumber, $orders, $syncFrom, $operatingDate->toDateString(), $currentShift->shift->id);
        }
    }

    /**
     * Números de parte con acumulado "No planeado" en el mes operativo.
     */
    protected function partNumbersWithAccumulators(string $monthStart, string $monthEnd): array
    {
        $accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();

        if (!$accumulatorStatus) {
            return [];
        }

        return ProductionRecord::query()
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->where('production_records.status_id', $accumulatorStatus->id)
            ->where(fn($query) => $query->whereNull('production_records.shop_order_number')->orWhere('production_records.shop_order_number', ''))
            ->where('production_records.planned_quantity', 0)
            ->where('production_records.produced_quantity', '>', 0)
            ->whereBetween('production_records.planned_date', [$monthStart, $monthEnd])
            ->distinct()
            ->pluck('part_numbers.number')
            ->map(fn($number) => trim($number))
            ->all();
    }
}
