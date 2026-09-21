<?php

namespace App\Jobs;

use App\Models\ItemClass;
use App\Models\PartNumber;
use App\Models\Project;
use App\Models\StandardPack;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
    protected $routingMaster;

    /**
     * Create a new job instance.
     *
     * $routingMaster llega ya resuelto desde GetPartNumberJob (consultado en
     * lote junto con el resto del chunk) en vez de consultarse aquí por cada
     * número de parte, para no abrir una conexión a Infor por job.
     */
    public function __construct($partNumber, $partName, $itemClass, $project, $isObsolete, $standardPack, $quantityStandardPack, $routingMaster = null)
    {
        $this->partNumber =  $partNumber;
        $this->partName =  $partName;
        $this->itemClass =  $itemClass;
        $this->project =  $project;
        $this->isObsolete = $isObsolete;
        $this->standardPack =  $standardPack;
        $this->quantityStandardPack = $quantityStandardPack;
        $this->routingMaster = $routingMaster;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $itemClass = ItemClass::query()->where('abbreviation', $this->itemClass)->first();
        $standardPack = StandardPack::query()->where('name', $this->standardPack)->first();
        $isObsolete = $this->isObsolete == "OBSOLETE  ";

        $partNumber = PartNumber::storeFromInfor($this->partNumber, $this->partName, $itemClass, $isObsolete, $this->routingMaster);
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
