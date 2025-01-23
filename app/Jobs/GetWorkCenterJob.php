<?php

namespace App\Jobs;

use App\Models\LWK;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GetWorkCenterJob implements ShouldQueue
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
        $workCenters = LWK::query()->select('WWRKC AS workNumber', 'WDESC AS workName')->orderBy('WWRKC', 'ASC')->get();

        foreach ($workCenters as $workCenter) {
            StoreWorkCenterJob::dispatch(
                trim($workCenter->workNumber),
                trim($workCenter->workName)
            );
        }
    }
}
