<?php

namespace App\Jobs;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BreakdownProductionPlanJob implements ShouldQueue
{
    use Queueable;

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
        $productionPlans = ProductionRecord::query()
            ->select([
                'production_records.shop_order_number AS orderNumber',
                'part_numbers.id AS partNumberId',
                'part_numbers.standard_pack_quantity AS standardPackQuantity',
                'production_records.planned_date AS plannedDate',
                'shifts.id AS shiftId',
                'production_records.planned_quantity AS plannedQuantity'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->whereDate('production_records.planned_date', now())
            ->whereNotNull('production_records.shop_order_number')
            ->where([
                ['statuses.name', '=', 'Pendiente'],
                ['production_records.produced_quantity', '=', 0],
                ['production_records.shop_order_number', '<>', '']
            ])
            ->orderByRaw('production_records.planned_date ASC, shifts.abbreviation ASC, production_records.shop_order_number ASC')
            ->toBase()
            ->get();

        foreach ($productionPlans as $productionPlan) {
            dd($productionPlan);
        }
    }
}
