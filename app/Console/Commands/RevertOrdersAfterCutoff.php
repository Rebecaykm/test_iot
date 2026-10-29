<?php

namespace App\Console\Commands;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Temporal (manual): revierte las órdenes que se trajeron con fecha planeada
 * después de PLAN_CUTOFF_DATE.
 *
 * Por cada número de parte:
 *  1. Lo producido en esas órdenes rellena las órdenes abiertas de esta semana
 *     (hasta PLAN_CUTOFF_DATE), de la más antigua a la más nueva.
 *  2. Lo que sobra, y todo el scrap, pasa al acumulador "No planeado" de la
 *     parte; si no tiene, se crea uno en la fecha y turno actuales.
 *  3. Las órdenes después del corte se borran junto con sus etiquetas.
 *
 * Lo "En progreso" (el PLC puede estar escribiendo) y lo ya enviado a Infor no
 * se toca. Si el total de piezas de la parte no cuadra al final, se revierte.
 */
class RevertOrdersAfterCutoff extends Command
{
    protected $signature = 'iot:revert-orders-after-cutoff
        {numbers?* : Números de parte a revertir (vacío = todos los que tengan órdenes después del corte)}
        {--dry-run : Hace todo dentro de una transacción y la revierte al final, sin guardar cambios}';

    protected $description = 'Regresa a esta semana o al acumulador lo producido en órdenes después de PLAN_CUTOFF_DATE y borra esas órdenes.';

    protected ?Status $inProgressStatus = null;

    protected ?Status $accumulatorStatus = null;

    protected ?Status $completedStatus = null;

    protected ?Status $stoppedStatus = null;

