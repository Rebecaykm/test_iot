<?php

namespace App\Jobs;

use App\Models\ItemClass;
use App\Models\PartNumber;
use App\Models\Project;
use App\Models\StandardPack;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorePartNumberJob implements ShouldQueue
{
    use Queueable;

    protected $partNumber;
    protected $partName;
    protected $itemClass;
    protected $project;
    protected $isObsolete;
    protected $standardPack;
    protected $quantityStandardPack;

    /**
     * Create a new job instance.
     */
    public function __construct($partNumber, $partName, $itemClass, $project, $isObsolete, $standardPack, $quantityStandardPack)
    {
        $this->partNumber =  $partNumber;
        $this->partName =  $partName;
        $this->itemClass =  $itemClass;
        $this->project =  $project;
        $this->isObsolete = $isObsolete;
        $this->standardPack =  $standardPack;
        $this->quantityStandardPack = $quantityStandardPack;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $itemClass = ItemClass::query()->where('abbreviation', $this->itemClass)->first();
        $standardPack = StandardPack::query()->where('name', $this->standardPack)->first();
        $isObsolete = $this->isObsolete == "OBSOLETE  ";
        $facility = "YK1";

        $routingMaster = DB::connection('infor-live')
            ->table('LX834F01.FRT')
            ->select([
                'LX834F01.LWK.WWRKC AS workNumber',
                'LX834F01.LWK.WDESC AS workName',
                'LX834F01.FRT.RLAB AS productionRate',
                'LX834F01.FRT.RTWHS AS facility'
            ])
            ->join('LX834F01.IIM', 'LX834F01.IIM.IPROD', '=', 'LX834F01.FRT.RPROD')
            ->join('LX834F01.LWK', 'LX834F01.LWK.WWRKC', '=', 'LX834F01.FRT.RWRKC')
            ->where('LX834F01.IIM.IPROD', '=', $this->partNumber)
            ->where('LX834F01.FRT.RTWHS', '=', $facility)
            ->first();

        $partNumber = PartNumber::storeFromInfor($this->partNumber, $this->partName, $itemClass, $isObsolete, $routingMaster);
        $partNumber->update([
            'standard_pack_id' => $standardPack ? $standardPack->id : null,
            'standard_pack_quantity' => $this->quantityStandardPack ?? null,
        ]);
        Log::info("StorePartNumberJob.- Guardado No. Parte : {$partNumber->number}, Nombre : {$partNumber->name}");
        $this->syncProjects($partNumber);
    }

    /**
     * Asocia el número de parte con sus proyectos según el código IREF04 de Infor.
     */
    protected function syncProjects(PartNumber $partNumber): void
    {
        if (! Project::syncPartNumberByInforCode($partNumber, $this->project)) {
            // Log::notice("StorePartNumberJob.- Sin Proyecto No. Parte : {$this->partNumber}, Proyecto : {$this->project}");
        }
    }
}
