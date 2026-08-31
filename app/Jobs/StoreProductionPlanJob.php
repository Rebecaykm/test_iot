<?php

namespace App\Jobs;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class StoreProductionPlanJob implements ShouldQueue
{
    use Queueable;

    protected $shop_order_number;
    protected $part_number;
    protected $planned_quantity;
    protected $planned_date;
    protected $planned_shift;

    /**
     * Create a new job instance.
     */
    public function __construct($shop_order_number, $part_number, $planned_quantity, $planned_date, $planned_shift)
    {
        $this->shop_order_number = $shop_order_number;
        $this->part_number = $part_number;
        $this->planned_quantity = $planned_quantity;
        $this->planned_date = $planned_date;
        $this->planned_shift = $planned_shift;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $partNumber = PartNumber::query()->where('number', $this->part_number)->first();

        if (!$partNumber) {
            return;
        }

        $shift = Shift::query()->where('abbreviation', $this->planned_shift)->first();

        if (!$shift) {
            return;
        }

        $plannedQuantityInt = intval($this->planned_quantity);

        $existingRecord = ProductionRecord::where([
            'part_number_id' => $partNumber->id,
            'planned_date' => $this->planned_date,
            'shift_id' => $shift->id,
        ])->first();

        if ($existingRecord !== null) {
            if ((int) $existingRecord->planned_quantity !== $plannedQuantityInt || $existingRecord->shop_order_number !== $this->shop_order_number) {
                $existingRecord->update([
                    'shop_order_number' => $this->shop_order_number,
                    'planned_quantity' => $plannedQuantityInt,
                ]);
                Log::info("Production record updated for part number {$this->part_number}, shop order number {$this->shop_order_number}, planned date {$this->planned_date}, shift {$this->planned_shift}, and planned quantity {$this->planned_quantity}.");
            }
        } else {
            ProductionRecord::store($partNumber->id, $plannedQuantityInt, $this->planned_date, $shift->id, $this->shop_order_number);
        }
    }
}
