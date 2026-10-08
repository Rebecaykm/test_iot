<?php

namespace App\Console\Commands;

use App\Jobs\GetProductionPlanJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GetProductionPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:production-plan
        {--from= : Fecha inicial del plan (Y-m-d). Nunca antes de HISTORY_FLOOR_DATE}
        {--to= : Fecha final del plan (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the daily production plan from Infor and inserts it into the system\'s database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $from = $this->option('from');
        $to = $this->option('to');

        foreach (['from' => $from, 'to' => $to] as $name => $value) {
            if ($value !== null && ! Carbon::canBeCreatedFromFormat($value, 'Y-m-d')) {
                $this->error("--{$name} debe tener formato Y-m-d (ej. 2026-09-21).");
                return self::FAILURE;
            }
        }

        if ($from !== null && $to !== null && $from > $to) {
            $this->error('--from no puede ser mayor que --to.');
            return self::FAILURE;
        }

        info("Process GetProductionPlanJob is running at " . now() . ($from || $to ? " (from: {$from}, to: {$to})" : ''));

        GetProductionPlanJob::dispatch($from, $to);

        return self::SUCCESS;
    }
}
