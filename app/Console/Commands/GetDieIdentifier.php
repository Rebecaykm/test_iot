<?php

namespace App\Console\Commands;

use App\Jobs\GetDieIdentifierJob;
use Illuminate\Console\Command;

class GetDieIdentifier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:die-identifier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process GetDieIdentifier is running at " . now());

        GetDieIdentifierJob::dispatch();

        info("Process GetDieIdentifier completed at " . now());
    }
}
