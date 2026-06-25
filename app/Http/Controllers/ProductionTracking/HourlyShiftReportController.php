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
        $shift = Shift::from($request->query('shift', Shift::Day->value));
        $shiftRange = Shift::shiftRange($shift);

        $productionTrackingDataSet = $this->productionTrackingService->getProductionTrackingStatus(
            productionDate: new DateTimeImmutable('2026-06-25'),
            shift: $shift,
            workcenterCode: '122450'
        );

        $hourlyRange = $this->productionTrackingReportService->generateHourRange($shiftRange[0], $shiftRange[1]);

        $report = $this->productionTrackingReportService->hourlyShiftReport(
            productionTrackingDataSet: $productionTrackingDataSet,
            shift: $shift,
            hourlyRange: $hourlyRange
        );

        $totalPlanned = collect($report)->sum('plannedSequences');
        $totalCompleted = collect($report)->sum('completedSequences');

        $totalHours = count($hourlyRange);

        // Hora actual
        $currentHour = date('H');

        // Buscar la posición actual dentro del rango
        $currentHourIndex = collect($hourlyRange)->search(function ($hour) use ($currentHour) {
            return (int) substr($hour, 0, 2) == (int) $currentHour;
        });

        $currentHourIndex = $currentHourIndex === false ? $totalHours : $currentHourIndex + 1;

        // Producción esperada a esta hora
        $expectedByNow = ($totalPlanned / $totalHours) * $currentHourIndex;

        $variance = $totalCompleted - $expectedByNow;

        return view('production-tracking.hourly-shift-report', compact('report', 'hourlyRange', 'totalPlanned', 'totalCompleted', 'expectedByNow', 'variance'));
    }
}
