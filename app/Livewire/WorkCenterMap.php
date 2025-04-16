<?php

namespace App\Livewire;

use App\Models\Line;
use App\Models\WorkCenter;
use Livewire\Component;

class WorkCenterMap extends Component
{
    public $lines = [];
    public $selectedLine = null;
    public $zoomLevel = 1;

    protected $listeners = ['workCenterMoved' => 'updateWorkCenterPosition'];

    public function mount()
    {
        $this->lines = Line::all();
    }

    public function getWorkCentersProperty()
    {
        $query = WorkCenter::with('line')->whereNotNull('line_id');

        if ($this->selectedLine) {
            $query->where('line_id', $this->selectedLine);
        }

        return $query->get();
    }

    public function updateWorkCenterPosition($workCenterId, $x, $y)
    {
        $x = max((float)$x, 0);
        $y = max((float)$y, 0);

        $workCenter = WorkCenter::find($workCenterId);
        if ($workCenter) {
            $workCenter->update([
                'position_x' => $x,
                'position_y' => $y,
            ]);
        }
    }

    public function zoomIn()
    {
        $this->zoomLevel = min($this->zoomLevel + 0.1, 3);
    }

    public function zoomOut()
    {
        $this->zoomLevel = max($this->zoomLevel - 0.1, 0.3);
    }

    public function render()
    {
        return view('livewire.work-center-map');
    }
}
