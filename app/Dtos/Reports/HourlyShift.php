<?php

namespace App\Dtos\Reports;

use App\Dtos\ProductionTracking;
use App\Enums\Shift;

class HourlyShift
{
    /**
     * Create a new class instance.
     *
     * @param  ProductionTracking[]  $productionTrackingDataSet
     */
    public function __construct(
        public array $productionTrackingDataSet,
        public ?Shift $shift = null,
    ) {}

    public function toReport(): array
    {
        $report = [
            'kpi' => [
                'totalPlannedSequences' => $this->obtainTotalPlannedSequences(),
                'totalCompletedSequences' => $this->obtainTotalCompletedSequences(),
            ],
        ];

        return $report;
    }

    private function obtainTotalPlannedSequences(): int
    {
        return array_sum(
            array_column($this->productionTrackingDataSet, 'AS4PlannedSequences')
        );
    }

    public function obtainTotalCompletedSequences(): int
    {
        return array_sum(
            array_column($this->productionTrackingDataSet, 'IoTCompletedSequences')
        );
    }
}
