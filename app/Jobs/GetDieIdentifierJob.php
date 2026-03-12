<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class GetDieIdentifierJob implements ShouldQueue
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
        $dieIdentifiers = DB::connection('infor-live')
            ->table('LX834F01.IIU')
            ->select([
                DB::raw('TRIM(IUPROD) AS partNumber'),
                DB::raw('TRIM(IUFD05) AS dieNumber'),
                DB::raw('IUFD11 AS piecesPerShot')
            ])
            ->where('IUSEQN', 2)
            ->whereRaw("TRIM(IUFD05) <> ''")
            ->get();

        foreach ($dieIdentifiers as $dieIdentifier) {
            StoreDieIdentifierJob::dispatch(
                $dieIdentifier->PARTNUMBER,
                $dieIdentifier->DIENUMBER,
                $dieIdentifier->PIECESPERSHOT
            );
        }
    }
}
