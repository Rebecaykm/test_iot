<?php

namespace App\Jobs;

use App\Models\PartNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        $part = PartNumber::where('number', $this->partNumber)->orderByDesc('id')->first();

        if ($part) {
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
