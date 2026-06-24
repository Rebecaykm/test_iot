<?php

namespace App\Services;

use App\Enums\Shift;

class ProductionTrackingReportService
{
    public function __construct() {}

    public function hourlyShiftReport(array $productionTrackingDataSet, Shift $shift, array $hourlyRange): array
    {
        $shiftRange = Shift::shiftRange($shift);
        $totalHours = count($hourlyRange);

        $report = [];
        foreach ($productionTrackingDataSet as $record) {
            $report[] = [
                'product' => $record['productCode'],
                'workCenterDescription' => $record['workCenterDescription'],
                'SNP' => $record['standardPackQuantity'],
                'plannedSequences' => $record['AS4PlannedSequences'],
                'cycleTime' => $record['cycleTime'],
                'shopOrderNumber' => trim($record['shopOrderNumber']),
                'hourlyData' => $this->distribute(trim($record['shopOrderNumber']) ?? 'UNKNOWN', $record['AS4PlannedSequences'], $record['cycleTime'], $hourlyRange),
            ];
        }

        return $report;
    }

    public function generateHourRange(string $from, string $to): array
    {
        $hours = [];
        $currentHour = strtotime($from);
        $endHour = strtotime($to);

        while ($currentHour <= $endHour) {
            $hours[] = date('H:i', $currentHour);
            $currentHour = strtotime('+1 hour', $currentHour);
        }

        return $hours;
    }

    /**
     * Distributes the planned sequences across the hourly report strings,
     * showing exactly which sequence number is being processed in each hour.
     *
     * @param  int  $plannedSequences  e.g., 2
     * @param  float  $cycleTime  e.g., 2.5 (hours per sequence)
     * @param  array  $hourlyReport  e.g., ["08:00", "09:00", ...]
     * @return array Structured array with hours as keys and their sequence label.
     */
    private function distribute(string $shopOrderNumber, int $plannedSequences, float $cycleTime, array $hourlyReport): array
    {
        $distributedReport = [];

        // Paso 1: Inicializar todas las horas vacías
        foreach ($hourlyReport as $hour) {
            $distributedReport[$hour] = [
                'sequences' => '', // 'IDLE' o vacío si no se produce nada
                'hoursUsed' => 0.0,
            ];
        }

        if ($plannedSequences <= 0 || $cycleTime <= 0 || empty($hourlyReport)) {
            return $distributedReport;
        }

        $currentHourIndex = 0;
        $hoursCount = count($hourlyReport);

        // Recorremos una a una las secuencias planeadas (Secuencia 1, Secuencia 2...)
        for ($sequenceNumber = 1; $sequenceNumber <= $plannedSequences; $sequenceNumber++) {
            $timeNeededForCurrentSequence = $cycleTime; // 2.5 horas para esta secuencia

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

                // Asignar el nombre de la secuencia a esta hora
                $sequenceLabel = $shopOrderNumber.'-'.$sequenceNumber;
                if ($currentHourData['sequences'] === '') {
                    $currentHourData['sequences'] = $sequenceLabel;
                } else {
                    // Si ya había otra secuencia en esta misma hora (transición), las combinamos
                    $currentHourData['sequences'] .= '|'.$sequenceLabel;
                }

                if ($timeNeededForCurrentSequence <= $availableTimeInSlot) {
                    // La secuencia se termina por completo en esta hora
                    $currentHourData['hoursUsed'] += $timeNeededForCurrentSequence;
                    $timeNeededForCurrentSequence = 0; // Secuencia completada
                } else {
                    // La secuencia es más larga que el tiempo que le queda a esta hora, la llenamos y pasamos a la siguiente
                    $currentHourData['hoursUsed'] = 1.0;
                    $timeNeededForCurrentSequence -= $availableTimeInSlot;
                    $currentHourIndex++;
                }
            }
        }
        unset($currentHourData); // Romper referencia

        return $distributedReport;
    }
}
