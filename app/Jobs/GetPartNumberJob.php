<?php

namespace App\Jobs;

use App\Models\IIM;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class GetPartNumberJob implements ShouldQueue
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

        $itemMaster = IIM::query()->select('IPROD AS partNumber', 'IDESC AS partName', 'IREF04 AS project', 'IMPLC AS isObsolete')->get();

        foreach ($itemMaster as $key => $item) {
            StorePartNumberJob::dispatch(
                preg_replace('/[^a-zA-Z0-9\/\-\s]/', '', $item->partNumber),
                preg_replace('/[^a-zA-Z0-9\/\-\s]/', '', $item->partName),
                $item->project,
                $item->isObsolete
            );
        }

        // $items = DB::connection('infor-live')
        //     ->table('LX834F01.FRT')
        //     ->select([
        //         'LX834F01.IIM.IPROD AS partNumber',
        //         'LX834F01.IIM.IDESC AS partName',
        //         'LX834F01.IIM.IREF04 AS project',
        //         'LX834F01.LWK.WWRKC AS workNumber',
        //         'LX834F01.LWK.WDESC AS workName',
        //         'LX834F01.IIM.IMPLC AS isObsolete',
        //         'LX834F01.FRT.RDDDT AS discontinueDate'
        //     ])
        //     ->join('LX834F01.IIM', 'LX834F01.IIM.IPROD', '=', 'LX834F01.FRT.RPROD')
        //     ->join('LX834F01.LWK', 'LX834F01.LWK.WWRKC', '=', 'LX834F01.FRT.RWRKC')
        //     ->whereIn('LX834F01.LWK.WWRKC', ['123140', '122070'])
        //     ->where([
        //         ['LX834F01.IIM.IMPLC', '!=', 'OBSOLETE'],
        //         ['LX834F01.FRT.RDDDT', '=', '99999999']
        //     ])
        //     ->get();

        // foreach ($items as $item) {
        //     StorePartNumberJob::dispatch(
        //         $item->partNumber,
        //         $item->partName,
        //         $item->project,
        //         $item->workNumber,
        //         $item->workName,
        //         $item->isObsolete,
        //         $item->discontinueDate
        //     );
        // }
    }
}
