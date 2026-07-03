<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductionRecordsProto;
use Illuminate\Console\Command;

class SyncProductionRecordsProtoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:sync-production-records-proto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Production record synchronization to Infor (Proto environment)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncProductionRecordsProto::dispatch();
    }
}
