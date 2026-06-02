<?php

namespace App\Console\Commands;

use App\Jobs\GetStandardPackJob;
use Illuminate\Console\Command;

class GetStandardPack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:standard-pack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The standard packs are retrieved from Infor and stored in the system\'s database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process GetStandardPackJob is running at ". now());

        GetStandardPackJob::dispatch();

        info("Process GetStandardPackJob completed at ". now());
    }
}
