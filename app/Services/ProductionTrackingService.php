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
