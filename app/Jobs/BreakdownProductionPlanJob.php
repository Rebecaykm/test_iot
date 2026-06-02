<?php

namespace App\Jobs;

use App\Models\ProductionRecord;
use App\Models\ProductionSequence;
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
                'production_records.id AS productionRecordId',
                'part_numbers.standard_pack_quantity AS standardPackQuantity',
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

        foreach ($productionPlans as $plan) {

            if (ProductionSequence::where('production_record_id', $plan->productionRecordId)->exists()) {
                continue;
            }

            $plannedQty = (int) $plan->plannedQuantity;
            $stdPackQty = (int) $plan->standardPackQuantity;

            if ($stdPackQty <= 0) {
                $stdPackQty = $plannedQty;
            }

            $remainingQty = $plannedQty;
            $currentSequence = 1;

            $sequencesToInsert = [];

            while ($remainingQty > 0) {
                $qtyForThisSequence = min($stdPackQty, $remainingQty);

                $sequencesToInsert[] = [
                    'production_record_id' => $plan->productionRecordId,
                    'sequence_number'      => $currentSequence,
                    'quantity'             => $qtyForThisSequence,
                    'is_processed'         => 0,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];

                $remainingQty -= $qtyForThisSequence;
                $currentSequence++;
            }

            if (!empty($sequencesToInsert)) {
                ProductionSequence::insert($sequencesToInsert);
            }
        }
    }
}
