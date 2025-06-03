<?php

namespace App\Livewire\Guest;

use App\Models\Line;
use App\Models\WorkCenter;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class WorkCenterMapView extends Component
{
    public $lines = [];
    public $selectedLine = null;
    public $zoomLevel = 1;
    public $now;
    public $currentShift;

    public function mount()
    {
        $this->lines = Line::all();
        $this->now = Carbon::now();
        $this->currentShift = Shift::getShift($this->now);
    }

    public function getWorkCentersProperty()
    {
        $workCenters = WorkCenter::query()
            ->with('line')
            ->when($this->selectedLine, fn($q) => $q->where('line_id', $this->selectedLine))
            ->whereNotNull('line_id')
            ->get();

        // Obtener estados de producción para cada work center
        $productionStatuses = $this->getWorkCenterStatuses();

        // Agregar el estado a cada work center
        foreach ($workCenters as $workCenter) {
            $workCenter->production_status = $productionStatuses[$workCenter->id] ?? 'Detenida';
        }

        return $workCenters;
    }

    private function getWorkCenterStatuses()
    {
        if (!$this->currentShift) {
            return [];
        }

        // Usar el método centralizado del modelo
        $currentShiftInfo = Shift::getCurrentShiftInfo($this->now);

        $results = DB::select("
            SELECT
                wc.id as work_center_id,
                wc.number AS work_center_number,
                wc.name AS work_center_name,
                s.name AS status_name,
                COUNT(*) AS total_records
            FROM work_centers AS wc
            INNER JOIN lines AS ln ON wc.line_id = ln.id
            INNER JOIN part_numbers AS pn ON wc.id = pn.work_center_id
            INNER JOIN production_records AS pr ON pn.id = pr.part_number_id
            INNER JOIN statuses AS s ON pr.status_id = s.id
            WHERE wc.line_id IS NOT NULL
              AND pr.planned_date = ?
              AND pr.shift_id = ?
            GROUP BY wc.id, wc.number, wc.name, s.name
            ORDER BY wc.number, s.name
        ", [$currentShiftInfo->date->toDateString(), $this->currentShift->id]);

        $statuses = [];

        foreach ($results as $result) {
            $workCenterId = $result->work_center_id;

            // Si encontramos "En progreso", marcamos como tal
            if ($result->status_name === 'En progreso') {
                $statuses[$workCenterId] = 'En progreso';
            } else {
                // Solo establecemos como "Detenida" si no hemos encontrado "En progreso" antes
                if (!isset($statuses[$workCenterId])) {
                    $statuses[$workCenterId] = 'Detenida';
                }
            }
        }

        return $statuses;
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
