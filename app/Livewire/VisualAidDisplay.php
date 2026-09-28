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

    /** MID (troquel) del número de parte en producción; null si la parte no lo tiene registrado */
    public ?string $currentMid = null;
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
            WorkCenter::query()
                ->where('name', $this->workCenter)
                ->firstOrFail();

            // Igual que /press-production: el registro "En progreso" (estatus 7) de la estación,
            // sin importar el día ni el turno en que se planeó. Antes se buscaba solo en el turno
            // actual, y si se producía material de otro turno la vista decía "Sin producción".
            $productionRecord = cache()->remember(
                "visual_aid_in_progress_{$this->workCenter}",
                ProductionRecord::PRODUCTION_CACHE_TTL,
                fn () => ProductionRecord::query()
                    ->select(['part_numbers.id AS part_number_id', 'part_numbers.number AS part_number'])
                    ->join('part_numbers', 'production_records.part_number_id', '=', 'part_numbers.id')
                    ->join('work_centers', 'part_numbers.work_center_id', '=', 'work_centers.id')
                    ->where('work_centers.name', 'LIKE', $this->workCenter)
                    ->where('production_records.status_id', 7)
                    ->orderBy('part_numbers.production_order')
                    ->orderBy('part_numbers.number') // desempate: mismo orden que /press-production
                    ->first()
            );

            if (!$productionRecord) {
                $this->resetState();
                return;
            }

            $this->isProducing = true;
            $this->currentPartNumber = $productionRecord->part_number;

            // Por id (no por número): el mismo número de parte puede existir en otra estación
            $partNumber = PartNumber::find($productionRecord->part_number_id);

            $this->currentMid = $partNumber?->getCustomAttributeValue('mid') ?: null;

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
        $this->currentMid        = null;
    }

    public function render()
    {
        return view('livewire.visual-aid-display');
    }
}
