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
    public string $workCenter;
    public ?string $visualAidPath = null;
    public ?string $visualAidAlt = null;
    public ?string $currentPartNumber = null;
    public bool $isProducing = false;
    public $currentTime;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount($work_center): void
    {
        $this->workCenter = $work_center;
        $this->refresh();
    }

    #[On('refresh')]
    public function refresh(): void
    {
        $this->currentTime = Carbon::now();
        $this->loadVisualAid();
    }

    public function loadVisualAid(): void
    {
        try {
            $currentShift = Shift::getShift($this->currentTime);

            if (!$currentShift) {
                $this->resetState();
                return;
            }

            WorkCenter::query()
                ->where('name', $this->workCenter)
                ->firstOrFail();

            $productionRecord = ProductionRecord::getWorkCenterProductionRecord(
                $this->workCenter,
                $currentShift->id,
                $this->currentTime
            )->first();

            if (!$productionRecord) {
                $this->resetState();
                return;
            }

            $this->isProducing = true;
            $this->currentPartNumber = $productionRecord->part_number;

            $partNumber = PartNumber::query()
                ->where('number', $productionRecord->part_number)
                ->first();

            $visualAid = $partNumber?->visualAids()
                ->where('is_active', true)
                ->first();

            // ✅ Solo guardamos strings primitivos, no el modelo
            $this->visualAidPath = $visualAid?->path ?? null;
            $this->visualAidAlt  = $visualAid?->alt_text ?? null;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Estación de trabajo no encontrada');
        }
    }

    private function resetState(): void
    {
        $this->isProducing      = false;
        $this->visualAidPath    = null;
        $this->visualAidAlt     = null;
        $this->currentPartNumber = null;
    }

    public function render()
    {
        return view('livewire.visual-aid-display');
    }
}
