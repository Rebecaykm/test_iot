<?php

namespace App\Console\Commands;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mueve los production_records de los registros duplicados (viejos) de un
 * número de parte al registro más reciente, que es el que tiene la estación
 * correcta. Después hay que correr iot:production-plan para que el plan se
 * concilie con el acumulador del registro nuevo.
 */
class MovePartNumberRecords extends Command
{
    protected $signature = 'iot:move-part-number-records
        {numbers* : Números de parte a corregir}
        {--from=2026-09-21 : Solo mueve production_records con planned_date desde esta fecha (Y-m-d)}
        {--dry-run : Solo muestra lo que se movería, sin guardar cambios}';

    protected $description = 'Mueve los production_records de los registros duplicados de un número de parte al registro más reciente y marca los viejos como obsoletos.';

    public function handle()
    {
        $from = $this->option('from');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN: no se guardará ningún cambio.');
        }

        foreach ($this->argument('numbers') as $number) {
            $partNumbers = PartNumber::query()
                ->with('workCenter')
                ->where('number', $number)
                ->orderByDesc('id')
                ->get();

            if ($partNumbers->count() < 2) {
                $this->line("{$number}: no está duplicado, se omite.");
                continue;
            }

            $target = $partNumbers->first();
            $oldIds = $partNumbers->slice(1)->pluck('id')->all();

            $records = ProductionRecord::query()
                ->whereIn('part_number_id', $oldIds)
                ->where('planned_date', '>=', $from)
                ->orderBy('planned_date')
                ->get(['id', 'part_number_id', 'planned_date', 'shop_order_number', 'planned_quantity', 'produced_quantity']);

            $this->newLine();
            $this->info("{$number}: destino #{$target->id} ({$target->workCenter?->name}), viejos #" . implode(', #', $oldIds));
            $this->line("  production_records a mover desde {$from}: {$records->count()} (plan total: {$records->sum('planned_quantity')}, producido total: {$records->sum('produced_quantity')})");

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($records, $target, $oldIds) {
                ProductionRecord::query()
                    ->whereIn('id', $records->pluck('id'))
                    ->update(['part_number_id' => $target->id]);

                PartNumber::query()->whereIn('id', $oldIds)->update(['is_obsolete' => true]);
            });

            $this->line("  Movidos. Registros #" . implode(', #', $oldIds) . " marcados como obsoletos.");
        }

        return self::SUCCESS;
    }
}
