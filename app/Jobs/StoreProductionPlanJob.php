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

        $accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();
        $inProgressStatus = Status::where('name', 'LIKE', 'En progreso')->first();

        // El recolector del PLC puede empezar a acumular producción bajo una fecha
        // (y turno) distinta a la que finalmente trae Infor para esa orden (arrancó
        // antes o después de la fecha real del plan, o en otro turno). Estos registros
        // "acumulador" no tienen orden ni plan, solo producción, y están en status
        // "No planeado" o "En progreso" (el PLC puede seguir escribiendo sobre su ID
        // en cualquiera de los dos). Se busca solo por número de parte, sin límite de
        // fecha, ya que pueden llevar varios días sin que Infor los alcance.
        $accumulatorStatusIds = array_filter([
            optional($accumulatorStatus)->id,
            optional($inProgressStatus)->id,
        ]);

        if (!empty($accumulatorStatusIds)) {
            $accumulatorCandidates = ProductionRecord::where('part_number_id', $partNumber->id)
                ->whereIn('status_id', $accumulatorStatusIds)
                ->where(function ($query) {
                    $query->whereNull('shop_order_number')->orWhere('shop_order_number', '');
                })
                ->where('planned_quantity', 0)
                ->where('produced_quantity', '>', 0)
                ->where('planned_date', '!=', $this->planned_date)
                ->get();

            if ($accumulatorCandidates->count() > 1) {
                Log::warning("Multiple accumulator records found for part number {$this->part_number} on dates other than {$this->planned_date} (IDs: " . $accumulatorCandidates->pluck('id')->implode(', ') . "). Skipping date reconciliation for shop order number {$this->shop_order_number}; needs manual review.");
            } elseif ($accumulatorCandidates->count() === 1) {
                $accumulator = $accumulatorCandidates->first();

                if ($existingRecord !== null && (int) $existingRecord->produced_quantity > 0) {
                    Log::warning("Accumulator record #{$accumulator->id} and a non-empty record #{$existingRecord->id} both found for part number {$this->part_number}, planned date {$this->planned_date}, shift {$this->planned_shift}. Skipping date reconciliation; needs manual review.");
                } else {
                    if ($existingRecord !== null) {
                        $existingRecord->delete();
                        $existingRecord = null;
                    }

                    $accumulatorWasInProgress = $inProgressStatus && (int) $accumulator->status_id === $inProgressStatus->id;
                    $producedQuantity = (int) $accumulator->produced_quantity;

                    if ($producedQuantity > $plannedQuantityInt) {
                        $excessQuantity = $producedQuantity - $plannedQuantityInt;
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

                        $accumulatorUpdate = [
                            'shop_order_number' => null,
                            'planned_quantity' => 0,
                            'produced_quantity' => $excessQuantity,
                        ];

                        // Si el acumulador estaba "En progreso", el PLC sigue produciendo
                        // ahora mismo bajo la fecha/turno reales, así que se corrigen para
                        // reflejarlo. Si estaba "No planeado", el restante no pertenece a
                        // esta orden y conserva su fecha/turno original (donde realmente
                        // se produjo).
                        if ($accumulatorWasInProgress) {
                            $accumulatorUpdate['planned_date'] = $this->planned_date;
                            $accumulatorUpdate['shift_id'] = $shift->id;
                        }

                        $accumulator->update($accumulatorUpdate);

                        $remainderLocation = $accumulatorWasInProgress
                            ? "moved to {$this->planned_date}/{$this->planned_shift}"
                            : "keeping its original date/shift";

                        Log::info("Accumulator record #{$accumulator->id} for part number {$this->part_number} covered Infor's planned quantity ({$plannedQuantityInt}) for shop order number {$this->shop_order_number} on {$this->planned_date}, shift {$this->planned_shift}. Completed order frozen in a new record; accumulator kept the remaining quantity ({$excessQuantity}), {$remainderLocation}.");
                    } else {
                        $accumulator->update([
                            'planned_date' => $this->planned_date,
                            'shift_id' => $shift->id,
                            'shop_order_number' => $this->shop_order_number,
                            'planned_quantity' => $plannedQuantityInt,
                        ]);

                        Log::info("Accumulator record #{$accumulator->id} for part number {$this->part_number} was misdated. Moved to {$this->planned_date}/{$this->planned_shift} and matched to shop order number {$this->shop_order_number} with planned quantity {$plannedQuantityInt}.");
                    }

                    return;
                }
            }
        }

        if ($existingRecord !== null) {
            $producedQuantity = (int) $existingRecord->produced_quantity;

            $isInProgress = $inProgressStatus && (int) $existingRecord->status_id === $inProgressStatus->id;

            // Mientras el registro sigue "En progreso", el recolector del PLC sigue
            // escribiendo producción sobre su ID. Por eso, en ese estado, en cuanto la
            // orden se cubre por completo (producido >= plan, no solo cuando se rebasa)
            // esa orden se congela en un registro nuevo y este registro se libera
            // (sin orden/plan) para seguir acumulando lo que seguirá llegando del PLC.
            // En cualquier otro estado no hay ese riesgo, así que solo se divide si
            // realmente se rebasa (producido > plan).
            $isOverflow = $isInProgress
                ? $producedQuantity >= $plannedQuantityInt
                : $producedQuantity > $plannedQuantityInt;

            if ($isOverflow) {
                $excessQuantity = $producedQuantity - $plannedQuantityInt;

                if ($isInProgress) {
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

                    Log::info("Production record #{$existingRecord->id} for part number {$this->part_number}, planned date {$this->planned_date}, shift {$this->planned_shift} was In Progress and covered Infor's planned quantity ({$plannedQuantityInt}) for shop order number {$this->shop_order_number}. Completed order frozen in a new record; existing record kept its ID with the remaining quantity ({$excessQuantity}) and no order/plan.");
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
