<?php

namespace App\Jobs;

use App\Models\StandardPack;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreStandardPackJob implements ShouldQueue
{
    use Queueable;

    private string $name;

    /**
     * Create a new job instance.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        StandardPack::firstOrCreate(['name' => $this->name]);
    }
}
