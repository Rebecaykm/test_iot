<?php

namespace App\Services;

use App\Enums\Shift;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PDO;

class ProductionTrackingService
{
    public function __construct() {}

    public function getProductionTrackingStatus(DateTimeImmutable $productionDate, Shift $shift, string $workcenterCode): array
    {
        $query = 'EXEC dbo.GetProductionTrackingStatus @productionDate = ?, @shift = ?, @workCenterCode = ?';

        $statement = DB::connection()->getPdo()->prepare($query);

        $statement->bindValue(1, $productionDate->format('Ymd'));
        $statement->bindValue(2, $shift->value);
        $statement->bindValue(3, $workcenterCode);

        $rc = $statement->execute();

        if ($rc === false) {
            throw new \RuntimeException('Failed to execute query: '.implode(', ', $statement->errorInfo()));
        }

        $dataSet = $statement->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($dataSet as $row) {
            $result[] = [
                'productionDate' => date_create_immutable($row['productionDate']),
                'shopOrderNumber' => $row['shopOrderNumber'] ?? null,
                'productCode' => $row['productCode'],
                'productDescription' => $row['productDescription'],
                'classCode' => $row['classCode'],
                'classDescription' => $row['classDescription'],
                'workCenterCode' => $row['workCenterCode'],
                'workCenterDescription' => $row['workCenterDescription'],
                'shift' => $row['shift'],
                'standardPackQuantity' => (int) $row['standardPackQuantity'],
                'laborHours' => (float) $row['laborHours'],
                'cycleTime' => (float) $row['cycleTime'],
                'AS4QuantityRequired' => (int) $row['AS4QuantityRequired'],
                'AS4QuantityFinished' => (int) $row['AS4QuantityFinished'],
                'IoTQuantityRequired' => (int) $row['IoTQuantityRequired'],
                'IoTQuantityFinished' => (int) $row['IoTQuantityFinished'],
                'AS4PlannedSequences' => (float) $row['AS4PlannedSequences'],
                'IoTPlannedSequences' => (float) $row['IoTPlannedSequences'],
                'AS4CompletedSequences' => (float) $row['AS4CompletedSequences'],
                'IoTCompletedSequences' => (float) $row['IoTCompletedSequences'],
                'recordConciliation' => $row['recordConciliation'],
            ];
        }

        return $result;
    }
}
