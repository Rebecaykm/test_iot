<?php

namespace App\Console\Commands;

use App\Jobs\GetPartShotsJob;
use Illuminate\Console\Command;

class GetPartShots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:part-shots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las piezas por golpe de los números de parte.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Process GetPartShotsJob is running at " . now());

        GetPartShotsJob::dispatch();

        info("Process GetPartShotsJob completed at " . now());

        /**
         * Query
         *
         * $part = App\Models\PartNumber::where('number', 'DA6A53/54917 -BK')->first();
         *
         * > $part->getAllCustomAttributes();
         * = [
         *     "pieces_per_shot" => "2",
         *   ]
         *
         * > $part->getCustomAttributeValue('pieces_per_shot');
         * = "2"
         */
    }
}
