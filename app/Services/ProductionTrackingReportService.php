<?php

namespace App\Services;

use App\Dtos\ProductionTracking;
use App\Enums\Shift;
use Carbon\Carbon;
use DateTime;

class ProductionTrackingReportService
{
    public function __construct() {}

    /**
     * Undocumented function
     *
     * @param  array[]  $productionTrackingDataSet
     */
    public function hourlyShiftReport(array $productionTrackingDataSet, array $hourlyRange, ?Shift $shift = null): array
    {
        $w = collect($productionTrackingDataSet)->groupBy('workCenterCode')
            ->map(function ($records, $workCenterCode) {

                // 1. Mapeamos y limpiamos los datos iniciales primero
                $mappedRecords = $records->map(function ($record) {
                    return [
                        'productCode' => trim($record['productCode'] ?? ''),
                        'classCode' => trim($record['classCode'] ?? ''),
                        'shopOrderNumber' => trim($record['shopOrderNumber'] ?? ''),
                        'snp' => (float) ($record['standardPackQuantity'] ?? 0),
                        'workCenterCode' => trim($record['workCenterCode'] ?? ''),
                        'workCenterName' => trim($record['workCenterDescription'] ?? ''),
                        'lineName' => trim($record['lineName'] ?? ''),
                        'laborHours' => (float) ($record['laborHours'] ?? 0),
                        'sequencesPerUnit' => ((float) ($record['standardPackQuantity'] ?? 0) > 0)
                            ? (float) ($record['laborHours'] ?? 0) / (float) ($record['standardPackQuantity'] ?? 0)
                            : 0,
                        'productionOrder' => (int) ($record['productionOrder'] ?? 0),
                        'plannedSequences' => (float) ($record['AS4PlannedSequences'] ?? 0),
                        'plannedPieces' => (int) ($record['AS4QuantityRequired'] ?? 0),
                        'completedPieces' => (float) ($record['IoTQuantityFinished'] ?? 0),
                        'completedSequences' => (int) ($record['IoTCompletedSequences'] ?? 0),
                        'completedPercentage' => ($record['AS4PlannedSequences'] > 0)
                            ? round(($record['IoTCompletedSequences'] / $record['AS4PlannedSequences']) * 100, 2)
                            : 0,
                        'shift' => $record['shift'],
                        'completed' => (float) ($record['IoTCompletedSequences'] ?? 0),
                    ];
                })
                    ->sortBy([
                        ['shift', 'asc'],
                        ['productionOrder', 'asc'],
                    ])->values();

                $currentTime = Carbon::today()->setHour(8)->setMinute(0)->setSecond(0);
                $startTimeLimit = $currentTime->copy();
                $endTimeLimit = Carbon::today()->setHour(20)->setMinute(0)->setSecond(0);

                $timelineRecords = [];
                $totalMinutesLine = 0;

                // 3. Calculamos la línea de tiempo aplicando el redondeo hacia arriba por renglón
                foreach ($mappedRecords as $record) {
                    $totalPieces = $record['plannedSequences'] * $record['snp'];
                    $piecesPerHour = $record['laborHours'];

                    // Calculamos los minutos exactos
                    $exactMinutes = ($totalPieces > 0 && $piecesPerHour > 0)
                        ? ($totalPieces / $piecesPerHour) * 60
                        : 0;

                    // REDONDEO HACIA ARRIBA: ceil(52.5) se convierte en 53
                    $durationInMinutes = (int) ceil($exactMinutes);

                    $startOrderTime = $currentTime->copy();

                    if ($durationInMinutes > 0) {
                        $currentTime->addMinutes($durationInMinutes);
                    }

                    $endOrderTime = $currentTime->copy();
                    $totalMinutesLine += $durationInMinutes;

                    // Guardamos la información con los minutos ya redondeados
                    $timelineRecords[] = array_merge($record, [
                        'totalPieces' => $totalPieces,
                        'duration_minutes' => $durationInMinutes,
                        'duration_formatted' => $durationInMinutes > 0
                            ? sprintf('%dh %02dm', floor($durationInMinutes / 60), $durationInMinutes % 60)
                            : '0 min',
                        'start_time' => $startOrderTime->format('Y-m-d H:i:s'),
                        'end_time' => $endOrderTime->format('Y-m-d H:i:s'),
                        'status' => $durationInMinutes > 0 ? 'Produced' : 'Skipped',
                    ]);
                }

                // 4. Retornamos la estructura con el resumen global afectado por los redondeos
                return [
                    'workCenterCode' => $workCenterCode,
                    // 'summary' => [
                    //     'global_start_time' => $startTimeLimit->format('Y-m-d H:i:s'),
                    //     'global_end_time' => $currentTime->format('Y-m-d H:i:s'),
                    //     'total_duration_hours' => round($totalMinutesLine / 60, 2),
                    //     'total_duration_minutes' => $totalMinutesLine,
                    //     'exceeds_limit' => $currentTime->greaterThan($endTimeLimit),
                    // ],
                    'records' => $timelineRecords,
                ];
            })->values()->toArray();

        return $w;
    }

