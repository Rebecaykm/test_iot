<?php

namespace App\Livewire;

use App\Models\History;
use App\Models\ProductionRecord;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class PressProductionGraph extends Component
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
        // 1. Obtener el turno anterior una sola vez
        $previousShift = Shift::findPreviousShift($this->shift);

        // 2. Obtener rangos de tiempo para ambos turnos
        $previousShift = (object)[
            'model' => $previousShift,
            'timeRange' => Shift::getShiftDateTimeRange($previousShift, $this->now)
        ];

        $currentShift = (object)[
            'model' => $this->shift,
            'timeRange' => Shift::getShiftDateTimeRange($this->shift, $this->now)
        ];

        // 3. Calcular diferencias de horas
        $previousHoursDifference = Carbon::parse($previousShift->timeRange->startDateTime)
            ->diffInHours($previousShift->timeRange->endDateTime);

        $currentHoursDifference = Carbon::parse($currentShift->timeRange->startDateTime)
            ->diffInHours($currentShift->timeRange->endDateTime);

        // 4.1. Obtener registros de producción
        $previousProductionRecords = ProductionRecord::getPressProductionRecord(
            $this->workCenter,
            $previousShift->model->id,
            $previousShift->timeRange->startDateTime
        );

        $currentProductionRecords = ProductionRecord::getPressProductionRecord(
            $this->workCenter,
            $this->shift->id,
            $this->now
        );

        // 5.1. Calcular cantidad por hora (ahora por dos horas)
        $previousQuantityPerTwoHours = $previousProductionRecords->sum('planned_quantity') / ($previousHoursDifference / 2);
        $currentQuantityPerTwoHours = $currentProductionRecords->sum('planned_quantity') / ($currentHoursDifference / 2);

        // 6.1. Procesar horas y cantidades para ambos turnos
        $hoursAndQuantities = [];

        // Para el turno anterior
        $startDateTime = Carbon::parse($previousShift->timeRange->startDateTime);
        $endDateTime = Carbon::parse($previousShift->timeRange->endDateTime);
        $totalQuantity = 0;

        while ($startDateTime <= $endDateTime) {
            if (!$startDateTime->equalTo($previousShift->timeRange->startDateTime)) {
                $totalQuantity += $previousQuantityPerTwoHours;
            }

            $hourKey = $startDateTime->format('Y-m-d H:i:s');
            $hoursAndQuantities[$hourKey] = [
                'hour' => $hourKey,
                'quantity' => round($totalQuantity)
            ];

            $startDateTime->addHours(2);
        }

        // Para el turno actual
        $startDateTime = Carbon::parse($currentShift->timeRange->startDateTime);
        $endDateTime = Carbon::parse($currentShift->timeRange->endDateTime);
        $totalQuantity = 0;

        while ($startDateTime <= $endDateTime) {
            $hourKey = $startDateTime->format('Y-m-d H:i:s');

            if (isset($hoursAndQuantities[$hourKey]) && $hoursAndQuantities[$hourKey]['quantity'] > 0) {
                $startDateTime->addHours(2);
                continue;
            }

            if (!$startDateTime->equalTo($currentShift->timeRange->startDateTime)) {
                $totalQuantity += $currentQuantityPerTwoHours;
            }

            $hoursAndQuantities[$hourKey] = [
                'hour' => $hourKey,
                'quantity' => round($totalQuantity)
            ];

            $startDateTime->addHours(2);
        }

        // 7. Generar lista completa de horas entre ambos turnos (cada 2 horas)
        $startDateTime = Carbon::parse(min(
            $previousShift->timeRange->startDateTime,
            $currentShift->timeRange->startDateTime
        ));

        $endDateTime = Carbon::parse(max(
            $previousShift->timeRange->endDateTime,
            $currentShift->timeRange->endDateTime
        ));

        // 8. Crear el resultado final con todas las horas
        $finalHoursAndQuantities = [];

        while ($startDateTime <= $endDateTime) {
            $hourKey = $startDateTime->format('Y-m-d H:i:s');

            $finalHoursAndQuantities[] = $hoursAndQuantities[$hourKey] ?? [
                'hour' => $hourKey,
                'quantity' => 0
            ];

            $startDateTime->addHours(2);
        }

        // Real
        // 4.2. Obtener registros de producción
        $previousHistories = History::getProductionHistory($this->workCenter, $previousShift->timeRange->startDateTime, $previousShift->timeRange->endDateTime);
        $currentHistories = History::getProductionHistory($this->workCenter, $currentShift->timeRange->startDateTime, $currentShift->timeRange->endDateTime);

        // 5.2. Calcular cantidad por hora
        // Para el turno anterior
        $previousGroupedByHour = $previousHistories->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:00');  // Agrupar por hora
        });

        $previousgroupedByHourAndPartNumber = $previousGroupedByHour->map(function ($hourGroup) {
            $previousGroupedByPartNumber = $hourGroup->groupBy('part_number');

            $partNumbersWithMaxQuantity = $previousGroupedByPartNumber->map(function ($partGroup) {
                return $partGroup->max('quantity');
            });

            $totalQuantity = $partNumbersWithMaxQuantity->sum();

            $currentHour = \Carbon\Carbon::parse($hourGroup->first()->created_at);
            $nextHour = $currentHour->addHour()->format('Y-m-d H:00');

            return [
                'hour' => $nextHour,
                'quantity' => $totalQuantity
            ];
        });

        // Para el turno actual
        $currentGroupedByHour = $currentHistories->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:00');  // Agrupar por hora
        });

        $currentgroupedByHourAndPartNumber = $currentGroupedByHour->map(function ($hourGroup) {
            $currentGroupedByPartNumber = $hourGroup->groupBy('part_number');

            $partNumbersWithMaxQuantity = $currentGroupedByPartNumber->map(function ($partGroup) {
                return $partGroup->max('quantity');
            });

            $totalQuantity = $partNumbersWithMaxQuantity->sum();

            $currentHour = \Carbon\Carbon::parse($hourGroup->first()->created_at);
            $nextHour = $currentHour->addHour()->format('Y-m-d H:00');

            return [
                'hour' => $nextHour,
                'quantity' => $totalQuantity
            ];
        });

        //6.2 Procesar horas y cantidades para ambos turnos
        // Para el turno anterior
        $groupedByTwoHours = $previousgroupedByHourAndPartNumber->chunk(2)->mapWithKeys(function ($twoHourGroup) {
            if ($twoHourGroup->count() === 2) {
                $firstHourData = $twoHourGroup->first();
                $secondHourData = $twoHourGroup->last();

                $endTime = $secondHourData['hour'];
                $totalQuantity = $firstHourData['quantity'] + $secondHourData['quantity'];

                return [$endTime => [
                    'hour' => $endTime,
                    'quantity' => $totalQuantity,
                ]];
            } elseif ($twoHourGroup->count() === 1) {
                $singleHourData = $twoHourGroup->first();
                return [$singleHourData['hour'] => $singleHourData];
            } else {
                return [];
            }
        })->values()->pipe(function ($collection) {
            $accumulatedQuantity = 0;
            return $collection->map(function ($item) use (&$accumulatedQuantity) {
                $accumulatedQuantity += $item['quantity'];
                $item['quantity'] = $accumulatedQuantity;
                return $item;
            });
        });

        $previousStartDateTime = Carbon::parse($previousShift->timeRange->startDateTime)->floorHour(); // Aseguramos que empiece en una hora cerrada
        $previousEndDateTime = Carbon::parse($previousShift->timeRange->endDateTime)->floorHour();   // Aseguramos que termine en una hora cerrada

        $interval = new \DateInterval('PT2H');
        $period = new \DatePeriod($previousStartDateTime, $interval, $previousEndDateTime->add($interval)); // Incluimos el último intervalo

        $previousFinalGroupedData = collect([]);
        $previousLastAccumulatedQuantity = 0;

        foreach ($period as $dateTime) {
            $intervalEndDateTime = $dateTime->format('Y-m-d H:00');
            $found = false;

            foreach ($groupedByTwoHours as $item) {
                if ($item['hour'] === $intervalEndDateTime) {
                    $lastAccumulatedQuantity = $item['quantity'];
                    $previousFinalGroupedData->push([
                        'hour' => $intervalEndDateTime,
                        'quantity' => $lastAccumulatedQuantity,
                    ]);
                    $previousLastAccumulatedQuantity = $lastAccumulatedQuantity;
                    $found = true;
                    break;
                } elseif (Carbon::parse($item['hour'])->gt(Carbon::parse($intervalEndDateTime))) {
                    $previousFinalGroupedData->push([
                        'hour' => $intervalEndDateTime,
                        'quantity' => $previousLastAccumulatedQuantity,
                    ]);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $previousFinalGroupedData->push([
                    'hour' => $intervalEndDateTime,
                    'quantity' => $previousLastAccumulatedQuantity,
                ]);
            }
        }

        // Para el turno actual
        $currentGroupedByTwoHours = $currentgroupedByHourAndPartNumber->chunk(2)->mapWithKeys(function ($twoHourGroup) {
            if ($twoHourGroup->count() === 2) {
                $firstHourData = $twoHourGroup->first();
                $secondHourData = $twoHourGroup->last();

                $endTime = $secondHourData['hour'];
                $totalQuantity = $firstHourData['quantity'] + $secondHourData['quantity'];

                return [$endTime => [
                    'hour' => $endTime,
                    'quantity' => $totalQuantity,
                ]];
            } elseif ($twoHourGroup->count() === 1) {
                $singleHourData = $twoHourGroup->first();
                return [$singleHourData['hour'] => $singleHourData];
            } else {
                return [];
            }
        })->values()->pipe(function ($collection) {
            $accumulatedQuantity = 0;
            return $collection->map(function ($item) use (&$accumulatedQuantity) {
                $accumulatedQuantity += $item['quantity'];
                $item['quantity'] = $accumulatedQuantity;
                return $item;
            });
        });

        //

        // Primero combinamos las dos colecciones
        $combined = $previousFinalGroupedData->merge($currentGroupedByTwoHours);

// Agrupamos por 'hour' y sumamos las cantidades
        $finalGrouped = $combined->groupBy('hour')->map(function ($group) {
            // Sumamos las cantidades de todas las entradas con la misma hora
            $totalQuantity = $group->sum('quantity');
            return [
                'hour' => $group->first()['hour'], // Mantenemos la hora del primer registro
                'quantity' => $totalQuantity
            ];
        });

// Si quieres reindexar el array
        $finalGrouped = $finalGrouped->values();


        $this->labels = collect($finalHoursAndQuantities)->pluck('hour')->toArray();
        $this->plannedData = collect($finalHoursAndQuantities)->pluck('quantity')->toArray();
        $this->producedData = collect($finalGrouped)->pluck('quantity')->toArray();
        //        $this->productionRate = $productionRecords->pluck('production_rate')->toArray();
        //        $this->productionStart = $productionRecords->pluck('production_start')->toArray();
    }

    public function render()
    {
        return view('livewire.press-production-graph');
    }
}
