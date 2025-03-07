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
    protected $signature = 'iot:production-plan';

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
        info("Process GetProductionPlanJob is running at " . now());

        GetProductionPlanJob::dispatch();
    }
}
