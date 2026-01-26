<?php

namespace App\Jobs;

use App\Models\PartNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class StoreDieIdentifierJob implements ShouldQueue
{
    use Queueable;

    protected $partNumber;
    protected $dieNumber;

    /**
     * Create a new job instance.
     */
    public function __construct($partNumber, $dieNumber)
    {
        $this->partNumber = $partNumber;
        $this->dieNumber = $dieNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $part = PartNumber::where('number', $this->partNumber)->first();

        if ($part) {
            Log::debug("GetDieIdentifierJob", ['partNumber' => $this->partNumber, 'dieNumber' => $this->dieNumber]);
            $currentValue = $part->getCustomAttributeValue('mid');

            if ($currentValue != $this->dieNumber) {
                $part->setCustomAttributeValue('mid', $this->dieNumber);
            }
        }
    }
}
