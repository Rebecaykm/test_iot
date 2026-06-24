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

        $productionTrackingDataSet = $this->productionTrackingService->getProductionTrackingStatus(
            productionDate: new DateTimeImmutable('2026-06-23'),
            shift: $shift,
            workcenterCode: '122070'
        );

        $report = $this->productionTrackingReportService->hourlyShiftReport(
            productionTrackingDataSet: $productionTrackingDataSet,
            shift: $shift
        );

        dd($report);

        return view('production-tracking.hourly-shift-report', compact('report'));
    }
}
