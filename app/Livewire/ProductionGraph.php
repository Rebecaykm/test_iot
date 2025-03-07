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
    public array $labels = [];
    public array $plannedData = [];
    public array $producedData = [];
    public array $productionRate = [];
    public array $productionStart = [];


    public bool $realTime = false;
    public $workCenter;

    public $now;
    public $shift;

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
        $productionRecords = ProductionRecord::getWorkCenterProductionRecord($this->workCenter, $this->shift->id, $this->now);

        $this->labels = $productionRecords->pluck('part_number')->toArray();
        $this->plannedData =  $productionRecords->pluck('planned_quantity')->toArray();
        $this->producedData = $productionRecords->pluck('produced_quantity')->toArray();
        $this->productionRate = $productionRecords->pluck('production_rate')->toArray();
        $this->productionStart = $productionRecords->pluck('production_start')->toArray();
    }

    public function render()
    {
        return view('livewire.production-graph');
    }
}