    protected array $totals = ['records' => 0, 'labels' => 0, 'orders' => 0, 'accumulator' => 0, 'skipped' => 0];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN: se muestra lo que se haría y se revierte, no se guarda ningún cambio.');
        }

        $this->inProgressStatus = Status::where('name', 'LIKE', 'En progreso')->first();
        $this->accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();
        $this->completedStatus = Status::where('name', 'LIKE', 'Completado')->first();
        $this->stoppedStatus = Status::where('name', 'LIKE', 'Detenid%')->first();

        $numbers = $this->targetNumbers(collect($this->argument('numbers'))->map(fn ($number) => trim($number))->filter());

        if ($numbers->isEmpty()) {
            $this->info('No hay órdenes después de ' . ProductionRecord::PLAN_CUTOFF_DATE . ' por revertir.');
            return self::SUCCESS;
        }

        $this->info("Corte: " . ProductionRecord::PLAN_CUTOFF_DATE . " · números de parte a revisar: {$numbers->count()}");

        foreach ($numbers as $number) {
            // Mismo candado que StoreProductionPlanJob, para no conciliar a la vez.
            $lock = Cache::lock('production-plan:' . trim((string) $number), 300);

            try {
                $lock->block(60, function () use ($number, $dryRun) {
                    DB::beginTransaction();

                    try {
                        $this->processNumber($number);
                        $dryRun ? DB::rollBack() : DB::commit();
                    } catch (Throwable $e) {
                        DB::rollBack();
                        $this->error("{$number}: error, no se guardó nada para este número. {$e->getMessage()}");
                    }
                });
            } catch (LockTimeoutException $e) {
                $this->warn("{$number}: otro proceso lo está conciliando, se omite. Vuelve a correr el comando más tarde.");
            }
        }

        $this->newLine();
        $this->info("Registros borrados: {$this->totals['records']} · etiquetas borradas: {$this->totals['labels']} · piezas a órdenes: {$this->totals['orders']} · piezas al acumulador: {$this->totals['accumulator']} · omitidos (En progreso o en Infor): {$this->totals['skipped']}");
        $this->info($dryRun ? 'DRY RUN terminado, no se guardó nada.' : 'Listo.');

        return self::SUCCESS;
    }

    protected function targetNumbers(Collection $only): Collection
    {
        return ProductionRecord::query()
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->where('production_records.planned_date', '>', ProductionRecord::PLAN_CUTOFF_DATE)
            ->when($only->isNotEmpty(), fn ($query) => $query->whereIn('part_numbers.number', $only))
            ->distinct()
            ->orderBy('part_numbers.number')
            ->pluck('part_numbers.number');
    }

    protected function processNumber(string $number): void
    {
        $partNumberIds = PartNumber::query()->where('number', $number)->pluck('id');
        $partNumber = PartNumber::query()->where('number', $number)->orderByDesc('id')->first();

        $afterCutoff = ProductionRecord::query()
            ->whereIn('part_number_id', $partNumberIds)
            ->where('planned_date', '>', ProductionRecord::PLAN_CUTOFF_DATE)
            ->orderBy('planned_date')
            ->orderBy('shift_id')
            ->orderBy('id')
            ->get();

        [$skipped, $sources] = $afterCutoff->partition(fn (ProductionRecord $record) => $record->synced_to_infor
            || ($this->inProgressStatus && (int) $record->status_id === $this->inProgressStatus->id));

        $this->totals['skipped'] += $skipped->count();

        if ($sources->isEmpty()) {
            $this->line("{$number}: solo tiene registros En progreso o en Infor (#" . $skipped->pluck('id')->implode(', #') . '), no se toca.');
            return;
        }

        $totalBefore = $this->totalPieces($partNumberIds);

        $produced = (int) $sources->sum(fn ($record) => (int) $record->produced_quantity);
        $scrap = (int) $sources->sum(fn ($record) => (int) $record->scrap_quantity);

        $toOrders = $produced > 0 ? $this->fillOpenOrders($partNumber, $produced) : collect();
        $leftover = $produced - (int) $toOrders->sum('quantity');

        $accumulator = ($leftover > 0 || $scrap > 0) ? $this->addToAccumulator($partNumber, $leftover, $scrap) : null;

        $sourceIds = $sources->pluck('id');
        $labels = 0;

        foreach ($sourceIds->chunk(1000) as $chunk) {
            DB::table('production_sequences')
                ->whereIn('production_record_id', $chunk)
                ->update(['production_record_id' => $accumulator?->id]);

            $labels += DB::table('production_labels')->whereIn('production_record_id', $chunk)->delete();
            ProductionRecord::query()->whereIn('id', $chunk)->delete();
        }

        $totalAfter = $this->totalPieces($partNumberIds);

        if ($totalAfter !== $totalBefore) {
            throw new \RuntimeException("el total no cuadra (antes {$totalBefore}, después {$totalAfter})");
        }

        $this->totals['records'] += $sources->count();
        $this->totals['labels'] += $labels;
        $this->totals['orders'] += (int) $toOrders->sum('quantity');
        $this->totals['accumulator'] += $leftover;

        $line = "{$number}: {$sources->count()} registros borrados ({$labels} etiquetas)";

        if ($produced > 0 || $scrap > 0) {
            $line .= " · {$produced} piezas";
            $line .= $scrap > 0 ? " y {$scrap} de scrap" : '';
            $line .= $toOrders->isNotEmpty()
                ? ' · a órdenes: ' . $toOrders->map(fn ($move) => "{$move['order']} +{$move['quantity']}")->implode(', ')
                : '';
            $line .= $accumulator ? " · al acumulador #{$accumulator->id}: +{$leftover}" : '';
        }

        if ($skipped->isNotEmpty()) {
            $line .= ' · sin tocar (En progreso o en Infor): #' . $skipped->pluck('id')->implode(', #');
        }

        $this->line($line);
    }

    /**
     * Rellena las órdenes abiertas de esta semana hasta PLAN_CUTOFF_DATE, de la
     * más antigua a la más nueva. Regresa cuánto recibió cada una.
     */
    protected function fillOpenOrders(PartNumber $partNumber, int $quantity): Collection
    {
        $moves = collect();

        $openOrders = ProductionRecord::query()
            ->where('part_number_id', $partNumber->id)
            ->whereBetween('planned_date', [Carbon::today()->startOfWeek()->toDateString(), ProductionRecord::PLAN_CUTOFF_DATE])
            ->whereNotNull('shop_order_number')
            ->where('shop_order_number', '<>', '')
            ->where('synced_to_infor', false)
            ->whereColumn('produced_quantity', '<', 'planned_quantity')
            ->whereNotIn('status_id', array_filter([optional($this->completedStatus)->id, optional($this->inProgressStatus)->id]))
            ->orderBy('planned_date')
            ->orderBy('shift_id')
            ->orderBy('id')
            ->get();

        foreach ($openOrders as $order) {
            if ($quantity <= 0) {
                break;
            }

            $transfer = min($quantity, (int) $order->planned_quantity - (int) $order->produced_quantity);
            $newProduced = (int) $order->produced_quantity + $transfer;

            $order->update([
                'produced_quantity' => $newProduced,
                'status_id' => $newProduced >= (int) $order->planned_quantity
                    ? $this->completedStatus->id
                    : ($this->stoppedStatus?->id ?? $order->status_id),
            ]);

            $quantity -= $transfer;
            $moves->push(['order' => $order->shop_order_number, 'quantity' => $transfer]);
        }

        return $moves;
    }

    /**
     * Suma al acumulador "No planeado" más antiguo de la parte (nunca a uno "En
     * progreso"); si no hay, crea uno en la fecha y turno actuales.
     */
    protected function addToAccumulator(PartNumber $partNumber, int $produced, int $scrap): ProductionRecord
    {
        $accumulator = ProductionRecord::query()
            ->where('part_number_id', $partNumber->id)
            ->where('status_id', $this->accumulatorStatus->id)
            ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
            ->where('planned_quantity', 0)
            ->where('produced_quantity', '>', 0)
            ->whereBetween('planned_date', [ProductionRecord::HISTORY_FLOOR_DATE, ProductionRecord::PLAN_CUTOFF_DATE])
            ->where(function ($query) {
                $query->whereNull('production_start')
                    ->orWhere('production_start', '>=', ProductionRecord::HISTORY_FLOOR_DATE);
            })
            ->orderBy('id')
            ->first();

        if ($accumulator !== null) {
            // scrap_quantity puede ser NULL y NULL + n da NULL en SQL Server.
            $accumulator->update([
                'produced_quantity' => (int) $accumulator->produced_quantity + $produced,
                'scrap_quantity' => (int) $accumulator->scrap_quantity + $scrap,
            ]);

            return $accumulator;
        }

        $now = Carbon::now();
        $shift = Shift::getShift($now);

        // El turno N antes de las 08:00 pertenece al día anterior.
        $date = $now->hour < 8 ? $now->copy()->subDay() : $now->copy();

        return ProductionRecord::create([
            'part_number_id' => $partNumber->id,
            'planned_date' => $date->min(Carbon::parse(ProductionRecord::PLAN_CUTOFF_DATE))->toDateString(),
            'shift_id' => $shift?->id,
            'planned_quantity' => 0,
            'produced_quantity' => $produced,
            'scrap_quantity' => $scrap,
            'shop_order_number' => null,
            'production_order' => $partNumber->production_order,
            'status_id' => $this->accumulatorStatus->id,
            'synced_to_infor' => false,
        ]);
    }

    protected function totalPieces(Collection $partNumberIds): int
    {
        return (int) ProductionRecord::query()
            ->whereIn('part_number_id', $partNumberIds)
            ->sum(DB::raw('COALESCE(produced_quantity, 0) + COALESCE(scrap_quantity, 0)'));
    }
}
