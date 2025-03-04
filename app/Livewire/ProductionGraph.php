<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductionGraph extends Component
{
    public string|null $chartId = null;
    public $productionRecords;
    public array $labels = [];
    public array $datasets = [];

    public bool $realTime = false;
    public $workCenter;

    public $now;
    public $shift;
    public $startDateTime;
    public $endDateTime;

    public function __construct()
    {
        $this->chartId = Str::ulid();
    }

    public function mount($workCenter, $realTime): void
    {
        $this->workCenter = $workCenter;
        $this->realTime = $realTime;

        $this->refreshGraph();
    }

    #[On('refresh-graph')]
    public function refreshGraph()
    {
        $this->now = Carbon::now();

        $this->shift = Shift::getShift($this->now);

        $this->fetchGraphData();
    }


    public function fetchGraphData(): void
    {
        $this->productionRecords = ProductionRecord::getWorkCenterProductionRecord($this->workCenter, $this->shift->id, $this->now);
    }

    public function render()
    {
        return view('livewire.production-graph');
    }
}
