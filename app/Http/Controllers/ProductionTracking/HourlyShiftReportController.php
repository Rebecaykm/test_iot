<?php

namespace App\Http\Controllers\ProductionTracking;

use App\Enums\Shift;
use App\Http\Controllers\Controller;
use App\Services\ProductionTrackingReportService;
use App\Services\ProductionTrackingService;
use DateTimeImmutable;
use Illuminate\Http\Request;

class HourlyShiftReportController extends Controller
{
    public function __construct(
        private readonly ProductionTrackingService $productionTrackingService,
        private readonly ProductionTrackingReportService $productionTrackingReportService
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $shift = null;

        $dateInput = $request->input('date');
        try {
            $productionDate = $dateInput
                ? new DateTimeImmutable($dateInput)
                : new DateTimeImmutable('today');
        } catch (\Throwable) {
            $productionDate = new DateTimeImmutable('today');
        }
        $date = $productionDate->format('Y-m-d');

        $shiftRange = Shift::shiftRange($shift);

        $productionTrackingDataSet = $this->productionTrackingService->getProductionStatus(
            productionDate: $productionDate,
            shift: $shift,
            workcenterCode: '122070'
        );

        $shiftRange[0] = empty($shift) ? date('Y-m-d').' 08:00:00' : $shiftRange[0];
        $shiftRange[1] = empty($shift) ? now()->addDay()->format('Y-m-d').' 08:00:00' : $shiftRange[1];

        $hourlyRange = $this->productionTrackingReportService->generateHourRange(
            from: $shiftRange[0],
            to: $shiftRange[1]
        );

        $report = $this->productionTrackingReportService->hourlyShiftReport(
            productionTrackingDataSet: $productionTrackingDataSet,
            shift: $shift,
            hourlyRange: $hourlyRange
        );

        $data = [];

        foreach ($report as $reportRow) {
            // Nos aseguramos de que existan registros en esta fila antes de iterar
            if (! isset($reportRow['records']) || ! is_array($reportRow['records'])) {
                continue;
            }

            foreach ($reportRow['records'] as $item) {
                $planned = (float) ($item['plannedSequences'] ?? 0);
                $completed = (float) ($item['IoTCompletedSequences'] ?? 0);

                $percentage = $planned > 0
                    ? ($completed / $planned) * 100
                    : 0;

                $overflowPercentage = max(0, $percentage - 100);

                // Además, corregimos el 'progress' para que envíe el porcentaje real (ej: 1.30)
                // y tu JS de dhtmlxGantt pueda detectar el desborde (progress > 1)
                $realProgress = $percentage / 100;

                $data[] = [
                    'id' => $item['productionOrder'],
                    'text' => $item['productCode'],

                    'start_date' => $item['start_time'],
                    'end_date' => $item['end_time'],

                    // Nota: Quitamos el min(..., 1) para que mande valores mayores a 1 si hay desborde
                    'progress' => $realProgress,

                    'open' => true,
                    'type' => 'task',
                    'shift' => $item['shift'],

                    'snp' => $item['snp'],
                    'plannedPieces' => $planned,
                    'completedPieces' => $completed,
                    'workcenterDescription' => $item['workCenterName'],

                    'percentage' => $item['completed'],
                    'overflowPercentage' => round($overflowPercentage, 2),
                    'shopOrderNumber' => $item['shopOrderNumber'],
                    'productionOrder' => $item['productionOrder'],

                    'status' => $percentage > 100
                        ? 'overflow'
                        : ($percentage >= 95 ? 'normal' : 'warning'),
                ];
            }
        }

        $data = [
            'data' => collect($data)
                ->sortBy(function ($item) {
                    return [
                        $item['shift'],
                        $item['productionOrder'],
                    ];
                })
                ->values()
                ->all(),
        ];

        // dd($data['data']);

        return view('production-tracking.hourly-shift-report', compact('data', 'hourlyRange', 'shift', 'date'));
    }
}
