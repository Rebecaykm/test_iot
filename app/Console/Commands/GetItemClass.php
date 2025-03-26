<?php

namespace App\Console\Commands;

use App\Jobs\GetItemClassJob;
use Illuminate\Console\Command;

class GetItemClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:item-class';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtain the item classes registered in Infor and register them in the system\'s database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process GetItemClassJob is running at ". now());

        GetItemClassJob::dispatch();
    }
}
