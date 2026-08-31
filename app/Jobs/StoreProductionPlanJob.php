<?php

namespace App\Jobs;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Status;
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
            $producedQuantity = (int) $existingRecord->produced_quantity;

            if ($producedQuantity > $plannedQuantityInt) {
                $excessQuantity = $producedQuantity - $plannedQuantityInt;
                $inProgressStatus = Status::where('name', 'LIKE', 'En progreso')->first();

                if ($inProgressStatus && (int) $existingRecord->status_id === $inProgressStatus->id) {
                    // El recolector del PLC escribe la producción usando el ID de este
                    // registro. No se puede "congelar" ese ID como completado: debe
                    // seguir siendo el mismo registro el que reciba lo que sigue
                    // llegando, así que la orden completada se congela en uno nuevo.
                    $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();

                    ProductionRecord::create([
                        'part_number_id' => $partNumber->id,
                        'planned_date' => $this->planned_date,
                        'shift_id' => $shift->id,
                        'planned_quantity' => $plannedQuantityInt,
                        'produced_quantity' => $plannedQuantityInt,
                        'shop_order_number' => $this->shop_order_number,
                        'status_id' => $completedStatus->id,
                        'synced_to_infor' => false,
                    ]);

                    $existingRecord->update([
                        'shop_order_number' => null,
                        'planned_quantity' => 0,
                        'produced_quantity' => $excessQuantity,
                    ]);

                    Log::info("Production record #{$existingRecord->id} for part number {$this->part_number}, planned date {$this->planned_date}, shift {$this->planned_shift} was In Progress and exceeded Infor's planned quantity ({$plannedQuantityInt}) for shop order number {$this->shop_order_number}. Completed order frozen in a new record; existing record kept its ID with the remaining quantity ({$excessQuantity}) and no order/plan.");
                } else {
                    $existingRecord->update([
                        'shop_order_number' => $this->shop_order_number,
                        'planned_quantity' => $plannedQuantityInt,
                        'produced_quantity' => $plannedQuantityInt,
                    ]);

                    $overflowStatus = Status::where('name', 'LIKE', 'No planeado')->first();

                    ProductionRecord::create([
                        'part_number_id' => $partNumber->id,
                        'planned_date' => $this->planned_date,
                        'shift_id' => $shift->id,
                        'planned_quantity' => 0,
                        'produced_quantity' => $excessQuantity,
                        'shop_order_number' => null,
                        'status_id' => $overflowStatus->id,
                        'synced_to_infor' => false,
                    ]);

                    Log::info("Production record for part number {$this->part_number}, planned date {$this->planned_date}, shift {$this->planned_shift} had produced quantity ({$producedQuantity}) exceeding Infor's planned quantity ({$plannedQuantityInt}) for shop order number {$this->shop_order_number}. Capped record to planned quantity and moved the excess ({$excessQuantity}) to a new unplanned record.");
                }
            } elseif ((int) $existingRecord->planned_quantity !== $plannedQuantityInt || $existingRecord->shop_order_number !== $this->shop_order_number) {
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
