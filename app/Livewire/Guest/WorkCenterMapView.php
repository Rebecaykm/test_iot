<?php

namespace App\Livewire\Guest;

use App\Models\Line;
use App\Models\WorkCenter;
use Livewire\Component;

class WorkCenterMapView extends Component
{
    public $lines = [];
    public $selectedLine = null;
    public $zoomLevel = 1;

    public function mount()
    {
        $this->lines = Line::all();
    }

    public function getWorkCentersProperty()
    {
        return WorkCenter::query()
            ->with('line')
            ->when($this->selectedLine, fn($q) => $q->where('line_id', $this->selectedLine))
            ->whereNotNull('line_id')
            ->get();
    }

    public function zoomIn()
    {
        $this->zoomLevel = min($this->zoomLevel + 0.1, 3);
    }

    public function zoomOut()
    {
        $this->zoomLevel = max($this->zoomLevel - 0.1, 0.3);
    }

    public function resetView()
    {
        $this->zoomLevel = 1;
        $this->dispatch('resetViewPosition');
    }

    public function render()
    {
        return view('livewire.guest.work-center-map-view');
    }
}
