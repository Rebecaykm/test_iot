<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductionRecordsLive;
use Illuminate\Console\Command;

class SyncProductionRecordsLiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:sync-production-records-live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Production record synchronization to Infor (Live environment)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncProductionRecordsLive::dispatch();
    }
}
