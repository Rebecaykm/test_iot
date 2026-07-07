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

        return $statement->fetchAll(PDO::FETCH_ASSOC);
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
