<?php

namespace App\Livewire;

use App\Models\PartNumber;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class VisualAidDisplay extends Component
{
    public $workCenter;
    public $visualAid;
    public $currentTime;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount($work_center)
    {
        $this->workCenter = $work_center;
        $this->refresh();
    }

    #[On('refresh')]
    public function refresh()
    {
        $this->currentTime = Carbon::now();
        $this->loadVisualAid();
    }

    public function loadVisualAid()
    {
        try {
            $currentShift = Shift::getShift($this->currentTime);

            $workCenter = WorkCenter::query()
                ->where('name', $this->workCenter)
                ->firstOrFail();

            $currentPartNumber = ProductionRecord::getWorkCenterProductionRecord(
                $workCenter->name,
                $currentShift->id,
                $this->currentTime
            )->first();

            if (!$currentPartNumber) {
                $this->visualAid = null;
                return;
            }

            $partNumber = PartNumber::query()
                ->where('number', $currentPartNumber->part_number)
                ->first();
            
            $this->visualAid = $partNumber?->visualAids()
                ->where('is_active', true)
                ->first();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, "Estación no encontrado");
        }
    }

    public function render()
    {
        return view('livewire.visual-aid-display');
    }
}