    public function generateHourRange(string $from, string $to): array
    {
        $hours = [];
        $current = new DateTime($from);
        $end = new DateTime($to);

        while ($current <= $end) {
            $hours[] = $current->format('H:i');
            $current->modify('+1 hour');
        }

        return $hours;
    }

    /**
     * Distributes the sequences across the hourly report strings,
     * separating explicitly what was planned, what was completed, and the hours used.
     *
     * @param  array  $hourlyReport  e.g., ["08:00", "09:00", ...]
     * @return array Structured array with hours as keys, planned, completed and hoursUsed.
     */
    private function distribute(
        ProductionTracking $record,
        array $hourlyReport
    ): array {
        $distributedReport = [];

        // Paso 1: Inicializar todas las horas con la estructura requerida
        foreach ($hourlyReport as $hour) {
            $distributedReport[$hour] = [
                'plannedSequences' => '', // Secuencias planificadas trabajadas en esta hora
                'completedSequences' => '', // Secuencias completadas trabajadas en esta hora
                'hoursUsed' => 0.0, // Horas totales ocupadas
            ];
        }

        $plannedSequences = $record->AS4PlannedSequences;
        $completedSequences = $record->IoTCompletedSequences;
        $cycleTimeRate = $record->sequencesPerHour; // Ej: 4 (secuencias por hora)

        // IMPORTANTE: Si cycleTimeRate es la tasa por hora, el tiempo de cada secuencia es (1 / tasa)
        if ($plannedSequences <= 0 || $cycleTimeRate <= 0 || empty($hourlyReport)) {
            return $distributedReport;
        }

        // Calcular las horas que consume CADA secuencia de forma individual
        $hoursPerSequence = 1.0 / $cycleTimeRate; // Ej: 1 / 4 = 0.25 horas por secuencia

        $currentHourIndex = 0;
        $hoursCount = count($hourlyReport);

        // Recorremos una a una las secuencias planeadas
        for ($sequenceNumber = 1; $sequenceNumber <= $plannedSequences; $sequenceNumber++) {

            // Usamos el nuevo tiempo calculado por secuencia
            $timeNeededForCurrentSequence = $hoursPerSequence;

            // Identificar si la secuencia actual entra en el rango de completadas
            $isCompleted = ($sequenceNumber <= $completedSequences);
            $sequenceLabel = ($record->shopOrderNumber ?? '').'-'.$sequenceNumber;

            // Consumir el tiempo de esta secuencia en las horas del turno
            while ($timeNeededForCurrentSequence > 0 && $currentHourIndex < $hoursCount) {
                $currentHourKey = $hourlyReport[$currentHourIndex];
                $currentHourData = &$distributedReport[$currentHourKey];

                // Cuánto tiempo le queda libre a la hora actual (máximo 1.0 hora)
                $availableTimeInSlot = 1.0 - $currentHourData['hoursUsed'];

                if ($availableTimeInSlot <= 0) {
                    $currentHourIndex++;

                    continue;
                }

                // 1. Registrar en 'plannedSequences'
                if ($currentHourData['plannedSequences'] === '') {
                    $currentHourData['plannedSequences'] = $sequenceLabel;
                } else {
                    if (strpos($currentHourData['plannedSequences'], $sequenceLabel) === false) {
                        $currentHourData['plannedSequences'] .= '|'.$sequenceLabel;
                    }
                }

                // 2. Registrar en 'completedSequences' (Solo si se cumplió)
                if ($isCompleted) {
                    if ($currentHourData['completedSequences'] === '') {
                        $currentHourData['completedSequences'] = $sequenceLabel;
                    } else {
                        if (strpos($currentHourData['completedSequences'], $sequenceLabel) === false) {
                            $currentHourData['completedSequences'] .= '|'.$sequenceLabel;
                        }
                    }
                }

                // 3. Acumular el tiempo transcurrido
                // Usamos un pequeño delta (1e-9) para mitigar problemas de precisión de flotantes en PHP
                if ($timeNeededForCurrentSequence <= ($availableTimeInSlot + 1e-9)) {
                    // La secuencia se termina por completo en esta hora o remanente exacto
                    $currentHourData['hoursUsed'] += $timeNeededForCurrentSequence;
                    $timeNeededForCurrentSequence = 0;
                } else {
                    // La secuencia es más larga, llenamos la hora y pasamos a la siguiente
                    $currentHourData['hoursUsed'] = 1.0;
                    $timeNeededForCurrentSequence -= $availableTimeInSlot;
                    $currentHourIndex++;
                }
            }
        }
        unset($currentHourData);

        return $distributedReport;
    }

    public function getCurrentShift(): ?Shift
    {
        $currentHour = (int) now()->format('H');

        if ($currentHour >= 8 && $currentHour < 20) {
            return Shift::Day;
        } elseif ($currentHour >= 20 || $currentHour < 8) {
            return Shift::Night;
        }

        return null;
    }
}
