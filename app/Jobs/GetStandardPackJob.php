<?php

namespace App\Jobs;

use App\Models\IIM;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GetStandardPackJob implements ShouldQueue
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
        $standarPacks = IIM::query()
            ->select('IMSPKT')
            ->groupBy('IMSPKT')
            ->orderBy('IMSPKT', 'ASC')
            ->get();

        foreach ($standarPacks as $key => $standarPack) {
            StoreStandardPackJob::dispatch(
                trim($standarPack->IMSPKT)
            );
        }
    }
}
