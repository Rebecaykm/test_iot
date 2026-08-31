<?php

namespace App\Services;

use App\Dtos\ProductionTracking;
use App\Enums\Shift;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PDO;

class ProductionTrackingService
{
    public function __construct() {}

    public function getProductionTrackingStatus(DateTimeImmutable $productionDate, string $workcenterCode, ?Shift $shift = null): array
    {
        $query = 'EXEC dbo.GetProductionTrackingStatus @productionDate = ?, @shift = ?, @workCenterCode = ?';

        $statement = DB::connection()->getPdo()->prepare($query);

        $statement->bindValue(1, $productionDate->format('Ymd'));
        $statement->bindValue(2, $shift?->value);
        $statement->bindValue(3, $workcenterCode);

        $rc = $statement->execute();

        if ($rc === false) {
            throw new \RuntimeException('Failed to execute query: '.implode(', ', $statement->errorInfo()));
        }

        $dataSet = $statement->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($dataSet as $row) {
            $result[] = ProductionTracking::fromArray($row);
        }

        return $result;
    }

    public function getProductionStatus(DateTimeImmutable $productionDate, ?string $workcenterCode = null, ?Shift $shift = null): array
    {
        $query = 'EXEC dbo.GetProductionTrackingStatusV2 @productionDate = ?, @shift = ?, @workCenterCode = ?';

        $statement = DB::connection()->getPdo()->prepare($query);

        $statement->bindValue(1, $productionDate->format('Ymd'));
        $statement->bindValue(2, $shift?->value);
        $statement->bindValue(3, $workcenterCode);

        $rc = $statement->execute();

        if ($rc === false) {
            throw new \RuntimeException('Failed to execute query: '.implode(', ', $statement->errorInfo()));
        }

        $dataSet = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $dataSet;

        // return array_map(function (array $row) {
        //     $shopOrderNumber = trim($row['shopOrderNumber'] ?? null);

        //     $labels = [];

        //     for ($i = 0; $i < ceil($row['AS4PlannedSequences']); $i++) {
        //         $labels[] = $shopOrderNumber.'-'.($i + 1);
        //     }

        //     return [
        //         'orderNumber' => (int) $row['productionOrder'] ?? null,
        //         'areaName' => $row['areaName'] ?? null,
        //         'lineName' => $row['lineName'] ?? null,
        //         'workCenterCode' => $row['workCenterCode'] ?? null,
        //         'workCenterDescription' => $row['workCenterDescription'] ?? null,
        //         'productCode' => $row['productCode'] ?? null,
        //         'productDescription' => $row['productDescription'] ?? null,
        //         'classCode' => $row['classCode'] ?? null,
        //         'classDescription' => $row['classDescription'] ?? null,
        //         'standardPackQuantity' => (int) $row['standardPackQuantity'] ?? null,
        //         'piecesPerHour' => (int) $row['laborHours'] ?? null,
        //         'shopOrderNumber' => $shopOrderNumber,
        //         'plannedLabels' => (float) $row['AS4PlannedSequences'] ?? 0,
        //         'completedLabels' => (float) $row['IoTCompletedSequences'] ?? 0,
        //         'percentageCompleted' => ($row['AS4PlannedSequences'] ?? 0) > 0
        //             ? round((($row['IoTCompletedSequences'] ?? 0) * 100) / $row['AS4PlannedSequences'], 2)
        //             : 0,
        //         'labels' => $labels,
        //     ];
        // }, $dataSet);
    }

    public function getProductionStatusForWorkcenter(DateTimeImmutable $productionDate, ?string $workcenterCode = null): array
    {
        $query = 'EXEC [dbo].[GetProductionTrackingProducts] ?, ?';
        $statement = DB::connection()->getPdo()->prepare($query);
        $statement->bindValue(1, $productionDate->format('Ymd'));
        $statement->bindValue(2, $workcenterCode);

        $rc = $statement->execute();
        if ($rc === false) {
            throw new \RuntimeException('Failed to execute query: '.implode(', ', $statement->errorInfo()));
        }

        $dataSet1 = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->nextRowset();
        // $dataSet2 = $statement->fetchAll(PDO::FETCH_ASSOC);
        // Dummy data for testing purposes
        $dataSet2 = [
            [
                'shopOrderNumber' => 'SO-001',
                'labelId' => 1,
                'labelOrder' => 1,
                'labelNumber' => 1,
                'labelQuantityPlanned' => 60,
                'labelQuantityCompleted' => 60,
                'labelPlannedAt' => '2026-07-29 08:22:00',
                'labelCompletedAt' => '2026-07-29 08:20:00',
                'labelIsComplete' => 1,
                'labelStatus' => 'A',
                'productId' => 1823,
                'productionRecordId' => 0,
                'label' => 'SO-001-1',
            ],
            [
                'shopOrderNumber' => 'SO-001',
                'labelId' => 2,
                'labelOrder' => 2,
                'labelNumber' => 2,
                'labelQuantityPlanned' => 60,
                'labelQuantityCompleted' => 5,
                'labelPlannedAt' => '2026-07-29 08:50:00',
                'labelCompletedAt' => null,
                'labelIsComplete' => 0,
                'labelStatus' => 'R',
                'productId' => 1823,
                'productionRecordId' => 0,
                'label' => 'SO-001-2',
            ],
            [
                'shopOrderNumber' => 'SO-001',
                'labelId' => 3,
                'labelOrder' => 3,
                'labelNumber' => 3,
                'labelQuantityPlanned' => 55,
                'labelQuantityCompleted' => 0,
                'labelPlannedAt' => '2026-07-29 09:12:00',
                'labelCompletedAt' => null,
                'labelIsComplete' => 0,
                'labelStatus' => 'R',
                'productId' => 1823,
                'productionRecordId' => 0,
                'label' => 'SO-001-3',
            ],
        ];

        return [$dataSet1, $dataSet2];
    }

    public function getLabelProductionRecords(
        int $workcenterId,
        DateTimeImmutable $plannedDate
    ): array {
        $statement = DB::connection('iot')->getPdo()->prepare('EXEC dbo.GetProductionLabelsTracking ?, ?');
        $statement->execute([$plannedDate->format('Y-m-d'), $workcenterId]);
        $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->nextRowset();
        $labels = $statement->fetchAll(PDO::FETCH_ASSOC);

        return ['orders' => $orders, 'labels' => $labels];
    }

    public function getWorkcenters(): array
    {
        return [];
    }

    public function getCustomers(bool $withModels = false): array
    {
        $sql = <<< 'SQL'
        SELECT 
            c.code AS customerCode, 
            c.name AS customerName, 
            p.model 
        FROM projects p
        JOIN dbo.clients c
            ON (c.id = p.client_id)
        ORDER BY c.id
        SQL;

        $dataSet = DB::select($sql);
        if ($withModels) {
            return [];
        }

        return [];
    }
}
