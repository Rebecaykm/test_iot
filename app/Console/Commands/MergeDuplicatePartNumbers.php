<?php

namespace App\Console\Commands;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Status;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Limpieza (manual) de números de parte duplicados y órdenes repetidas.
 *
 * Por cada número de parte el registro correcto es el más reciente (MAX id):
 *  1. Los production_records de los ids viejos (desde HISTORY_FLOOR_DATE, no
 *     sincronizados) pasan al correcto: los que tienen orden se mueven tal
 *     cual; los que no tienen orden y sí producción pasan al acumulador.
 *  2. Si una orden (shop_order_number) quedó repetida, se conserva la que está
 *     en Infor o, si ninguna, la de más producción (y luego la de id mayor).
 *     Lo producido de las demás pasa al acumulador y se descartan.
 *  3. Los acumuladores "No planeado" del correcto se juntan en uno solo.
 *  4. El resto de tablas pasa al correcto; los ids viejos que se quedan sin
 *     nada relacionado se borran y los que no, se marcan obsoletos.
 *
 * Lo sincronizado con Infor y lo "En progreso" (el PLC puede estar escribiendo)
 * no se toca. Después hay que correr iot:production-plan --from=HISTORY_FLOOR_DATE
 * para que los acumuladores rellenen las órdenes.
 */
class MergeDuplicatePartNumbers extends Command
{
    protected $signature = 'iot:merge-duplicate-part-numbers
        {numbers?* : Números de parte a corregir (vacío = todos los que tengan duplicados u órdenes repetidas)}
        {--dry-run : Hace todo dentro de una transacción y la revierte al final, sin guardar cambios}';

    protected $description = 'Junta los números de parte duplicados en el más reciente y deja una sola fila por orden, pasando lo producido de los repetidos al acumulador.';

    protected ?Status $inProgressStatus = null;

