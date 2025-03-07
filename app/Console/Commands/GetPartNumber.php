<?php

namespace App\Console\Commands;

use App\Jobs\GetPartNumberJob;
use Illuminate\Console\Command;

class GetPartNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:part-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtain the part numbers registered in Infor to register them in the system\'s database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process GetPartNumberJob is running at ". now());

        GetPartNumberJob::dispatch();
    }
}
