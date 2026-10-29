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

    protected $part_number;
    protected $orders;
    protected $sync_from;
    protected $operating_date;
    protected $operating_shift_id;

    // Orden que se está conciliando; handle() la asigna antes de cada reconcile().
    protected $shop_order_number;
    protected $planned_quantity;
    protected $planned_date;
    protected $planned_shift;

    /** Turnos ordenados por hora de inicio (D antes que N). */
    protected ?Collection $shifts = null;

    /**
     * Cambios de la corrida, por tipo; al final se escribe una sola línea por
     * parte. Las órdenes sin cambios no se registran.
     *
     * @var array<string, list<string>>
     */
    protected array $changes = [];

    /** @var list<string> */
    protected array $warnings = [];

    /** Máximo de detalles por tipo de cambio en la línea del log. */
    protected const LOG_DETAILS = 10;

    /** Horas que una advertencia idéntica no se vuelve a escribir. */
    protected const WARNING_SILENCE_HOURS = 24;

    /**
     * Create a new job instance.
     *
     * @param  array  $orders  Órdenes de FSO de la parte, en orden de fecha y turno.
     * @param  string|null  $sync_from  Fecha de arranque (sync_from de Live) de su estación.
     * @param  string  $operating_date  Fecha operativa del turno en curso (el N de madrugada es del día anterior).
     * @param  int  $operating_shift_id  Turno en curso.
     */
    public function __construct(string $part_number, array $orders, ?string $sync_from, string $operating_date, int $operating_shift_id)
    {
        $this->part_number = $part_number;
        $this->orders = $orders;
        $this->sync_from = $sync_from;
        $this->operating_date = $operating_date;
        $this->operating_shift_id = $operating_shift_id;
    }

    /**
     * Segundos que un job espera a que otro de la misma parte libere el
     * candado antes de regresar a la cola.
     */
    protected const LOCK_WAIT_SECONDS = 60;

    /**
     * Segundos que dura el candado: alcanza para todas las órdenes de la parte.
     */
    protected const LOCK_SECONDS = 600;

    /**
     * Execute the job.
     *
     * Solo un job a la vez por número de parte: si dos workers concilian
     * órdenes de la misma parte en paralelo, ambos leen el mismo acumulador
     * y reparten sus piezas dos veces. Las órdenes se concilian una por una en
     * orden de fecha y turno, porque el acumulador solo llena hacia adelante.
     */
    public function handle(): void
    {
        $lock = Cache::lock('production-plan:' . trim((string) $this->part_number), self::LOCK_SECONDS);

        try {
            $lock->block(self::LOCK_WAIT_SECONDS, function () {
                $this->shifts = Shift::query()->orderBy('start_time')->get();

                foreach ($this->orders as $order) {
                    $this->shop_order_number = $order['shop_order_number'];
                    $this->planned_quantity = $order['planned_quantity'];
                    $this->planned_date = $order['planned_date'];
                    $this->planned_shift = $order['planned_shift'];

                    $this->reconcile();
                }

                $this->consolidateAccumulators();

                $this->writeLog();
            });
        } catch (LockTimeoutException $e) {
            Log::warning("[Plan] {$this->part_number} · bloqueada por otro job; sus " . count($this->orders) . ' órdenes regresan a la cola');
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

        // Antes de la fecha de arranque de la estación no se llena nada.
        if ($this->sync_from !== null && $plannedDateStr < $this->sync_from) {
            return;
        }

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
            return;
        }

        // Cerrada con su mes (iot:close-previous-month): aunque Infor la reprograme
        // no se mueve al mes nuevo ni se crea otro registro con la misma orden.
        $closedStatusIds = Status::whereIn('name', ['Incompleto', 'Expirado'])->pluck('id')->map(fn ($id) => (int) $id);
        if ($orderRecords->contains(fn ($record) => $closedStatusIds->contains((int) $record->status_id))) {
            return;
        }

        if ($orderRecords->count() > 1) {
            $this->warn("orden {$this->shop_order_number} repetida en #" . $orderRecords->pluck('id')->implode(', #') . "; se usa #{$orderRecords->first()->id}. Correr iot:merge-duplicate-part-numbers");
        }

        $existingRecord = $orderRecords->first();

        if ($existingRecord !== null && (
            (int) $existingRecord->part_number_id !== (int) $partNumber->id
            || Carbon::parse($existingRecord->planned_date)->toDateString() !== $plannedDateStr
            || (int) $existingRecord->shift_id !== (int) $shift->id
        )) {
            $existingRecord->update([
                'part_number_id' => $partNumber->id,
                'planned_date' => $this->planned_date,
                'shift_id' => $shift->id,
            ]);

            $this->change('reprogramadas', "{$this->shop_order_number} → {$plannedDateStr} {$this->planned_shift}");
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
        // planeado" o "En progreso"). Solo sirve el del mismo mes que la orden y
        // con fecha/turno igual o anterior a ella (llena hacia adelante: D, luego
        // N, luego el D siguiente...), desde la fecha de arranque de la estación.
        // Como al llenar solo se mueve al slot de la orden, nunca cambia de mes.
        $accumulatorStatusIds = array_filter([
            optional($accumulatorStatus)->id,
            optional($inProgressStatus)->id,
        ]);

        if (!empty($accumulatorStatusIds) && !$orderAlreadyCompleted) {
            $accumulatorCandidates = ProductionRecord::accumulators($partNumber->id)
                ->whereIn('status_id', $accumulatorStatusIds)
                ->when($existingRecord, fn ($query) => $query->where('id', '!=', $existingRecord->id))
                ->where('planned_date', '>=', Carbon::parse($plannedDateStr)->startOfMonth()->toDateString())
                ->when($this->sync_from !== null, fn ($query) => $query->where('planned_date', '>=', $this->sync_from))
                ->where(fn ($query) => $query->where('planned_date', '<', $plannedDateStr)
                    ->orWhere(fn ($query) => $query->where('planned_date', $plannedDateStr)
                        ->whereIn('shift_id', $this->shiftIdsWhere('<=', $shift))))
                ->get();

            // Con varios acumuladores se usa el más antiguo que no esté "En progreso"
            // (en ese puede seguir escribiendo el PLC). Si todos lo están, se omite.
            $accumulator = $accumulatorCandidates->count() > 1
                ? $accumulatorCandidates->sortBy('id')->first(fn ($candidate) => ! ($inProgressStatus && (int) $candidate->status_id === $inProgressStatus->id))
                : $accumulatorCandidates->first();

            if ($accumulator === null && $accumulatorCandidates->count() > 1) {
                $this->warn("orden {$this->shop_order_number} sin conciliar: varios acumulados En progreso (#" . $accumulatorCandidates->pluck('id')->implode(', #') . ')');
            } elseif ($accumulator !== null) {
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
                    $this->warn("orden {$this->shop_order_number} sin conciliar: el acumulado #{$accumulator->id} y el registro #{$existingRecord->id} ({$this->planned_date} {$this->planned_shift}) tienen producción propia");
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
                    } else {
                        ProductionRecord::discard($accumulator, $partNumber, $existingRecord);
                    }

                    $this->change('llenadas con acumulado', "{$this->shop_order_number} +{$transferQuantity} ({$newProducedQuantity}/{$plannedQuantityInt})");

                    return;
                } elseif ($existingRecordHasLabels) {
                    $this->warn("orden {$this->shop_order_number} sin conciliar: el registro #{$existingRecord->id} ({$this->planned_date} {$this->planned_shift}) está vacío pero tiene etiquetas");
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

                        // El restante se mueve a la fecha/turno de la orden que llenó (siempre
                        // hacia adelante y en el mismo mes), como en un rellenado parcial.
                        $accumulator->update([
                            'planned_date' => $this->planned_date,
                            'shift_id' => $shift->id,
                            'shop_order_number' => null,
                            'planned_quantity' => 0,
                            'produced_quantity' => $excessQuantity,
                        ]);

                        $this->change('completadas con acumulado', "{$this->shop_order_number} ({$plannedQuantityInt}, sobran {$excessQuantity})");
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

                        $this->change('llenadas con acumulado', "{$this->shop_order_number} +{$producedQuantity} ({$producedQuantity}/{$plannedQuantityInt})");
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

                    $this->change('completadas', "{$this->shop_order_number} ({$plannedQuantityInt}, sobran {$excessQuantity})");
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

                    $this->change('completadas', "{$this->shop_order_number} ({$plannedQuantityInt}, sobran {$excessQuantity})");
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
                $this->change('plan actualizado', "{$this->shop_order_number} ({$plannedQuantityInt})");
            }
        } else {
            ProductionRecord::store($partNumber->id, $plannedQuantityInt, $this->planned_date, $shift->id, $this->shop_order_number, $productionOrderForRecord);
            $this->change('nuevas', $this->shop_order_number);
        }
    }

    /**
     * Lo que sobró del mes operativo, ya llenadas las órdenes, se junta en un
     * solo acumulador "No planeado" en la fecha/turno operativo actual. Así no
     * quedan acumulados en fechas anteriores y el último día del mes todo queda
     * en su último turno. Si ya está en ese turno, solo se suman las cantidades.
     * Los "En progreso" no se mueven ni se juntan (el recolector puede estar
     * escribiendo en ellos).
     */
    protected function consolidateAccumulators(): void
    {
        $partNumber = PartNumber::query()->where('number', $this->part_number)->orderByDesc('id')->first();
        $accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();
        $operatingShift = $this->shifts->firstWhere('id', $this->operating_shift_id);

        if (!$partNumber || !$accumulatorStatus || !$operatingShift) {
            return;
        }

        $this->discardEmptyAccumulators($partNumber, $accumulatorStatus);

        $operatingMonth = Carbon::parse($this->operating_date);

        $accumulators = ProductionRecord::accumulators($partNumber->id)
            ->where('status_id', $accumulatorStatus->id)
            ->whereBetween('planned_date', [
                $operatingMonth->copy()->startOfMonth()->toDateString(),
                $operatingMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->when($this->sync_from !== null, fn ($query) => $query->where('planned_date', '>=', $this->sync_from))
            ->orderBy('id')
            ->get();

        $isAtOperatingSlot = fn (ProductionRecord $record) => Carbon::parse($record->planned_date)->toDateString() === $this->operating_date
            && (int) $record->shift_id === (int) $operatingShift->id;

        // Se conserva el que ya está en el turno actual; si no hay, el más antiguo.
        $keeper = $accumulators->first($isAtOperatingSlot) ?? $accumulators->first();

        if ($keeper === null) {
            return;
        }

        $moved = !$isAtOperatingSlot($keeper);

        if ($moved) {
            $keeper->update([
                'planned_date' => $this->operating_date,
                'shift_id' => $operatingShift->id,
            ]);
        }

        $merged = $accumulators->reject(fn (ProductionRecord $record) => $record->is($keeper));

        foreach ($merged as $record) {
            ProductionRecord::moveToAccumulator($partNumber, $record, $keeper);
        }

        if ($moved || $merged->isNotEmpty()) {
            $keeper->refresh();

            $this->change('acumulado', "#{$keeper->id} en {$this->operating_date} {$operatingShift->abbreviation}, {$keeper->produced_quantity} pzs"
                . ($merged->isNotEmpty() ? ", juntó #" . $merged->pluck('id')->implode(', #') : ''));
        }
    }

    /**
     * Un acumulador "No planeado" sin orden, sin plan y sin producción ya no
     * guarda nada: se borra. Los que tienen etiquetas no se pueden borrar y se
     * dejan. Los "En progreso" no entran porque el recolector puede estar
     * escribiendo en ellos.
     */
    protected function discardEmptyAccumulators(PartNumber $partNumber, Status $accumulatorStatus): void
    {
        ProductionRecord::query()
            ->where('part_number_id', $partNumber->id)
            ->whereNotExists(fn ($query) => $query->from('production_labels')->whereColumn('production_labels.production_record_id', 'production_records.id'))
            ->where('status_id', $accumulatorStatus->id)
            ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
            ->where('planned_quantity', 0)
            ->where('produced_quantity', 0)
            ->where('synced_to_infor', false)
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->orderBy('id')
            ->get()
            ->each(function (ProductionRecord $record) use ($partNumber) {
                ProductionRecord::discard($record, $partNumber);

                $this->change('acumulados en cero descartados', "#{$record->id}");
            });
    }

    /**
     * Ids de los turnos cuya hora de inicio cumple "$operator" contra la de
     * $shift; p. ej. '<=' con N da D y N.
     */
    protected function shiftIdsWhere(string $operator, Shift $shift): array
    {
        return $this->shifts->where('start_time', $operator, $shift->start_time)->pluck('id')->all();
    }

    /**
     * Si el sistema externo escribió producción sin orden en un id viejo
     * (duplicado) de este número de parte, se pasa al acumulador del id
     * correcto para que se reparta en las órdenes. Los "En progreso" se dejan
     * (el PLC puede seguir escribiendo en ellos), los "No verificado" no son
     * acumulado, los "Expirado" y "Cancelado" ya se cerraron, y los ya
     * sincronizados con Infor no se tocan. Los que traen orden los mueve la
     * búsqueda por orden.
     */
    protected function absorbDuplicatedPartNumberRecords(PartNumber $partNumber, Collection $partNumberIds, ?Status $inProgressStatus): void
    {
        $oldIds = $partNumberIds->reject(fn ($id) => (int) $id === (int) $partNumber->id);

        if ($oldIds->isEmpty()) {
            return;
        }

        $keptStatusIds = Status::whereIn('name', ['No verificado', 'Expirado', 'Cancelado'])->pluck('id')->all();

        ProductionRecord::query()
            ->whereIn('part_number_id', $oldIds)
            ->where('synced_to_infor', false)
            ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
            ->where('produced_quantity', '>', 0)
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->when($inProgressStatus, fn ($query) => $query->where(
                fn ($query) => $query->whereNull('status_id')->orWhere('status_id', '!=', $inProgressStatus->id)
            ))
            ->when($keptStatusIds, fn ($query) => $query->where(
                fn ($query) => $query->whereNull('status_id')->orWhereNotIn('status_id', $keptStatusIds)
            ))
            ->orderBy('id')
            ->get()
            ->each(function (ProductionRecord $record) use ($partNumber) {
                $producedQuantity = (int) $record->produced_quantity;
                $accumulator = ProductionRecord::moveToAccumulator($partNumber, $record);

                $this->change('de ids viejos al acumulado', "#{$record->id} +{$producedQuantity} → #{$accumulator->id}");
            });
    }

    protected function change(string $type, string $detail): void
    {
        $this->changes[$type][] = $detail;
    }

    protected function warn(string $message): void
    {
        $this->warnings[] = $message;
    }

    /**
     * Una línea por parte con lo que cambió (nada si no hubo cambios) y cada
     * advertencia una sola vez cada WARNING_SILENCE_HOURS, aunque el plan
     * corra cada hora sobre las mismas órdenes.
     *
     * Ej.: [Plan] DGH954570A · nuevas: 4202801 · reprogramadas: 4202796 → 2026-10-30 N
     */
    protected function writeLog(): void
    {
        if ($this->changes !== []) {
            $parts = collect($this->changes)->map(function (array $details, string $type) {
                $shown = array_slice($details, 0, self::LOG_DETAILS);
                $rest = count($details) - count($shown);

                return "{$type}: " . implode(', ', $shown) . ($rest > 0 ? " y {$rest} más" : '');
            });

            Log::info("[Plan] {$this->part_number} · " . $parts->implode(' · '));
        }

        foreach (array_unique($this->warnings) as $message) {
            $key = 'plan-warning:' . md5($this->part_number . '|' . $message);

            if (Cache::add($key, true, now()->addHours(self::WARNING_SILENCE_HOURS))) {
                Log::warning("[Plan] {$this->part_number} · {$message}");
            }
        }

        $this->changes = [];
        $this->warnings = [];
    }
}
