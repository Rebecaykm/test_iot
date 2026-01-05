<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductionRecords;
use Illuminate\Console\Command;

class SyncProductionRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:sync-production-records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Production record synchronization to Infor';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process SyncProductionRecords is running at ". now());

        SyncProductionRecords::dispatch();

        info("Process SyncProductionRecords completed at ". now());
    }
}
