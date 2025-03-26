<?php

namespace App\Jobs;

use App\Models\IIC;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GetItemClassJob implements ShouldQueue
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
        $itemClasses = IIC::query()
            ->select('IID', 'ICLAS', 'ICDES')
            ->orderBy('ICLAS', 'ASC')
            ->get();

        foreach ($itemClasses as $itemClass) {
            StoreItemClassJob::dispatch(
                trim($itemClass->ICLAS),
                trim($itemClass->ICDES)
            );
        }
    }
}
