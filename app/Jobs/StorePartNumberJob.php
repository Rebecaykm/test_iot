<?php

namespace App\Jobs;

use App\Models\PartNumber;
use App\Models\Project;
use App\Models\WorkCenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorePartNumberJob implements ShouldQueue
{
    use Queueable;

    protected $partNumber;
    protected $partName;
    protected $project;
    protected $isObsolete;

    /**
     * Create a new job instance.
     */
    public function __construct($partNumber, $partName, $project, $isObsolete)
    {
        $this->partNumber =  $partNumber;
        $this->partName =  $partName;
        $this->project =  $project;
        $this->isObsolete = $isObsolete;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $partNumber = PartNumber::query()->where([['number', $this->partNumber], ['name', $this->partName]])->first();

        if ($partNumber !== null) {
            $partNumber->update([
                'number' => $this->partNumber,
                'name' => $this->partName,
                'obsolete' => ($this->isObsolete == "OBSOLETE") ? true : false,
            ]);
        } else {
            $partNumber = PartNumber::create([
                'number' => $this->partNumber,
                'name' => $this->partName,
                'is_obsolete' => ($this->isObsolete == "OBSOLETE") ? true : false,
            ]);
        }

        $routingMaster = DB::connection('infor-live')
            ->table('LX834F01.FRT')
            ->select([
                'LX834F01.IIM.IPROD AS partNumber',
                'LX834F01.IIM.IDESC AS partName',
                'LX834F01.LWK.WWRKC AS workNumber',
                'LX834F01.LWK.WDESC AS workName',
                'LX834F01.IIM.IREF04 AS project',
                'LX834F01.FRT.RLAB AS productionRate',
                'LX834F01.IIM.IMPLC AS isObsolete',
            ])
            ->join('LX834F01.IIM', 'LX834F01.IIM.IPROD', '=', 'LX834F01.FRT.RPROD')
            ->join('LX834F01.LWK', 'LX834F01.LWK.WWRKC', '=', 'LX834F01.FRT.RWRKC')
            ->where('LX834F01.IIM.IPROD', '=', $partNumber->number)
            ->first();

        if ($routingMaster !== null) {
            $workCenter = WorkCenter::query()->where([['number', $routingMaster->workNumber], ['name', $routingMaster->workName]])->first();

            $partNumber->update([
                'work_center_id' => $workCenter->id,
                'production_rate' => $routingMaster->productionRate
            ]);
        }

        switch (trim($this->project)) {
            case '1':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '2':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '3':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '4':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '5':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '7':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '8':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '9':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '10':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '11':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '3Y':
                $project = Project::where('type', '3Y')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '20':
                $project = Project::where('type', trim($this->project))->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '12':
                $project = Project::where('type', '1')->orWhere('type', '2')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '123':
                $project = Project::where('type', '1')->orWhere('type', '2')->orWhere('type', '3')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '13':
                $project = Project::where('type', '1')->orWhere('type', '3')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '23':
                $project = Project::where('type', '2')->orWhere('type', '3')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '45':
                $project = Project::where('type', '4')->orWhere('type', '5')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '56':
                $project = Project::where('type', '5')->orWhere('type', '6')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '710':
                $project = Project::where('type', '7')->orWhere('type', '10')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '79':
                $project = Project::where('type', '7')->orWhere('type', '9')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '47':
                $project = Project::where('type', '4')->orWhere('type', '7')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '57':
                $project = Project::where('type', '5')->orWhere('type', '7')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            case '811':
                $project = Project::where('type', '8')->orWhere('type', '11')->pluck('id')->toArray();
                $partNumber->projects()->sync($project);
                break;

            default:
                Log::notice("StorePartNumberJob.- Sin Proyecto No. Parte : " . $this->partNumber . ", Proyecto : " . $this->project);
                break;
        }
    }
}
