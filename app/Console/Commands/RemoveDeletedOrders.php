<?php

namespace App\Console\Commands;

use App\Models\ProductionRecord;
use App\Models\Status;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Quita de IoT órdenes que se eliminaron en Infor.
 *
 * Lo producido (y el scrap) de cada registro pasa al acumulador "No planeado"
 * de su fecha y turno y el registro se descarta; los que no tienen producción
 * solo se descartan. Lo sincronizado con Infor y lo "En progreso" no se toca.
 * Si a la orden ya no le queda ningún registro, sus histories quedan sin
 * orden (igual que la producción que pasa por el acumulador).
 *
 * Después hay que correr iot:production-plan para que los acumuladores
 * llenen las órdenes que tengan faltante; lo que sobre se queda acumulado.
 */
class RemoveDeletedOrders extends Command
{
    protected $signature = 'iot:remove-deleted-orders
        {orders* : Números de orden eliminados en Infor (separados por espacio o coma)}
        {--dry-run : Hace todo dentro de una transacción y la revierte al final, sin guardar cambios}';

    protected $description = 'Quita de IoT órdenes eliminadas en Infor, pasando lo producido al acumulador.';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN: se muestra lo que se haría y se revierte, no se guarda ningún cambio.');
        }

        $orders = collect($this->argument('orders'))
            ->flatMap(fn ($value) => explode(',', $value))
            ->map(fn ($order) => trim($order))
            ->filter()
            ->unique()
            ->values();

        $inProgressId = (int) Status::where('name', 'LIKE', 'En progreso')->value('id');

        $records = ProductionRecord::query()
            ->with('partNumber')
            ->whereIn('shop_order_number', $orders)
            ->orderBy('planned_date')
            ->orderBy('id')
            ->get();

        $missing = $orders->diff($records->pluck('shop_order_number')->map(fn ($order) => trim($order)));

        if ($missing->isNotEmpty()) {
            $this->line('Sin registros en IoT: ' . $missing->implode(', '));
        }

        if ($records->isEmpty()) {
            $this->info('No hay nada que quitar.');
            return self::SUCCESS;
        }

        $from = substr((string) $records->min('planned_date'), 0, 10);

        foreach ($records->groupBy('part_number_id') as $partRecords) {
            $partNumber = $partRecords->first()->partNumber;
            $this->newLine();
            $this->info("{$partNumber->number} (#{$partNumber->id})");

            // Mismo candado que StoreProductionPlanJob, para no conciliar a la vez.
            $lock = Cache::lock('production-plan:' . trim((string) $partNumber->number), 300);

            try {
                $lock->block(60, function () use ($partRecords, $partNumber, $inProgressId, $dryRun) {
                    DB::beginTransaction();

                    try {
                        $entries = $this->processRecords($partRecords, $partNumber, $inProgressId);

                        if ($dryRun) {
                            DB::rollBack();
                        } else {
                            DB::commit();
                            // Solo después del commit, para no registrar algo que se revirtió.
                            foreach ($entries as [$message, $context]) {
                                Log::channel('deleted_orders')->info($message, $context);
                            }
                        }
                    } catch (Throwable $e) {
                        DB::rollBack();
                        $this->error("  error, no se guardó nada para este número. {$e->getMessage()}");
                    }
                });
            } catch (LockTimeoutException $e) {
                $this->warn('  otro proceso lo está conciliando, se omite. Vuelve a correr el comando más tarde.');
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? 'DRY RUN terminado, no se guardó nada.'
            : "Listo. Ahora corre: php artisan iot:production-plan --from={$from}");

        return self::SUCCESS;
    }

    /**
     * Devuelve las entradas para el log deleted_orders (los descartados sin producción no van).
     */
    protected function processRecords(Collection $records, $partNumber, int $inProgressId): array
    {
        $entries = [];

        foreach ($records as $record) {
            $label = $this->describe($record);
            $context = $this->logContext($record, $partNumber);

            if ($record->synced_to_infor) {
                $this->warn("  {$label}: ya está en Infor, se deja. Corregir en Infor.");
                $entries[] = ['[EN INFOR - SIN CAMBIO] Orden eliminada en Infor pero su producción ya se envió; corregir en Infor', $context];
                continue;
            }

            if ((int) $record->status_id === $inProgressId) {
                $this->warn("  {$label}: En progreso, se deja (el PLC puede estar escribiendo). Volver a correr cuando cambie.");
                $entries[] = ['[EN PROGRESO - SIN CAMBIO] Orden eliminada en Infor, se deja porque el PLC puede estar escribiendo', $context];
                continue;
            }

            if ((int) $record->produced_quantity > 0 || (int) $record->scrap_quantity > 0) {
                $producedQuantity = (int) $record->produced_quantity;
                $accumulator = ProductionRecord::moveToAccumulator($partNumber, $record);
                $this->line("  {$label}: {$producedQuantity} pzs pasan al acumulador #{$accumulator->id} y se descarta.");
                $entries[] = ['[AL ACUMULADOR] Orden eliminada en Infor, su producción pasa al acumulador', $context + ['Acumulador' => $accumulator->id]];
                continue;
            }

            ProductionRecord::discard($record, $partNumber);
            $this->line("  {$label}: sin producción, se descarta.");
        }

        // Solo las órdenes que ya no tienen registros: si alguno se quedó, sus histories siguen siendo suyos.
        foreach ($records->pluck('shop_order_number')->unique() as $order) {
            if (ProductionRecord::where('shop_order_number', $order)->exists()) {
                continue;
            }

            $updated = DB::table('histories')->where('shop_order_number', $order)->update(['shop_order_number' => null]);

            if ($updated > 0) {
                $this->line("  Orden {$order}: {$updated} histories quedan sin orden.");
                $entries[] = ['[HISTORIES] Quedan sin orden', ['Orden' => $order, 'Registros' => $updated]];
            }
        }

        return $entries;
    }

    /**
     * Datos del registro antes de tocarlo (moveToAccumulator lo deja en 0 o lo borra).
     */
    protected function logContext(ProductionRecord $record, $partNumber): array
    {
        return [
            'Orden' => $record->shop_order_number,
            'Registro' => $record->id,
            'Número de parte' => $partNumber->number,
            'Fecha planeada' => substr((string) $record->planned_date, 0, 10),
            'Turno' => $record->shift_id,
            'Cantidad planeada' => (int) $record->planned_quantity,
            'Cantidad producida' => (int) $record->produced_quantity,
            'Scrap' => (int) $record->scrap_quantity,
            'Sincronizado a Infor' => (bool) $record->synced_to_infor,
            'Fecha de sincronización' => $record->synced_at,
        ];
    }

    protected function describe(ProductionRecord $record): string
    {
        $plannedDate = substr((string) $record->planned_date, 0, 10);

        return "Orden {$record->shop_order_number} #{$record->id} ({$plannedDate}, turno #{$record->shift_id}, plan {$record->planned_quantity}, producido {$record->produced_quantity})";
    }
}
