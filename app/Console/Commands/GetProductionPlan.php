<?php

namespace App\Console\Commands;

use App\Jobs\GetProductionPlanJob;
use Illuminate\Console\Command;

class GetProductionPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets today\'s production plan and inserts it into the system\'s database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process running at ". now());

        GetProductionPlanJob::dispatch();
    }
}
