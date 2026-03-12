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
    protected $piecesPerShot;

    /**
     * Create a new job instance.
     */
    public function __construct($partNumber, $dieNumber, $piecesPerShot)
    {
        $this->partNumber    = $partNumber;
        $this->dieNumber     = $dieNumber;
        $this->piecesPerShot = $piecesPerShot;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $part = PartNumber::where('number', $this->partNumber)->first();

        if ($part) {
            Log::debug("StoreDieIdentifierJob", [
                'partNumber'    => $this->partNumber,
                'dieNumber'     => $this->dieNumber,
                'piecesPerShot' => $this->piecesPerShot,
            ]);

            if ($part->getCustomAttributeValue('mid') != $this->dieNumber) {
                $part->setCustomAttributeValue('mid', $this->dieNumber);
            }

            $piecesValue = (int) $this->piecesPerShot === 0 ? 1 : (int) $this->piecesPerShot;

            if ($part->getCustomAttributeValue('pieces_per_shot') != $piecesValue) {
                $part->setCustomAttributeValue('pieces_per_shot', $piecesValue);
            }
        }
    }
}