    protected ?Status $accumulatorStatus = null;

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN: se muestra lo que se haría y se revierte, no se guarda ningún cambio.');
        }

        $this->inProgressStatus = Status::where('name', 'LIKE', 'En progreso')->first();
        $this->accumulatorStatus = Status::where('name', 'LIKE', 'No planeado')->first();

        $numbers = $this->targetNumbers(collect($this->argument('numbers'))->map(fn ($number) => trim($number))->filter());

        if ($numbers->isEmpty()) {
            $this->info('No hay números de parte duplicados ni órdenes repetidas.');
            return self::SUCCESS;
        }

        $this->info("Números de parte a revisar: {$numbers->count()}");

        foreach ($numbers as $number) {
            $this->newLine();

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
                        $this->error("  {$number}: error, no se guardó nada para este número. {$e->getMessage()}");
                    }
                });
            } catch (LockTimeoutException $e) {
                $this->warn("{$number}: otro proceso lo está conciliando, se omite. Vuelve a correr el comando más tarde.");
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? 'DRY RUN terminado, no se guardó nada.'
            : 'Listo. Ahora corre: php artisan iot:production-plan --from=' . ProductionRecord::HISTORY_FLOOR_DATE);

        return self::SUCCESS;
    }

    /**
     * Números con más de un registro en part_numbers o con órdenes repetidas
     * desde HISTORY_FLOOR_DATE.
     */
    protected function targetNumbers(Collection $only): Collection
    {
        $duplicatedNumbers = PartNumber::query()
            ->when($only->isNotEmpty(), fn ($query) => $query->whereIn('number', $only))
            ->groupBy('number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('number');

        $numbersWithRepeatedOrders = ProductionRecord::query()
            ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->whereNotNull('production_records.shop_order_number')
            ->where('production_records.shop_order_number', '<>', '')
            ->where('production_records.planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->when($only->isNotEmpty(), fn ($query) => $query->whereIn('part_numbers.number', $only))
            ->groupBy('part_numbers.number', 'production_records.shop_order_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('part_numbers.number');

        return $duplicatedNumbers->merge($numbersWithRepeatedOrders)->unique()->sort()->values();
    }

    protected function processNumber(string $number): void
    {
        $partNumbers = PartNumber::query()->where('number', $number)->orderByDesc('id')->get();
        $target = $partNumbers->first();
        $oldIds = $partNumbers->slice(1)->pluck('id');

        $this->info($oldIds->isEmpty()
            ? "{$number}: #{$target->id} (sin duplicados, solo órdenes repetidas)"
            : "{$number}: correcto #{$target->id}, viejos #" . $oldIds->implode(', #'));

        if ($oldIds->isNotEmpty()) {
            $this->moveOldProductionRecords($target, $oldIds);
        }

        $this->mergeRepeatedOrders($target, $partNumbers->pluck('id'));
        $this->mergeAccumulators($target);

        if ($oldIds->isNotEmpty()) {
            $this->retireOldPartNumbers($target, $oldIds);
        }
    }

    /**
     * Paso 1: production_records de los ids viejos al correcto.
     */
    protected function moveOldProductionRecords(PartNumber $target, Collection $oldIds): void
    {
        $records = ProductionRecord::query()
            ->whereIn('part_number_id', $oldIds)
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->where('synced_to_infor', false)
            ->orderBy('planned_date')
            ->orderBy('id')
            ->get();

        foreach ($records as $record) {
            $label = $this->describe($record);

            if ($this->isInProgress($record)) {
                $this->warn("  {$label}: En progreso, se deja en el id viejo (el PLC puede estar escribiendo).");
                continue;
            }

            if (empty($record->shop_order_number) && (int) $record->produced_quantity > 0) {
                $producedQuantity = (int) $record->produced_quantity;
                $accumulator = ProductionRecord::moveToAccumulator($target, $record);
                $this->line("  {$label}: sin orden, {$producedQuantity} pzs pasan al acumulador #{$accumulator->id}.");
                continue;
            }

            $record->update(['part_number_id' => $target->id]);
            $this->line("  {$label}: se mueve al id #{$target->id}.");
        }

        $skipped = ProductionRecord::query()
            ->whereIn('part_number_id', $oldIds)
            ->where(fn ($query) => $query->where('synced_to_infor', true)->orWhere('planned_date', '<', ProductionRecord::HISTORY_FLOOR_DATE))
            ->count();

        if ($skipped > 0) {
            $this->line("  {$skipped} registros sincronizados con Infor o anteriores a " . ProductionRecord::HISTORY_FLOOR_DATE . ' se quedan en los ids viejos.');
        }
    }

    /**
     * Paso 2: una sola fila por orden.
     */
    protected function mergeRepeatedOrders(PartNumber $target, Collection $partNumberIds): void
    {
        $repeatedOrders = ProductionRecord::query()
            ->whereIn('part_number_id', $partNumberIds)
            ->whereNotNull('shop_order_number')
            ->where('shop_order_number', '<>', '')
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->groupBy('shop_order_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('shop_order_number');

        foreach ($repeatedOrders as $shopOrderNumber) {
            $records = ProductionRecord::query()
                ->whereIn('part_number_id', $partNumberIds)
                ->where('shop_order_number', $shopOrderNumber)
                ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
                ->orderByDesc('synced_to_infor')
                ->orderByDesc('produced_quantity')
                ->orderByDesc('id')
                ->get();

            $keeper = $records->first();
            $this->line("  Orden {$shopOrderNumber}: {$records->count()} registros, se conserva {$this->describe($keeper)}.");

            foreach ($records->slice(1) as $record) {
                $label = $this->describe($record);

                if ($record->synced_to_infor) {
                    $this->warn("    {$label}: ya está en Infor, se deja.");
                    continue;
                }

                if ($this->isInProgress($record)) {
                    $this->warn("    {$label}: En progreso, se deja (el PLC puede estar escribiendo). Revisar manualmente.");
                    continue;
                }

                if ((int) $record->produced_quantity > 0 || (int) $record->scrap_quantity > 0) {
                    $producedQuantity = (int) $record->produced_quantity;
                    $accumulator = ProductionRecord::moveToAccumulator($target, $record);
                    $this->line("    {$label}: {$producedQuantity} pzs pasan al acumulador #{$accumulator->id} y se descarta.");
                    continue;
                }

                ProductionRecord::discard($record, $target, $keeper);
                $this->line("    {$label}: sin producción, se descarta.");
            }
        }
    }

    /**
     * Paso 3: un solo acumulador "No planeado" (StoreProductionPlanJob usa el
     * más antiguo). Los "En progreso" no se juntan.
     */
    protected function mergeAccumulators(PartNumber $target): void
    {
        if (! $this->accumulatorStatus) {
            return;
        }

        $accumulators = ProductionRecord::query()
            ->where('part_number_id', $target->id)
            ->where('status_id', $this->accumulatorStatus->id)
            ->where(fn ($query) => $query->whereNull('shop_order_number')->orWhere('shop_order_number', ''))
            ->where('planned_quantity', 0)
            ->where('produced_quantity', '>', 0)
            ->where('planned_date', '>=', ProductionRecord::HISTORY_FLOOR_DATE)
            ->where(function ($query) {
                $query->whereNull('production_start')
                    ->orWhere('production_start', '>=', ProductionRecord::HISTORY_FLOOR_DATE);
            })
            ->orderBy('id')
            ->get();

        foreach ($accumulators->slice(1) as $record) {
            $accumulator = ProductionRecord::moveToAccumulator($target, $record);
            $this->line("  Acumulador {$this->describe($record)}: se junta con el acumulador #{$accumulator->id}.");
        }
    }

    /**
     * Paso 4: el resto de tablas al correcto; los ids viejos se borran o, si
     * aún tienen algo relacionado, se marcan obsoletos.
     */
    protected function retireOldPartNumbers(PartNumber $target, Collection $oldIds): void
    {
        foreach (['histories', 'scrap_records', 'production_order_histories'] as $table) {
            DB::table($table)->whereIn('part_number_id', $oldIds)->update(['part_number_id' => $target->id]);
        }

        DB::table('production_labels')->whereIn('product_id', $oldIds)->update(['product_id' => $target->id]);

        // Pivotes con índice único: solo se mueve lo que el correcto no tenga.
        foreach (DB::table('part_number_project')->whereIn('part_number_id', $oldIds)->get() as $row) {
            if (DB::table('part_number_project')->where('part_number_id', $target->id)->where('project_id', $row->project_id)->exists()) {
                DB::table('part_number_project')->where('id', $row->id)->delete();
            } else {
                DB::table('part_number_project')->where('id', $row->id)->update(['part_number_id' => $target->id]);
            }
        }

        foreach (['parent_part_number_id' => 'child_part_number_id', 'child_part_number_id' => 'parent_part_number_id'] as $column => $otherColumn) {
            foreach (DB::table('part_number_relations')->whereIn($column, $oldIds)->get() as $row) {
                $alreadyExists = (int) $row->{$otherColumn} === (int) $target->id
                    || DB::table('part_number_relations')->where($column, $target->id)->where($otherColumn, $row->{$otherColumn})->exists();

                $alreadyExists
                    ? DB::table('part_number_relations')->where('id', $row->id)->delete()
                    : DB::table('part_number_relations')->where('id', $row->id)->update([$column => $target->id]);
            }
        }

        // Ayudas visuales: solo si el correcto no tiene ninguna, para no dejarle dos activas.
        if (! DB::table('visual_aids')->where('part_number_id', $target->id)->exists()) {
            DB::table('visual_aids')->whereIn('part_number_id', $oldIds)->update(['part_number_id' => $target->id]);
        }

        // Atributos: solo las claves que el correcto no tenga (índice único por clave).
        $targetKeys = $target->customAttributes()->pluck('key');
        DB::table('attributes')
            ->where('attributable_type', PartNumber::class)
            ->whereIn('attributable_id', $oldIds)
            ->whereNotIn('key', $targetKeys)
            ->orderBy('id')
            ->get()
            ->unique('key')
            ->each(fn ($row) => DB::table('attributes')->where('id', $row->id)->update(['attributable_id' => $target->id]));

        foreach ($oldIds as $oldId) {
            $references = collect([
                'production_records' => DB::table('production_records')->where('part_number_id', $oldId)->count(),
                'visual_aids' => DB::table('visual_aids')->where('part_number_id', $oldId)->count(),
                'attributes' => DB::table('attributes')->where('attributable_type', PartNumber::class)->where('attributable_id', $oldId)->count(),
            ])->filter();

            if ($references->isEmpty()) {
                PartNumber::query()->whereKey($oldId)->delete();
                $this->line("  Número de parte viejo #{$oldId}: sin registros relacionados, se elimina.");
            } else {
                PartNumber::query()->whereKey($oldId)->update(['is_obsolete' => true]);
                $detail = $references->map(fn ($count, $table) => "{$table}: {$count}")->implode(', ');
                $this->line("  Número de parte viejo #{$oldId}: aún tiene registros ({$detail}), se marca obsoleto.");
            }
        }
    }

    protected function isInProgress(ProductionRecord $record): bool
    {
        return $this->inProgressStatus !== null && (int) $record->status_id === (int) $this->inProgressStatus->id;
    }

    protected function describe(ProductionRecord $record): string
    {
        $plannedDate = substr((string) $record->planned_date, 0, 10);

        return "#{$record->id} ({$plannedDate}, turno #{$record->shift_id}, plan {$record->planned_quantity}, producido {$record->produced_quantity}"
            . ($record->synced_to_infor ? ', en Infor' : '')
            . ')';
    }
}
