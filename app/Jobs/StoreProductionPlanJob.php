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
            // Log::error("Part number not found: " . $this->part_number);
            return;
        }

        $shift = Shift::query()->where('abbreviation', $this->planned_shift)->first();

        if (!$shift) {
            // Log::error("Shift not found: " . $this->planned_shift);
            return;
        }

        $existingRecord = ProductionRecord::where([
            'part_number_id' => $partNumber->id,
            'planned_date' => $this->planned_date,
            'shift_id' => $shift->id,
            'shop_order_number' => $this->shop_order_number
        ])->first();

        if ($existingRecord !== null) {
            $existingRecord->update([
                'planned_quantity' => $this->planned_quantity
            ]);
            // Log::info("Se ha encontrado un registro de producción duplicado. Número de Orden: " . $this->shop_order_number .
            //     ", Part: " . $this->part_number .
            //     ", Date: " . $this->planned_date .
            //     ", Shift: " . $this->planned_shift);

            // return;
        } else {
            ProductionRecord::store($partNumber->id, intval($this->planned_quantity), $this->planned_date, $shift->id, $this->shop_order_number);
        }
    }
}
