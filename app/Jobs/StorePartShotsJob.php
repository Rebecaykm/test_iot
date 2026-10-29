<?php

namespace App\Jobs;

use App\Models\PartNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StorePartShotsJob implements ShouldQueue
{
    use Queueable;

    protected $partNumber;
    protected $piecesPerShot;

    /**
     * Create a new job instance.
     */
    public function __construct($partNumber, $piecesPerShot)
    {
        $this->partNumber = $partNumber;
        $this->piecesPerShot = $piecesPerShot;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $part = PartNumber::where('number', $this->partNumber)->orderByDesc('id')->first();

        if ($part) {
            $currentValue = $part->getCustomAttributeValue('pieces_per_shot');

            if ($currentValue != $this->piecesPerShot) {
                $part->setCustomAttributeValue('pieces_per_shot', $this->piecesPerShot);
            }
        }
    }
}
