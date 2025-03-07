<?php

namespace App\Console\Commands;

use App\Jobs\GetWorkCenterJob;
use Illuminate\Console\Command;

class GetWorkCenter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:work-center';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtain the work centers registered in Infor to register them in the system\'s database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process GetWorkCenterJob is running at ". now());

        GetWorkCenterJob::dispatch();
    }
}
