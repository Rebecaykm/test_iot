<?php

namespace App\Jobs;

use App\Models\ItemClass;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreItemClassJob implements ShouldQueue
{
    use Queueable;

    private $abbreviation;
    private $name;

    /**
     * Create a new job instance.
     */
    public function __construct($abbreviation, $name)
    {
        $this->abbreviation = $abbreviation;
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ItemClass::updateOrCreate(
            [
                'abbreviation' => $this->abbreviation,
            ],
            [
                'name' =>  $this->name,
            ],
        );
    }
}
