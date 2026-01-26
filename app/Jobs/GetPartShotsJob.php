<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetPartShotsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $items = DB::connection('infor-live')
            ->table('LX834F01.IIU')
            ->select([
                DB::raw('TRIM(IUPROD) AS partNumber'),
                'IUFD11 AS piecesPerShot'
            ])
            ->where('IUSEQN', 1)
            ->where('IUFD11', '>', 0)
            ->get();


        foreach ($items as $item) {
            StorePartShotsJob::dispatch(
                $item->PARTNUMBER,
                $item->piecesPerShot
            );
        }
    }
}
