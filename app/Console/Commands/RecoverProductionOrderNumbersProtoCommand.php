<?php

namespace App\Console\Commands;

use App\Jobs\RecoverProductionOrderNumbersProto;
use Illuminate\Console\Command;

class RecoverProductionOrderNumbersProtoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:recover-production-order-numbers-proto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover shop order numbers assigned by Infor for production records (Proto environment)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching RecoverProductionOrderNumbersProto job...');
        RecoverProductionOrderNumbersProto::dispatch();
        $this->info('Job dispatched RecoverProductionOrderNumbersProto successfully.');
    }
}
