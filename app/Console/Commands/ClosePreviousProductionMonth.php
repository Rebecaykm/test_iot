<?php

namespace App\Console\Commands;

use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cierra el mes inmediato anterior al mes operativo (el turno N antes de las
 * 08:00 es del día anterior). Los registros que quedaron abiertos (Pendiente,
 * En progreso, Detenido o No planeado) y no se enviaron a Infor:
 *
 *  - Con orden y producción menor al plan → Incompleto (se envía a Infor).
 *  - Con orden y producción igual o mayor al plan → Completado (se envía a Infor).
 *  - Con orden y sin producción → Expirado (no se produjo).
 *  - Sin orden (acumulado: no se planeó y se sobreprodujo) → Expirado.
 *
 * Cada registro se queda en su fecha, turno y cantidades; solo cambia el estado.
 * Se puede correr cualquier día: si el mes anterior ya está cerrado no hace nada.
 */
class ClosePreviousProductionMonth extends Command
{
    protected $signature = 'iot:close-previous-month {--dry-run : Muestra lo que se cambiaría sin guardar}';

    protected $description = 'Cierra el mes anterior: las órdenes abiertas pasan a Incompleto, Completado o Expirado y los acumulados a Expirado.';

    public function handle()
    {
        $currentShift = Shift::getCurrentShiftInfo(Carbon::now());

        if ($currentShift->shift === null) {
            $this->warn('No se pudo determinar el turno actual; no se cierra el mes.');
            return self::FAILURE;
        }

        $statuses = Status::whereIn('name', ['Pendiente', 'En progreso', 'Detenido', 'No planeado', 'Completado', 'Incompleto', 'Expirado'])
            ->pluck('id', 'name');

        foreach (['Completado', 'Incompleto', 'Expirado'] as $required) {
            if (!$statuses->has($required)) {
                $this->error("No existe el estado \"{$required}\"; no se cierra el mes.");
                return self::FAILURE;
            }
        }

        $openStatusIds = $statuses->only(['Pendiente', 'En progreso', 'Detenido', 'No planeado'])->values()->all();
        $previousMonth = $currentShift->date->copy()->startOfMonth()->subMonth();
        $from = ProductionRecord::applyHistoryFloor($previousMonth->copy())->toDateString();
        $to = $previousMonth->copy()->endOfMonth()->toDateString();

        if ($from > $to) {
            $this->info("El mes {$previousMonth->format('Y-m')} es anterior a " . ProductionRecord::HISTORY_FLOOR_DATE . '; no hay nada que cerrar.');
            return self::SUCCESS;
        }

        $records = ProductionRecord::query()
            ->whereIn('status_id', $openStatusIds)
            ->where('synced_to_infor', false)
            ->whereBetween('planned_date', [$from, $to])
            ->orderBy('id')
            ->get(['id', 'shop_order_number', 'planned_quantity', 'produced_quantity']);

        $hasOrder = fn (ProductionRecord $record) => trim((string) $record->shop_order_number) !== '';

        $changes = [
            'Incompleto' => $records->filter(fn ($r) => $hasOrder($r) && (int) $r->produced_quantity > 0 && (int) $r->produced_quantity < (int) $r->planned_quantity),
            'Completado' => $records->filter(fn ($r) => $hasOrder($r) && (int) $r->planned_quantity > 0 && (int) $r->produced_quantity >= (int) $r->planned_quantity),
        ];
        $changes['Expirado'] = $records->diffKeys($changes['Incompleto'])->diffKeys($changes['Completado']);

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Cierre del mes {$previousMonth->format('Y-m')} ({$from} a {$to}): {$records->count()} registros abiertos.");

        foreach ($changes as $statusName => $group) {
            $this->line("  {$statusName}: {$group->count()}");
        }

        if ($dryRun || $records->isEmpty()) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes, $statuses) {
            foreach ($changes as $statusName => $group) {
                // SQL Server acepta máximo 2100 parámetros por consulta.
                foreach ($group->pluck('id')->chunk(1000) as $chunk) {
                    ProductionRecord::query()->whereIn('id', $chunk->all())->update(['status_id' => $statuses[$statusName]]);
                }
            }
        });

        foreach ($changes as $statusName => $group) {
            $this->logIds($previousMonth, $statusName, $group);
        }

        return self::SUCCESS;
    }

    protected function logIds(Carbon $month, string $statusName, Collection $group): void
    {
        if ($group->isNotEmpty()) {
            Log::info("[iot:close-previous-month] {$month->format('Y-m')}: {$group->count()} registros pasaron a {$statusName}: #" . $group->pluck('id')->implode(', #'));
        }
    }
}
