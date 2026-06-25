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
        $shift = Shift::tryFrom('D');
        $shiftRange = Shift::shiftRange($shift);

        $productionTrackingDataSet = $this->productionTrackingService->getProductionTrackingStatus(
            productionDate: new DateTimeImmutable('2026-06-25'),
            shift: $shift,
            workcenterCode: '122450'
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

        $totalPlanned = collect($report)->sum('plannedSequences');
        $totalCompleted = collect($report)->sum('completedSequences');

        $totalHours = count($hourlyRange);

        $currentHour = date('H');

        $currentHourIndex = collect($hourlyRange)->search(function ($hour) use ($currentHour) {
            return (int) substr($hour, 0, 2) == (int) $currentHour;
        });

        $currentHourIndex = $currentHourIndex === false ? $totalHours : $currentHourIndex + 1;

        $expectedByNow = ($totalPlanned / $totalHours) * $currentHourIndex;

        $variance = $totalCompleted - $expectedByNow;

        return view('production-tracking.hourly-shift-report', compact('report', 'hourlyRange', 'totalPlanned', 'totalCompleted', 'expectedByNow', 'variance'));
    }
}
