<?php

namespace App\Jobs;

use App\Models\FSO;
use App\Models\PartNumber;
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
     * Fecha a partir de la cual se reconstruyen los acumulados hacia atrás.
     * El job nunca revisa órdenes previas a esta fecha, aunque la semana
     * calculada normalmente empiece antes. Más adelante, cuando se habilite
     * ver semanas anteriores, este tope se podrá mover o quitar.
     */
    private const HISTORY_FLOOR_DATE = '2026-09-21';

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::today();
        $historyFloor = Carbon::parse(self::HISTORY_FLOOR_DATE);

        $startDate = $today->copy()->subWeek()->startOfWeek();
        if ($startDate->lt($historyFloor)) {
            $startDate = $historyFloor->copy();
        }
        $startDate = $startDate->format('Ymd');

        $endDate = $today->copy()->addWeek()->endOfWeek()->format('Ymd');


        $partNumbers = PartNumber::query()
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('lines', 'work_centers.line_id', '=', 'lines.id')
            ->pluck('part_numbers.number')
            ->toArray();

        $productionPlans = FSO::query()
            ->select(
                'SORD as shop_order_number',
                DB::raw('TRIM(SPROD) AS part_number'),
                'SQREQ as planned_quantity',
                DB::raw("VARCHAR(SUBSTR(SRDTE, 1, 4) || '-' || SUBSTR(SRDTE, 5, 2) || '-' || SUBSTR(SRDTE, 7, 2)) AS planned_date"),
                DB::raw("SUBSTR(TRIM(SOCNO), LENGTH(TRIM(SOCNO)), 1) AS planned_shift"),
            )
            ->whereIn(DB::raw('TRIM(SPROD)'), $partNumbers)
            ->whereBetween('SRDTE', [$startDate, $endDate])
            ->orderBy('SRDTE')
            ->orderBy('SORD')
            ->get();

        foreach ($productionPlans as $productionPlan) {
            StoreProductionPlanJob::dispatch(
                $productionPlan->shop_order_number,
                $productionPlan->PART_NUMBER,
                $productionPlan->planned_quantity,
                $productionPlan->PLANNED_DATE,
                $productionPlan->PLANNED_SHIFT
            );
        }
    }
}
