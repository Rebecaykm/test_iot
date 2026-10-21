<?php

namespace App\Jobs;

use App\Models\PartNumber;
use App\Models\ProductionOrderHistory;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
     * Segundos que un job espera a que otro de la misma parte libere el
     * candado antes de regresar a la cola.
     */
    protected const LOCK_WAIT_SECONDS = 60;

    /**
     * Execute the job.
     *
     * Solo un job a la vez por número de parte: si dos workers concilian
     * órdenes de la misma parte en paralelo, ambos leen el mismo acumulador
     * y reparten sus piezas dos veces.
     */
    public function handle(): void
    {
        $lock = Cache::lock('production-plan:' . trim((string) $this->part_number), 120);

        try {
            $lock->block(self::LOCK_WAIT_SECONDS, fn () => $this->reconcile());
        } catch (LockTimeoutException $e) {
            Log::warning("StoreProductionPlanJob: la parte {$this->part_number} sigue bloqueada por otro job; la orden {$this->shop_order_number} regresa a la cola.");
            $this->release(15);
        }
    }

    protected function reconcile(): void
    {
        $partNumber = PartNumber::query()->where('number', $this->part_number)->orderByDesc('id')->first();
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

        $accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();
        $inProgressStatus = Status::where('name', 'LIKE', 'En progreso')->first();
        $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();

        // Todos los ids con este número (puede haber duplicados viejos); el
        // correcto es el más reciente ($partNumber).
        $partNumberIds = PartNumber::query()->where('number', $this->part_number)->pluck('id');

        $this->absorbDuplicatedPartNumberRecords($partNumber, $partNumberIds, $inProgressStatus);

        // La orden es la que identifica al registro: se busca por shop_order_number
        // en todos los ids del número de parte, sin importar fecha ni turno, porque
        // Infor puede reprogramarla. Si hay repetidos se toma el que tiene más
        // producción (iot:merge-duplicate-part-numbers limpia los demás).
        $orderRecords = ProductionRecord::query()
            ->whereIn('part_number_id', $partNumberIds)
            ->where('shop_order_number', $this->shop_order_number)
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->orderByDesc('produced_quantity')
            ->orderByDesc('id')
            ->get();

        // Ya está en Infor: no se mueve ni se crea otro registro con la misma orden.
        if ($orderRecords->contains(fn ($record) => $record->synced_to_infor)) {
            Log::info("Orden {$this->shop_order_number} (parte {$this->part_number}) ya está sincronizada con Infor; no se modifica.");
            return;
        }

        if ($orderRecords->count() > 1) {
            Log::warning("Orden {$this->shop_order_number} (parte {$this->part_number}) repetida en los registros #" . $orderRecords->pluck('id')->implode(', #') . "; se usa #{$orderRecords->first()->id}. Correr iot:merge-duplicate-part-numbers.");
        }

        $existingRecord = $orderRecords->first();

        if ($existingRecord !== null && (
            (int) $existingRecord->part_number_id !== (int) $partNumber->id
            || Carbon::parse($existingRecord->planned_date)->toDateString() !== $plannedDateStr
            || (int) $existingRecord->shift_id !== (int) $shift->id
        )) {
            $previousSlot = Carbon::parse($existingRecord->planned_date)->toDateString() . " turno #{$existingRecord->shift_id}, parte #{$existingRecord->part_number_id}";

            $existingRecord->update([
                'part_number_id' => $partNumber->id,
                'planned_date' => $this->planned_date,
                'shift_id' => $shift->id,
            ]);

            Log::info("Orden {$this->shop_order_number} (parte {$this->part_number}) cambió en Infor; el registro #{$existingRecord->id} se movió de {$previousSlot} a {$plannedDateStr} turno {$this->planned_shift}, parte #{$partNumber->id}.");
        }

        // Si la orden todavía no existe, se usa un registro libre (sin orden) del
        // mismo slot. Así un acumulador vacío en el mismo slot no se toma por la orden.
        $existingRecord ??= ProductionRecord::where([
            'part_number_id' => $partNumber->id,
            'planned_date' => $this->planned_date,
            'shift_id' => $shift->id,
        ])
            ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
            ->orderBy('id')
            ->first();

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

            // Con varios acumuladores se usa el más antiguo que no esté "En progreso"
            // (en ese puede seguir escribiendo el PLC). Si todos lo están, se omite.
            $accumulator = $accumulatorCandidates->count() > 1
                ? $accumulatorCandidates->sortBy('id')->first(fn ($candidate) => ! ($inProgressStatus && (int) $candidate->status_id === $inProgressStatus->id))
                : $accumulatorCandidates->first();

            if ($accumulator === null && $accumulatorCandidates->count() > 1) {
                Log::warning("Varios acumuladores En progreso para la parte {$this->part_number} (IDs: " . $accumulatorCandidates->pluck('id')->implode(', ') . "). Se omite conciliación de la orden {$this->shop_order_number}; revisar manualmente.");
            } elseif ($accumulator !== null) {
                if ($accumulatorCandidates->count() > 1) {
                    Log::info("Varios acumuladores para la parte {$this->part_number} (IDs: " . $accumulatorCandidates->pluck('id')->implode(', ') . "); se usa #{$accumulator->id} para la orden {$this->shop_order_number}.");
                }

                // "En progreso" no se toca (el PLC puede seguir escribiendo sobre él).
                $existingRecordIsInProgress = $existingRecord !== null && $inProgressStatus && (int) $existingRecord->status_id === $inProgressStatus->id;

                $existingRecordHasOwnProduction = $existingRecord !== null && (int) $existingRecord->produced_quantity > 0;

                // Ya tiene orden/plan de una corrida anterior: se rellena con el acumulador.
                $existingRecordHasOwnOrderOrPlan = $existingRecord !== null && (
                    !empty($existingRecord->shop_order_number) || (int) $existingRecord->planned_quantity > 0
                );

                // Lo que le falta para llegar al plan de Infor.
                $existingRecordMissingQuantity = $existingRecord !== null
                    ? max(0, $plannedQuantityInt - (int) $existingRecord->produced_quantity)
                    : 0;

                $existingRecordHasLabels = $existingRecord !== null
                    && DB::table('production_labels')->where('production_record_id', $existingRecord->id)->exists();

                if ($existingRecordHasOwnProduction && ($existingRecordIsInProgress || !$existingRecordHasOwnOrderOrPlan)) {
                    Log::warning("Acumulador #{$accumulator->id} y registro #{$existingRecord->id} (parte {$this->part_number}, {$this->planned_date} turno {$this->planned_shift}) tienen producción propia. Se omite conciliación; revisar manualmente.");
                } elseif ($existingRecordHasOwnOrderOrPlan && $existingRecordMissingQuantity === 0) {
                    // Ya llegó al plan: no recibe nada del acumulador, sigue la actualización normal.
                } elseif ($existingRecordHasOwnOrderOrPlan) {
                    $accumulatorProduced = (int) $accumulator->produced_quantity;
                    $transferQuantity = min($accumulatorProduced, $existingRecordMissingQuantity);
                    $remainingQuantity = $accumulatorProduced - $transferQuantity;
                    $newProducedQuantity = (int) $existingRecord->produced_quantity + $transferQuantity;
                    $completedStatus = Status::where('name', 'LIKE', 'Completado')->first();

                    $existingRecordUpdate = [
                        'shop_order_number' => $this->shop_order_number,
                        'planned_quantity' => $plannedQuantityInt,
                        'produced_quantity' => $newProducedQuantity,
                    ];

                    if ($shouldRefreshOrder) {
                        $existingRecordUpdate['production_order'] = $productionOrderForRecord;
                    }

                    // Completado si alcanzó el plan, Detenido si no (salvo "En progreso").
                    if (!$existingRecordIsInProgress) {
                        if ($completedStatus && $newProducedQuantity >= $plannedQuantityInt) {
                            $existingRecordUpdate['status_id'] = $completedStatus->id;
                        } else {
                            $stoppedStatus = Status::where('name', 'LIKE', 'Detenid%')->first();
                            if ($stoppedStatus) {
                                $existingRecordUpdate['status_id'] = $stoppedStatus->id;
                            }
                        }
                    }

                    $existingRecord->update($existingRecordUpdate);

                    if ($remainingQuantity > 0) {
                        $accumulator->update([
                            'planned_date' => $this->planned_date,
                            'shift_id' => $shift->id,
                            'produced_quantity' => $remainingQuantity,
                        ]);

                        Log::info("Registro #{$existingRecord->id} (orden {$this->shop_order_number}) se rellenó con {$transferQuantity} piezas del acumulador #{$accumulator->id} ({$newProducedQuantity}/{$plannedQuantityInt}). El acumulador conservó el restante ({$remainingQuantity}) en {$this->planned_date}/{$this->planned_shift}.");
                    } else {
                        ProductionRecord::discard($accumulator, $partNumber, $existingRecord);
                        Log::info("Registro #{$existingRecord->id} (orden {$this->shop_order_number}) se rellenó con {$transferQuantity} piezas del acumulador #{$accumulator->id} ({$newProducedQuantity}/{$plannedQuantityInt}); el acumulador quedó vacío y se descartó.");
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

    /**
     * Si el sistema externo escribió producción sin orden en un id viejo
     * (duplicado) de este número de parte, se pasa al acumulador del id
     * correcto para que se reparta en las órdenes. Los "En progreso" se dejan
     * (el PLC puede seguir escribiendo en ellos) y los ya sincronizados con
     * Infor no se tocan. Los que traen orden los mueve la búsqueda por orden.
     */
    protected function absorbDuplicatedPartNumberRecords(PartNumber $partNumber, Collection $partNumberIds, ?Status $inProgressStatus): void
    {
        $oldIds = $partNumberIds->reject(fn ($id) => (int) $id === (int) $partNumber->id);

        if ($oldIds->isEmpty()) {
            return;
        }

        ProductionRecord::query()
            ->whereIn('part_number_id', $oldIds)
            ->where('synced_to_infor', false)
            ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
            ->where('produced_quantity', '>', 0)
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->when($inProgressStatus, fn ($query) => $query->where(
                fn ($query) => $query->whereNull('status_id')->orWhere('status_id', '!=', $inProgressStatus->id)
            ))
            ->orderBy('id')
            ->get()
            ->each(function (ProductionRecord $record) use ($partNumber) {
                $producedQuantity = (int) $record->produced_quantity;
                $oldPartNumberId = $record->part_number_id;
                $accumulator = ProductionRecord::moveToAccumulator($partNumber, $record);

                Log::info("Parte {$this->part_number}: el registro #{$record->id} estaba en el id viejo #{$oldPartNumberId}; sus {$producedQuantity} piezas pasaron al acumulador #{$accumulator->id} del id #{$partNumber->id}.");
            });
    }
}
