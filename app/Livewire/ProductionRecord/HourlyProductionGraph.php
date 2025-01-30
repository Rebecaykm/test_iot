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

        $shift = Shift::query()
            ->where(function ($query) use ($now) {
                $query->whereTime('start_time', '<=', $now->format('H:i'))
                    ->whereTime('end_time', '>', $now->format('H:i'));
            })
            ->orWhere(function ($query) use ($now) {
                $query->whereTime('start_time', '<=', $now->format('H:i'))
                    ->whereTime('end_time', '>=', $now->format('H:i'));
            })
            ->first();

        $startDateTime = $now->copy()->setTimeFromTimeString($shift->start_time);

        if ($shift->abbreviation === 'N') {
            $endDateTime = $now->copy()->addDay()->setTimeFromTimeString($shift->end_time);
        } else {
            $endDateTime = $now->copy()->setTimeFromTimeString($shift->end_time);
        }

        $historyRecords = History::join('part_numbers', 'part_numbers.id', '=', 'histories.part_number_id')
            ->join('work_centers', 'work_centers.id', '=', 'part_numbers.work_center_id')
            ->join('production_records', 'production_records.part_number_id', '=', 'part_numbers.id')
            ->join('shifts', 'shifts.id', '=', 'production_records.shift_id')
            ->where('shifts.id', '=', $shift->id)
            ->whereBetween('histories.created_at', [$startDateTime, $endDateTime])
            ->orderBy('work_centers.number', 'asc')
            ->orderBy('histories.created_at', 'asc')
            ->select(
                'work_centers.number AS work_number',
                'work_centers.name AS work_name',
                'part_numbers.id AS part_id',
                'part_numbers.number AS part_number',
                'part_numbers.name AS part_name',
                'production_records.planned_quantity AS planned_quantity',
                'shifts.abbreviation',
                'histories.created_at',
                'histories.quantity'
            )
            ->get();

        $groupedData = [];

        foreach ($historyRecords as $record) {
            $dateTime = Carbon::parse($record->created_at)->format('Y-m-d H:00');

            if (!isset($groupedData[$record->work_name])) {
                $groupedData[$record->work_name] = [];
            }

            if (!isset($groupedData[$record->work_name][$record->part_number])) {
                $groupedData[$record->work_name][$record->part_number] = [
                    'planned_quantity' => $record->planned_quantity,
                    'production_per_hour' => []
                ];
            }

            if (!isset($groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime])) {
                $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime] = [];
            }

            if (!isset($groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number])) {
                $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number] = $record->quantity;
            } else {
                $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number] = max(
                    $groupedData[$record->work_name][$record->part_number]['production_per_hour'][$dateTime][$record->part_number],
                    $record->quantity
                );
            }
        }

        $hours = $startDateTime->diffInHours($endDateTime);

        // Convertir $groupedData al formato esperado por Chart.js
        $chartData = [];

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

                $productionData = [];
                foreach ($data['production_per_hour'] as $hourData) {
                    $productionData[] = array_sum($hourData);
                }

                $chartData[] = [
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
                        ]
                    ],
                    'chart_id' => (string) Str::ulid()
                ];
            }
        }

        $this->chartData = $chartData;
    }

    public function render()
    {
        return view('livewire.production-record.hourly-production-graph');
    }
}
