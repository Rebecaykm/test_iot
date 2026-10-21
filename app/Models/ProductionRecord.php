<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionRecord extends Model
{
    use HasFactory;

    /**
     * TTL (segundos) del cache de consultas de solo lectura usadas por las
     * vistas de piso (ayudas visuales, gráficas, tableros). Corto a propósito:
     * absorbe ráfagas de pantallas consultando el mismo work center/turno casi
     * al mismo tiempo, sin dejar la información "desactualizada" por mucho tiempo.
     */
    const PRODUCTION_CACHE_TTL = 20;

    /**
     * Fecha desde la que se reconstruye plan y acumulados; lo anterior se
     * ignora para no mezclar acumuladores viejos con órdenes nuevas.
     */
    const HISTORY_FLOOR_DATE = '2026-09-21';

    /** Recorta una fecha para que nunca quede antes de HISTORY_FLOOR_DATE. */
    public static function applyHistoryFloor(Carbon $date): Carbon
    {
        $floor = Carbon::parse(self::HISTORY_FLOOR_DATE);

        return $date->lt($floor) ? $floor->copy() : $date;
    }

    protected $fillable = [
        'part_number_id',
        'planned_quantity',
        'produced_quantity',
        'scrap_quantity',
        'planned_date',
        'production_start',
        'production_end',
        'shift_id',
        'status_id',
        'shop_order_number',
        'production_order',
        'synced_to_infor',
        'synced_at'
    ];

    protected $casts = [
        'planned_quantity' => 'integer',
        'produced_quantity' => 'integer',
        'scrap_quantity' => 'integer',
        'production_order' => 'integer',
        'synced_to_infor' => 'boolean',
    ];

    /**
     * [YFQPLA, YFQPRO] a enviar a YF013.
     */
    public function inforQuantities(): array
    {
        return [
            $this->planned_quantity ?: $this->produced_quantity,
            $this->produced_quantity - ($this->scrap_quantity ?? 0),
        ];

        // Compensación de la sonda (qty=1) del Job de recuperación de orden.
        // Reactivar si se vuelve a habilitar la recuperación en routes/console.php:
        // $producedNet = $this->produced_quantity - ($this->scrap_quantity ?? 0);
        // if ((int) $this->planned_quantity === 0) {
        //     return [1, max(0, $producedNet - 1)];
        // }
        // return [$this->planned_quantity, $producedNet];
    }

    /**
     * Relación con Shift
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Relación con Status
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Relación con PartNumber
     */
    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class, 'part_number_id');
    }

    /**
     * Relación con ProductionSequence
     */
    public function sequences()
    {
        return $this->hasMany(ProductionSequence::class);
    }

    /**
     * Método optimizado para obtener registros de producción con eficiencia
     */
    public static function getWorkCenterProductionRecord(string $workCenter, int $shiftId, $now): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_rate as production_rate',
                'part_numbers.efficiency as efficiency', // Agregar eficiencia
                'part_numbers.production_order as production_order',
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name',
                'production_records.production_start AS production_start'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->where('statuses.id', 7)
            ->orderBy('part_numbers.production_order', 'asc')
            ->orderBy('shifts.start_time', 'asc')
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('production_records.production_end', 'desc')
            ->get();
    }

    /**
     *
     */
    public static function store(
        $partNumberId,
        $plannedQuantity,
        $plannedDate,
        $shiftId = null,
        $shopOrderNumber = null,
        $productionOrder = null,
    ) {
        $plannedQuantity = (int) $plannedQuantity;

        $status = Status::where('name', 'LIKE', 'Pendiente')->first();

        $productionPlan = ProductionRecord::query()->where([['part_number_id', $partNumberId], ['planned_date', $plannedDate], ['shift_id', $shiftId], ['shop_order_number', $shopOrderNumber]])->first();

        if ($productionPlan === null) {
            return ProductionRecord::create([
                'part_number_id' => $partNumberId,
                'planned_quantity' => $plannedQuantity,
                'planned_date' => $plannedDate,
                'shift_id' => $shiftId,
                'status_id' => $status->id,
                'shop_order_number' => $shopOrderNumber,
                'production_order' => $productionOrder,
                'synced_to_infor' => false,
            ]);
        }

        return $productionPlan;
    }

    /**
     * Pasa lo producido (y el scrap) de $source al acumulador de $partNumber,
     * para que iot:production-plan lo reparta en las órdenes que falten por
     * llenar; si no hay ninguna, se queda en el acumulador. Después descarta
     * $source.
     *
     * Solo usa un acumulador "No planeado" (el más antiguo) y nunca uno "En
     * progreso", porque el PLC puede estar escribiendo en él. Si no hay
     * ninguno, crea uno en la fecha/turno de $source.
     */
    public static function moveToAccumulator(PartNumber $partNumber, self $source): self
    {
        return DB::transaction(function () use ($partNumber, $source) {
            $accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();
            $producedQuantity = (int) $source->produced_quantity;
            $scrapQuantity = (int) $source->scrap_quantity;

            $accumulator = self::query()
                ->where('part_number_id', $partNumber->id)
                ->where('id', '!=', $source->id)
                ->where('status_id', $accumulatorStatus->id)
                ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
                ->where('planned_quantity', 0)
                ->where('produced_quantity', '>', 0)
                ->where('planned_date', '>=', self::HISTORY_FLOOR_DATE)
                ->where(function ($query) {
                    $query->whereNull('production_start')
                        ->orWhere('production_start', '>=', self::HISTORY_FLOOR_DATE);
                })
                ->orderBy('id')
                ->first();

            if ($accumulator !== null) {
                $accumulator->increment('produced_quantity', $producedQuantity);

                // scrap_quantity puede ser NULL y NULL + n da NULL en SQL Server.
                if ($scrapQuantity > 0) {
                    $accumulator->update(['scrap_quantity' => (int) $accumulator->scrap_quantity + $scrapQuantity]);
                }
            } else {
                $accumulator = self::create([
                    'part_number_id' => $partNumber->id,
                    'planned_date' => $source->planned_date,
                    'shift_id' => $source->shift_id,
                    'planned_quantity' => 0,
                    'produced_quantity' => $producedQuantity,
                    'scrap_quantity' => $scrapQuantity,
                    'shop_order_number' => null,
                    'production_order' => $partNumber->production_order,
                    'status_id' => $accumulatorStatus->id,
                    'synced_to_infor' => false,
                ]);
            }

            self::discard($source, $partNumber, $accumulator);

            return $accumulator;
        });
    }

    /**
     * Borra un registro que sobra (duplicado o ya pasado al acumulador). Sus
     * production_sequences pasan a $replacement. Si tiene etiquetas no se
     * puede borrar: se deja en 0, sin orden ni plan, en el número de parte
     * correcto, para que ya no cuente ni repita la orden.
     */
    public static function discard(self $record, PartNumber $partNumber, ?self $replacement = null): void
    {
        DB::table('production_sequences')
            ->where('production_record_id', $record->id)
            ->update(['production_record_id' => $replacement?->id]);

        if (DB::table('production_labels')->where('production_record_id', $record->id)->exists()) {
            $record->update([
                'part_number_id' => $partNumber->id,
                'shop_order_number' => null,
                'planned_quantity' => 0,
                'produced_quantity' => 0,
                'scrap_quantity' => 0,
            ]);

            return;
        }

        $record->delete();
    }

    /**
     * Método para obtener registros con cache para mejor performance
     */
    public static function getCachedWorkCenterProductionRecord(string $workCenter, int $shiftId, $now): Collection
    {
        $cacheKey = "production_records_{$workCenter}_{$shiftId}_{$now->toDateString()}";

        return cache()->remember($cacheKey, self::PRODUCTION_CACHE_TTL, function () use ($workCenter, $shiftId, $now) {
            return self::getWorkCenterProductionRecord($workCenter, $shiftId, $now);
        });
    }

    /**
     * Método para limpiar cache cuando hay actualizaciones
     */
    public static function clearProductionCache(string $workCenter, int $shiftId, $date)
    {
        $cacheKey = "production_records_{$workCenter}_{$shiftId}_{$date}";
        cache()->forget($cacheKey);
    }

    public static function getProductionRecords(string $workCenter, int $shiftId, $now): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.id AS part_number_id',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_rate as production_rate',
                'part_numbers.production_order as production_order', // Agregamos el campo de orden
                'production_records.planned_date AS planned_date',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.shop_order_number AS order_number',
                'shifts.abbreviation AS shift_name',
                'statuses.name AS status_name',
                'production_records.production_start AS production_start'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->orderBy('part_numbers.production_order', 'asc')
            // ->orderBy('shifts.start_time', 'asc')
            // ->orderBy('production_records.planned_date', 'asc')
            // ->orderBy('production_records.production_end', 'desc')
            ->get();
    }

    /**
     * Versión con cache de getProductionRecords(), para las vistas que refrescan
     * seguido (gráficas de prensa, tablero de producción del turno). Varias
     * llamadas para el mismo work center/turno/día dentro del TTL reutilizan el
     * mismo resultado en vez de repetir el join de 4 tablas contra SQL Server.
     */
    public static function getCachedProductionRecords(string $workCenter, int $shiftId, $now): Collection
    {
        $cacheKey = "production_plan_records_{$workCenter}_{$shiftId}_{$now->toDateString()}";

        return cache()->remember($cacheKey, self::PRODUCTION_CACHE_TTL, function () use ($workCenter, $shiftId, $now) {
            return self::getProductionRecords($workCenter, $shiftId, $now);
        });
    }

    /**
     * Obtiene los registros de producción de un turno con su ventana de tiempo
     * (production_start / production_end) para dibujar una línea de tiempo (Gantt).
     * Solo trae los registros que ya iniciaron producción.
     */
    public static function getShiftProductionTimeline(string $workCenter, int $shiftId, $date): Collection
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'part_numbers.production_order as production_order',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'production_records.production_start AS production_start',
                'production_records.production_end AS production_end',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->where('production_records.planned_date', \Carbon\Carbon::parse($date)->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->whereNotNull('production_records.production_start')
            ->orderBy('production_records.production_start', 'asc')
            ->get();
    }

    /**
     * Plan del turno: TODAS las partes planeadas (hayan iniciado o no), agregadas
     * por número de parte. Se usa para dibujar la barra de "plan" en la línea de
     * tiempo, secuenciando por production_order y calculando el tiempo a partir de
     * production_rate (piezas/hora; en estampado es SPM y se convierte aparte).
     */
    public static function getShiftPlannedSchedule(string $workCenter, int $shiftId, $date): Collection
    {
        $date = \Carbon\Carbon::parse($date)->toDateString();

        $planParts = ProductionRecord::query()
            ->select([
                'part_numbers.id AS part_number_id',
                'part_numbers.number AS part_number',
                'part_numbers.production_order AS production_order',
                'part_numbers.production_rate AS production_rate',
                'part_numbers.efficiency AS efficiency',
                DB::raw('SUM(production_records.planned_quantity) AS planned_quantity'),
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->where('production_records.planned_date', $date)
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->groupBy(
                'part_numbers.id',
                'part_numbers.number',
                'part_numbers.production_order',
                'part_numbers.production_rate',
                'part_numbers.efficiency'
            )
            // Solo partes que tuvieron cantidad planeada o producida en el turno
            ->havingRaw('SUM(production_records.planned_quantity) > 0 OR SUM(production_records.produced_quantity) > 0')
            ->get();

        self::applyHistoricalProductionOrder($planParts, $date);

        return $planParts;
    }

    /**
     * Sustituye production_order (valor actual de part_numbers) por el que estaba
     * vigente en $date cuando esa fecha ya pasó, usando production_order_histories.
     * Sin esto, una línea de tiempo de un turno pasado ordenaría las filas con el
     * orden de HOY en vez del que realmente aplicaba ese día.
     */
    private static function applyHistoricalProductionOrder(Collection $planParts, string $date): void
    {
        if ($date >= now()->toDateString() || $planParts->isEmpty()) {
            return;
        }

        $partIds = $planParts->pluck('part_number_id')->unique()->all();

        $historicalOrders = ProductionOrderHistory::query()
            ->whereIn('part_number_id', $partIds)
            ->whereDate('effective_date', '<=', $date)
            ->orderBy('part_number_id')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get()
            ->unique('part_number_id')
            ->pluck('production_order', 'part_number_id');

        foreach ($planParts as $record) {
            if ($historicalOrders->has($record->part_number_id)) {
                $record->production_order = $historicalOrders->get($record->part_number_id);
            }
        }
    }

    /**
     * Obtiene los registros de producción de un work center en un rango de fechas
     * (p. ej. una semana de domingo a sábado), con plan y producido por registro.
     * Se usa para la tabla semanal de cumplimiento de producción.
     */
    public static function getWeeklyProductionRecords(string $workCenter, $startDate, $endDate): Collection
    {
        return ProductionRecord::query()
            ->select([
                'work_centers.name AS work_name',
                'production_records.shop_order_number AS order_number',
                'part_numbers.number AS part_number',
                'part_numbers.production_order AS production_order',
                'production_records.planned_date AS planned_date',
                'shifts.abbreviation AS shift',
                'shifts.name AS shift_name',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->whereBetween('production_records.planned_date', [
                \Carbon\Carbon::parse($startDate)->toDateString(),
                \Carbon\Carbon::parse($endDate)->toDateString(),
            ])
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->orderBy('production_records.planned_date', 'asc')
            ->orderBy('shifts.abbreviation', 'asc')
            ->orderBy('part_numbers.number', 'asc')
            ->get();
    }

    /**
     * Método para obtener el siguiente part number que debe producirse
     * basado en el orden de producción
     */
    public static function getNextPartNumberToProduceByOrder(string $workCenter, int $shiftId, $now)
    {
        return ProductionRecord::query()
            ->select([
                'production_records.id AS production_id',
                'part_numbers.number AS part_number',
                'part_numbers.production_order as production_order',
                'production_records.planned_quantity AS planned_quantity',
                'production_records.produced_quantity AS produced_quantity',
                'statuses.name AS status_name'
            ])
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
            ->join('shifts', 'production_records.shift_id', '=', 'shifts.id')
            ->join('statuses', 'production_records.status_id', '=', 'statuses.id')
            ->where('production_records.planned_date', $now->toDateString())
            ->where('shifts.id', $shiftId)
            ->where('work_centers.name', 'LIKE', $workCenter)
            ->whereIn('statuses.name', ['Pendiente', 'En Progreso']) // Solo pendientes o en proceso
            ->orderBy('part_numbers.production_order', 'asc')
            ->first();
    }

    /**
     * Plan de un turno de prensa agrupado por troquel (MDI), en GOLPES.
     *
     * Devuelve [clave del troquel => ['shots' => golpes planeados, 'rates' => SPM de
     * sus parts, 'order' => production_order más bajo]]. Lo comparten la tabla y la
     * gráfica de press-production para que ambas cuenten el mismo plan.
     *
     *  - Golpes: piezas planeadas / suma de pieces_per_shot del troquel, calculada
     *    solo con los parts del plan del turno (ver PartNumber::buildShotDivisors).
     *  - SPM: production_rate es el estándar en golpes/hora (SPH) => SPH / 60.
     */
    public static function getPressShiftPlanByDie(string $workCenter, int $shiftId, $date): array
    {
        $records = self::getCachedProductionRecords($workCenter, $shiftId, $date);

        $parts = PartNumber::query()
            ->whereIn('id', $records->pluck('part_number_id')->unique())
            ->with(['customAttributes' => fn ($q) => $q->whereIn('key', ['mid', 'pieces_per_shot'])])
            ->get()
            ->keyBy('id');

        $divisors = PartNumber::buildShotDivisors($parts);

        $dies = [];
        foreach ($records as $record) {
            $part = $parts->get($record->part_number_id);
            if (! $part) {
                continue;
            }

            $mid = trim((string) ($part->customAttributes->firstWhere('key', 'mid')->value ?? ''));
            $key = $mid !== '' ? $mid : "part:{$part->id}";

            $dies[$key] ??= ['shots' => 0.0, 'rates' => [], 'order' => PHP_INT_MAX];
            $dies[$key]['shots'] += $record->planned_quantity / ($divisors[$part->id] ?? 1);

            if ((float) $record->production_rate > 0) {
                $dies[$key]['rates'][] = (float) $record->production_rate / 60;
            }
            if (! is_null($record->production_order)) {
                $dies[$key]['order'] = min($dies[$key]['order'], (int) $record->production_order);
            }
        }

        // Orden de producción del plan (sin orden al final, desempate por troquel).
        uksort($dies, fn ($a, $b) => [$dies[$a]['order'], $a] <=> [$dies[$b]['order'], $b]);

        return $dies;
    }

    /**
     * Golpes que el plan de getPressShiftPlanByDie() debería llevar a los
     * $elapsedMinutes del turno.
     *
     * Los troqueles se recorren en su orden de producción, cada uno a su SPM estándar
     * (promedio de sus parts): golpes esperados = SPM * minutos, hasta agotar los
     * golpes planeados del troquel y pasar al siguiente.
     *
     * Un troquel sin estándar (production_rate en 0) no tiene ritmo con qué
     * secuenciarse: sus golpes se reparten parejo en todo el turno.
     */
    public static function expectedPressShots(array $dies, float $elapsedMinutes, float $totalShiftMinutes): float
    {
        $elapsedMinutes = max(0.0, min($elapsedMinutes, $totalShiftMinutes));

        $expected        = 0.0;
        $remaining       = $elapsedMinutes;
        $withoutStandard = 0.0;

        foreach ($dies as $die) {
            if (empty($die['rates'])) {
                $withoutStandard += $die['shots'];
                continue;
            }

            if ($remaining <= 0 || $die['shots'] <= 0) {
                continue;
            }

            $spm      = array_sum($die['rates']) / count($die['rates']);
            $duration = $die['shots'] / $spm;

            $expected  += min($die['shots'], $spm * $remaining);
            $remaining -= min($duration, $remaining);
        }

        return $expected + $withoutStandard * ($elapsedMinutes / max(1.0, $totalShiftMinutes));
    }
}
