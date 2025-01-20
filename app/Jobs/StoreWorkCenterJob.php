<?php

namespace App\Jobs;

use App\Models\WorkCenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class StoreWorkCenterJob implements ShouldQueue
{
    use Queueable;

    protected $number;
    protected $name;

    /**
     * Create a new job instance.
     */
    public function __construct($number, $name)
    {
        $this->number = $number;
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        WorkCenter::updateOrCreate(
            [
                'number' => $this->number
            ],
            [
                'name' => $this->name
            ]
        );
    }
}
