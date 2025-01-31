<?php

namespace App\Livewire\ProductionRecord;

use App\Models\History;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class HourlyProductionGraph extends Component
{
    public $chartData = [];

    public function mount()
    {
        $this->getHourlyProductionGraph();
    }

    public function getHourlyProductionGraph()
    {
        $now = Carbon::now();

        // Obtener el turno actual
        $shift = Shift::query()
            ->where(function ($query) use ($now) {
                $query->where('name', 'Diurno')
                    ->whereTime('start_time', '<=', $now)
                    ->whereTime('end_time', '>', $now);
            })
            ->orWhere(function ($query) use ($now) {
                $query->where('name', 'Nocturno')
                    ->where(function ($nestedQuery) use ($now) {
                        $nestedQuery->whereTime('start_time', '<=', $now)
                            ->orWhereTime('end_time', '>=', $now);
                    });
            })
            ->first();

        // Calcular las horas de inicio y fin del turno
        $startDateTime = $now->copy()->setTimeFromTimeString($shift->start_time);
        $endDateTime = $shift->abbreviation === 'N'
            ? $now->copy()->addDay()->setTimeFromTimeString($shift->end_time)
            : $now->copy()->setTimeFromTimeString($shift->end_time);

        // Obtener registros históricos
        $historyRecords = History::join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
            ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
            ->join('production_records', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('shifts', 'shifts.id', '=', 'production_records.shift_id')
            ->where('shifts.id', '=', $shift->id)
            ->whereBetween('histories.created_at', [$startDateTime, $endDateTime])
            ->orderBy('work_centers.number', 'asc')
            ->orderBy('histories.created_at', 'asc')
            ->select(
                'work_centers.name AS work_name',
                'part_numbers.number AS part_number',
                'production_records.planned_quantity AS planned_quantity',
                'histories.created_at',
                'histories.quantity'
            )
            ->get();

        // Agrupar datos por work center y número de parte
        $groupedData = [];
        foreach ($historyRecords as $record) {
            $hourKey = Carbon::parse($record->created_at)->format('Y-m-d H:00');

            $groupedData[$record->work_name][$record->part_number]['planned_quantity'] = $record->planned_quantity;
            $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$hourKey] =
                ($groupedData[$record->work_name][$record->part_number]['production_per_hour'][$hourKey] ?? 0) + $record->quantity;
        }

        // Preparar datos para Chart.js
        $hours = $startDateTime->diffInHours($endDateTime);
        foreach ($groupedData as $workName => $parts) {
            foreach ($parts as $partNumber => $data) {
                $labels = array_keys($data['production_per_hour']);
                $plannedPerHour = round($data['planned_quantity'] / $hours, 3);

                $plannedData = [];
                $accumulatedPlanned = 0;
                foreach ($labels as $label) {
                    $accumulatedPlanned += $plannedPerHour;
                    $plannedData[] = $accumulatedPlanned;
                }

                $productionData = array_values($data['production_per_hour']);

                $this->chartData[] = [
                    'work_name' => $workName,
                    'part_number' => $partNumber,
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Cantidad Planeada Por Hora',
                            'data' => $plannedData,
                            'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                            'borderColor' => 'rgb(255, 159, 64)',
                            'borderWidth' => 2,
                            'stack' => 'combined'
                        ],
                        [
                            'label' => 'Cantidad Producida por Hora',
                            'data' => $productionData,
                            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                            'borderColor' => 'rgb(75, 192, 192)',
                            'borderWidth' => 2,
                            'stack' => 'combined'
                        ],
                    ],
                    'chart_id' => (string) Str::ulid(),
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.production-record.hourly-production-graph');
    }
}
