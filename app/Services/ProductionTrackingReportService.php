<?php

namespace App\Services;

use App\Dtos\ProductionTracking;
use App\Enums\Shift;

class ProductionTrackingReportService
{
    public function __construct() {}

    /**
     * Undocumented function
     *
     * @param  ProductionTracking[]  $productionTrackingDataSet
     */
    public function hourlyShiftReport(array $productionTrackingDataSet, Shift $shift, array $hourlyRange): array
    {
        $shiftRange = Shift::shiftRange($shift);
        $totalHours = count($hourlyRange);

        $report = [];
        foreach ($productionTrackingDataSet as $record) {
            $report[] = [
                'product' => $record->productCode,
                'workCenterDescription' => $record->workCenterDescription,
                'SNP' => $record->standardPackQuantity,
                'plannedQty' => $record->AS4QuantityRequired,
                'completedQty' => $record->IoTQuantityFinished,
                'plannedSequences' => $record->AS4PlannedSequences,
                'completedSequences' => $record->IoTCompletedSequences,
                'quantityRequired' => $record->AS4QuantityRequired,
                'sequencesPerHour' => $record->sequencesPerHour,
                'shopOrderNumber' => trim($record->shopOrderNumber),
                'hourlyData' => $this->distribute(
                    $record,
                    $hourlyRange
                ),
            ];
        }

        $response = [
            'totalSequences' => array_sum(array_column($report, 'plannedSequences')),
        ];

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
        unset($currentHourData); // Romper referencia

        return $distributedReport;
    }
}
