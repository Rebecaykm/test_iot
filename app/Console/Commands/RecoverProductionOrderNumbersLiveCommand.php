<?php

namespace App\Console\Commands;

use App\Jobs\RecoverProductionOrderNumbersLive;
use Illuminate\Console\Command;

class RecoverProductionOrderNumbersLiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:recover-production-order-numbers-live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover shop order numbers assigned by Infor for production records (Live environment)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        RecoverProductionOrderNumbersLive::dispatch();
    }
}
