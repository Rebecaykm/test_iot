<?php

namespace App\Jobs;

use App\Models\PartNumber;
use App\Models\ProductionOrderHistory;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Status;
use Carbon\Carbon;
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

        // Si la fecha ya pasó, se usa la orden que estaba vigente ESE día (no la
        // actual, que pudo cambiar). Si es hoy o futuro, se usa la vigente ahora.
        $plannedDateStr = Carbon::parse($this->planned_date)->toDateString();
        $shouldRefreshOrder = $plannedDateStr >= Carbon::today()->toDateString();

        $productionOrderForRecord = $shouldRefreshOrder
            ? $partNumber->production_order
            : (ProductionOrderHistory::orderOnDate($partNumber->id, $plannedDateStr) ?? $partNumber->production_order);

        // Primero el registro que ya tiene esta orden; si no hay, uno libre (sin
        // orden). Así un acumulador vacío en el mismo slot no se toma por la orden.
        $slotQuery = fn () => ProductionRecord::where([
            'part_number_id' => $partNumber->id,
            'planned_date' => $this->planned_date,
            'shift_id' => $shift->id,
        ]);

        $existingRecord = $slotQuery()
            ->where('shop_order_number', $this->shop_order_number)
            ->orderBy('id')
            ->first()
            ?? $slotQuery()
                ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
                ->orderBy('id')
                ->first();

        $accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();
        $inProgressStatus = Status::where('name', 'LIKE', 'En progreso')->first();
        $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();

        // Orden ya Completada: no se le vuelve a pasar producción de acumuladores.
        $orderAlreadyCompleted = $existingRecord !== null
            && (string) $existingRecord->shop_order_number === (string) $this->shop_order_number
            && $completedStatus
            && (int) $existingRecord->status_id === $completedStatus->id;

        // "Acumulador": producción del PLC sin orden/plan todavía (status "No
        // planeado" o "En progreso"). Se busca por parte, sin tope hacia adelante, y
        // se excluye todo lo anterior a HISTORY_FLOOR_DATE. Solo se excluye por ID
        // (no por fecha) para que se pueda repartir entre turnos del mismo día en el
        // orden real en que Infor entrega las órdenes (D, luego N, luego el D
        // siguiente...), en vez de saltarse el turno N y caer solo en días futuros.
        $accumulatorStatusIds = array_filter([
            optional($accumulatorStatus)->id,
            optional($inProgressStatus)->id,
        ]);

        if (!empty($accumulatorStatusIds) && !$orderAlreadyCompleted) {
            $accumulatorCandidates = ProductionRecord::where('part_number_id', $partNumber->id)
                ->whereIn('status_id', $accumulatorStatusIds)
                ->where(function ($query) {
                    $query->whereNull('shop_order_number')->orWhere('shop_order_number', '');
                })
                ->where('planned_quantity', 0)
                ->where('produced_quantity', '>', 0)
                ->when($existingRecord, fn ($query) => $query->where('id', '!=', $existingRecord->id))
                ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
                ->where(function ($query) {
                    $query->whereNull('production_start')
                        ->orWhere('production_start', '>=', ProductionRecord::HISTORY_FLOOR_DATE);
                })
                ->get();

            if ($accumulatorCandidates->count() > 1) {
                Log::warning("Varios acumuladores para la parte {$this->part_number} (IDs: " . $accumulatorCandidates->pluck('id')->implode(', ') . "). Se omite conciliación de la orden {$this->shop_order_number}; revisar manualmente.");
            } elseif ($accumulatorCandidates->count() === 1) {
                $accumulator = $accumulatorCandidates->first();

                // No se fusiona si el registro ya tiene producción propia (se perdería).
                $existingRecordHasOwnProduction = $existingRecord !== null && (int) $existingRecord->produced_quantity > 0;

                // Ya tiene orden/plan de una corrida anterior: se le fusiona el acumulador.
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

                    // "En progreso" no se toca (el PLC puede seguir escribiendo sobre él).
                    $existingRecordIsInProgress = $inProgressStatus && (int) $existingRecord->status_id === $inProgressStatus->id;

                    if ($accumulatorProduced > $targetPlan) {
                        $excessQuantity = $accumulatorProduced - $targetPlan;

                        // Cubrió el plan: Completado (salvo "En progreso").
                        $existingRecordUpdate = ['produced_quantity' => $targetPlan];
                        if (!$existingRecordIsInProgress && $completedStatus) {
                            $existingRecordUpdate['status_id'] = $completedStatus->id;
                        }
                        $existingRecord->update($existingRecordUpdate);

                        $accumulator->update([
                            'planned_date' => $this->planned_date,
                            'shift_id' => $shift->id,
                            'produced_quantity' => $excessQuantity,
                        ]);

                        Log::info("Registro #{$existingRecord->id} (orden {$existingRecord->shop_order_number}) completó su plan ({$targetPlan}) con el acumulador #{$accumulator->id} y quedó Completado. El acumulador conservó el restante ({$excessQuantity}) en {$this->planned_date}/{$this->planned_shift}.");
                    } else {
                        $existingRecordUpdate = ['produced_quantity' => $accumulatorProduced];

                        // Completado si alcanzó el plan, Detenido si no (salvo "En progreso").
                        if (!$existingRecordIsInProgress) {
                            if ($completedStatus && $accumulatorProduced >= $targetPlan) {
                                $existingRecordUpdate['status_id'] = $completedStatus->id;
                            } elseif ($accumulatorProduced > 0) {
                                $stoppedStatus = Status::where('name', 'LIKE', 'Detenid%')->first();
                                if ($stoppedStatus) {
                                    $existingRecordUpdate['status_id'] = $stoppedStatus->id;
                                }
                            }
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
                            'production_order' => $productionOrderForRecord,
                            'status_id' => $completedStatus->id,
                            'synced_to_infor' => false,
                        ]);

                        $accumulatorUpdate = [
                            'shop_order_number' => null,
                            'planned_quantity' => 0,
                            'produced_quantity' => $excessQuantity,
                        ];

                        // "En progreso": el restante se mueve a la fecha/turno actual (ahí
                        // sigue produciendo). Si no, conserva su ubicación original.
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
                            // Cubrió el plan: Completado.
                            $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();
                            if ($completedStatus) {
                                $accumulatorUpdate['status_id'] = $completedStatus->id;
                            }
                        } elseif (!$accumulatorWasInProgress) {
                            // No alcanzó el plan y no seguía en progreso: Detenido.
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

            // "En progreso": al cubrir el plan (producido >= plan) se congela en un
            // registro nuevo y este se libera para seguir acumulando. Si no, solo se
            // divide si de verdad se rebasa (producido > plan).
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
                        'production_order' => $productionOrderForRecord,
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
                    $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();

                    $existingRecordUpdate = [
                        'shop_order_number' => $this->shop_order_number,
                        'planned_quantity' => $plannedQuantityInt,
                        'produced_quantity' => $plannedQuantityInt,
                    ];

                    if ($completedStatus) {
                        $existingRecordUpdate['status_id'] = $completedStatus->id;
                    }

                    if ($shouldRefreshOrder) {
                        $existingRecordUpdate['production_order'] = $productionOrderForRecord;
                    }

                    $existingRecord->update($existingRecordUpdate);

                    $overflowStatus = Status::where('name', 'LIKE', 'No planeado')->first();

                    ProductionRecord::create([
                        'part_number_id' => $partNumber->id,
                        'planned_date' => $this->planned_date,
                        'shift_id' => $shift->id,
                        'planned_quantity' => 0,
                        'produced_quantity' => $excessQuantity,
                        'shop_order_number' => null,
                        'production_order' => $productionOrderForRecord,
                        'status_id' => $overflowStatus->id,
                        'synced_to_infor' => false,
                    ]);

                    Log::info("Registro (parte {$this->part_number}, {$this->planned_date} turno {$this->planned_shift}) produjo {$producedQuantity}, más que el plan ({$plannedQuantityInt}) de la orden {$this->shop_order_number}. Se limitó al plan y el restante ({$excessQuantity}) pasó a un registro nuevo sin planear.");
                }
            } elseif ((int) $existingRecord->planned_quantity !== $plannedQuantityInt || $existingRecord->shop_order_number !== $this->shop_order_number) {
                $existingRecordUpdate = [
                    'shop_order_number' => $this->shop_order_number,
                    'planned_quantity' => $plannedQuantityInt,
                ];

                if ($shouldRefreshOrder) {
                    $existingRecordUpdate['production_order'] = $productionOrderForRecord;
                }

                // "En progreso": no se toca el status, solo orden y plan. Si no: Completado
                // si alcanzó el plan, Detenido si tuvo producción y no alcanzó (un
                // placeholder nuevo con producido=0 se deja Pendiente).
                if (!$isInProgress) {
                    if ($producedQuantity >= $plannedQuantityInt) {
                        $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();
                        if ($completedStatus) {
                            $existingRecordUpdate['status_id'] = $completedStatus->id;
                        }
                    } elseif ($producedQuantity > 0) {
                        $stoppedStatus = Status::where('name', 'LIKE', 'Detenid%')->first();
                        if ($stoppedStatus) {
                            $existingRecordUpdate['status_id'] = $stoppedStatus->id;
                        }
                    }
                }

                $existingRecord->update($existingRecordUpdate);
                Log::info("Registro actualizado: parte {$this->part_number}, orden {$this->shop_order_number}, {$this->planned_date} turno {$this->planned_shift}, plan {$this->planned_quantity}.");
            }
        } else {
            ProductionRecord::store($partNumber->id, $plannedQuantityInt, $this->planned_date, $shift->id, $this->shop_order_number, $productionOrderForRecord);
        }
    }
}
