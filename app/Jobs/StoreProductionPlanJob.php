<?php

namespace App\Jobs;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Status;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
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

        // Un registro con una orden DISTINTA a la que se está procesando no es "el
        // mismo" registro para efectos de este job: puede haber varias órdenes de
        // Infor para el mismo material/fecha/turno, y cada una necesita su propio
        // registro. Solo cuenta como match si está sin orden (disponible) o si ya es
        // exactamente esta misma orden (de una corrida anterior).
        $existingRecord = ProductionRecord::where([
            'part_number_id' => $partNumber->id,
            'planned_date' => $this->planned_date,
            'shift_id' => $shift->id,
        ])
            ->where(function ($query) {
                $query->whereNull('shop_order_number')
                    ->orWhere('shop_order_number', '')
                    ->orWhere('shop_order_number', $this->shop_order_number);
            })
            ->first();

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
                Log::warning("Varios acumuladores para la parte {$this->part_number} (IDs: " . $accumulatorCandidates->pluck('id')->implode(', ') . "). Se omite conciliación de la orden {$this->shop_order_number}; revisar manualmente.");
            } elseif ($accumulatorCandidates->count() === 1) {
                $accumulator = $accumulatorCandidates->first();

                // El registro en la fecha exacta ya tiene producción propia (no solo la
                // del acumulador): no se puede fusionar sin perder datos.
                $existingRecordHasOwnProduction = $existingRecord !== null && (int) $existingRecord->produced_quantity > 0;

                // Ya trae orden y/o plan propios (p. ej. quedó Pendiente de una corrida
                // anterior): no es un placeholder vacío, conserva su ID y se le fusiona
                // la producción del acumulador.
                $existingRecordHasOwnOrderOrPlan = $existingRecord !== null && (
                    !empty($existingRecord->shop_order_number) || (int) $existingRecord->planned_quantity > 0
                );

                $existingRecordHasLabels = $existingRecord !== null
                    && DB::table('production_labels')->where('production_record_id', $existingRecord->id)->exists();

                if ($existingRecordHasOwnProduction) {
                    Log::warning("Acumulador #{$accumulator->id} y registro #{$existingRecord->id} (parte {$this->part_number}, {$this->planned_date} turno {$this->planned_shift}) tienen producción propia. Se omite conciliación; revisar manualmente.");
                } elseif ($existingRecordHasOwnOrderOrPlan) {
                    $accumulatorProduced = (int) $accumulator->produced_quantity;
                    $targetPlan = (int) $existingRecord->planned_quantity;
                    $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();

                    if ($accumulatorProduced > $targetPlan) {
                        $excessQuantity = $accumulatorProduced - $targetPlan;

                        // Se cubrió el plan por completo, el resto se va al acumulador:
                        // el registro destino queda Completado.
                        $existingRecord->update([
                            'produced_quantity' => $targetPlan,
                            'status_id' => $completedStatus ? $completedStatus->id : $existingRecord->status_id,
                        ]);

                        $accumulator->update([
                            'planned_date' => $this->planned_date,
                            'shift_id' => $shift->id,
                            'produced_quantity' => $excessQuantity,
                        ]);

                        Log::info("Registro #{$existingRecord->id} (orden {$existingRecord->shop_order_number}) completó su plan ({$targetPlan}) con el acumulador #{$accumulator->id} y quedó Completado. El acumulador conservó el restante ({$excessQuantity}) en {$this->planned_date}/{$this->planned_shift}.");
                    } else {
                        $existingRecordUpdate = ['produced_quantity' => $accumulatorProduced];

                        // Solo se marca Completado si con esto se cubrió el plan completo;
                        // si quedó por debajo, sigue con su status actual.
                        if ($completedStatus && $accumulatorProduced >= $targetPlan) {
                            $existingRecordUpdate['status_id'] = $completedStatus->id;
                        }

                        $existingRecord->update($existingRecordUpdate);

                        if (DB::table('production_labels')->where('production_record_id', $accumulator->id)->exists()) {
                            $accumulator->update(['produced_quantity' => 0]);
                            Log::info("Registro #{$existingRecord->id} (orden {$existingRecord->shop_order_number}) recibió {$accumulatorProduced} piezas del acumulador #{$accumulator->id}. El acumulador tenía etiquetas, se dejó en 0 en vez de borrarse.");
                        } else {
                            $accumulator->delete();
                            Log::info("Registro #{$existingRecord->id} (orden {$existingRecord->shop_order_number}) recibió {$accumulatorProduced} piezas del acumulador #{$accumulator->id}, que se eliminó.");
                        }
                    }

                    return;
                } elseif ($existingRecordHasLabels) {
                    Log::warning("Registro #{$existingRecord->id} (parte {$this->part_number}, {$this->planned_date} turno {$this->planned_shift}) está vacío pero tiene etiquetas de producción; no se puede conciliar. Revisar manualmente.");
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
                            ? "se movió a {$this->planned_date}/{$this->planned_shift}"
                            : "conservó su fecha/turno original";

                        Log::info("Acumulador #{$accumulator->id} (parte {$this->part_number}) cubrió el plan ({$plannedQuantityInt}) de la orden {$this->shop_order_number}; se creó un registro Completado. El restante ({$excessQuantity}) {$remainderLocation}.");
                    } else {
                        $accumulatorUpdate = [
                            'planned_date' => $this->planned_date,
                            'shift_id' => $shift->id,
                            'shop_order_number' => $this->shop_order_number,
                            'planned_quantity' => $plannedQuantityInt,
                        ];

                        if ($producedQuantity >= $plannedQuantityInt) {
                            // Cubrió el plan completo: queda Completado.
                            $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();
                            if ($completedStatus) {
                                $accumulatorUpdate['status_id'] = $completedStatus->id;
                            }
                        } elseif (!$accumulatorWasInProgress) {
                            // No alcanzó el plan y no estaba "En progreso" (o sea, estaba
                            // "No planeado"): ya no va a seguir recibiendo producción, se
                            // marca Detenido. Si estaba "En progreso" se deja igual, sigue
                            // produciendo.
                            $stoppedStatus = Status::where('name', 'LIKE', 'Detenid%')->first();
                            if ($stoppedStatus) {
                                $accumulatorUpdate['status_id'] = $stoppedStatus->id;
                            }
                        }

                        $accumulator->update($accumulatorUpdate);

                        Log::info("Acumulador #{$accumulator->id} (parte {$this->part_number}) tenía la fecha equivocada. Se movió a {$this->planned_date}/{$this->planned_shift} con la orden {$this->shop_order_number} y plan {$plannedQuantityInt} (producido {$producedQuantity}).");
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

                    Log::info("Registro #{$existingRecord->id} (parte {$this->part_number}, {$this->planned_date} turno {$this->planned_shift}) en progreso cubrió el plan ({$plannedQuantityInt}) de la orden {$this->shop_order_number}; se creó un registro Completado. El restante ({$excessQuantity}) se quedó en este registro, sin orden ni plan.");
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

                    Log::info("Registro (parte {$this->part_number}, {$this->planned_date} turno {$this->planned_shift}) produjo {$producedQuantity}, más que el plan ({$plannedQuantityInt}) de la orden {$this->shop_order_number}. Se limitó al plan y el restante ({$excessQuantity}) pasó a un registro nuevo sin planear.");
                }
            } elseif ((int) $existingRecord->planned_quantity !== $plannedQuantityInt || $existingRecord->shop_order_number !== $this->shop_order_number) {
                $existingRecord->update([
                    'shop_order_number' => $this->shop_order_number,
                    'planned_quantity' => $plannedQuantityInt,
                ]);
                Log::info("Registro actualizado: parte {$this->part_number}, orden {$this->shop_order_number}, {$this->planned_date} turno {$this->planned_shift}, plan {$this->planned_quantity}.");
            }
        } else {
            ProductionRecord::store($partNumber->id, $plannedQuantityInt, $this->planned_date, $shift->id, $this->shop_order_number);
        }
    }
}
