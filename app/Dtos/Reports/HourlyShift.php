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
        public array $hourlyRange,
        public ?Shift $shift = null,
    ) {}

    public function toReport(): array
    {
        $report = [
            'kpi' => [
                'plannedSequences' => $this->obtainTotalPlannedSequences(),
                'actualSequences' => $this->obtainTotalCompletedSequences(),
            ],
            'dataSets' => [
                'completed' => $this->createDataSet($this->hourlyRange),
                'planned' => $this->createDataSet($this->hourlyRange),
            ],
            'data' => [],
        ];

        return $report;
    }

    private function obtainTotalPlannedSequences(): int
    {
        return array_sum(
            array_column($this->productionTrackingDataSet, 'AS4PlannedSequences')
        );
    }

    private function obtainTotalCompletedSequences(): int
    {
        return array_sum(
            array_column($this->productionTrackingDataSet, 'IoTCompletedSequences')
        );
    }

    private function createDataSet(array $hourlyRange): array
    {
        $distributedReport = [];

        foreach ($hourlyRange as $hour) {
            $distributedReport[$hour] = [
                'completed_sequences' => [
                    'quantity' => 0,
                    'shop_order_numbers' => [],
                ],
                'planned_sequences' => [
                    'quantity' => 0,
                    'shop_order_numbers' => [],
                ],
            ];
        }

        return $distributedReport;
    }
}
