<?php

namespace App\Livewire\ProductionRecord;

use Livewire\Component;

class HourlyProductionGraph extends Component
{
    public $realTime = true;
    public $labels = [];
    public $data = [];
    public $chartData = []; // Datos iniciales para los gráficos

    public function mount()
    {
        // Datos iniciales de ejemplo
        $this->chartData = [
            [
                'chart_id' => 'chart-1',
                'work_name' => 'Example Work 1',
                'part_number' => 'Part 1',
                'labels' => ['10:00', '11:00', '12:00'],
                'datasets' => [
                    [
                        'label' => 'Production',
                        'data' => [10, 15, 20],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1,
                    ],
                ],
            ],
        ];

        $this->labels = $this->chartData[0]['labels'];
        $this->data = $this->chartData[0]['datasets'][0]['data'];
    }

    public function refreshGraph()
    {
        // Actualizar datos de ejemplo (esto debería venir de tu lógica)
        $this->labels[] = now()->format('H:i');
        $this->data[] = rand(10, 50); // Simular datos nuevos

        if (count($this->labels) > 10) {
            array_shift($this->labels); // Limitar el número de etiquetas
            array_shift($this->data);   // Limitar el número de datos
        }

        // Emitir evento para actualizar los datos en Alpine.js
        $this->emit('updateGraph', $this->labels, $this->data);
    }

    public function render()
    {
        return view('livewire.production-record.hourly-production-graph');
    }
}
