<?php

namespace App\Dtos;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;

readonly class ProductionTracking implements Arrayable
{
    public function __construct(
        public string $areaName,
        public string $lineName,
        public DateTimeImmutable $productionDate,
        public ?string $shopOrderNumber,
        public string $productCode,
        public string $productDescription,
        public ?string $customer,
        public ?string $projects,
        public string $classCode,
        public string $classDescription,
        public string $workCenterCode,
        public string $workCenterDescription,
        public string $shift,
        public int $standardPackQuantity,
        public ?float $laborHours,
        public float $sequencesPerHour,
        public int $AS4QuantityRequired,
        public int $AS4QuantityFinished,
        public int $IoTQuantityRequired,
        public int $IoTQuantityFinished,
        public float $AS4PlannedSequences,
        public float $IoTPlannedSequences,
        public float $AS4CompletedSequences,
        public float $IoTCompletedSequences,
        public string $recordConciliation,
        public int $productionOrder,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            areaName: $data['areaName'],
            lineName: $data['lineName'],
            productionDate: date_create_immutable($data['productionDate']),
            shopOrderNumber: $data['shopOrderNumber'] ?? null,
            productCode: $data['productCode'],
            productDescription: $data['productDescription'],
            customer: $data['customer'] ?? null,
            projects: $data['projects'] ?? null,
            classCode: $data['classCode'],
            classDescription: $data['classDescription'],
            workCenterCode: $data['workCenterCode'],
            workCenterDescription: $data['workCenterDescription'],
            shift: $data['shift'],
            standardPackQuantity: (int) $data['standardPackQuantity'],
            laborHours: (float) $data['laborHours'],
            sequencesPerHour: (float) $data['sequencesPerHour'],
            AS4QuantityRequired: (int) $data['AS4QuantityRequired'],
            AS4QuantityFinished: (int) $data['AS4QuantityFinished'],
            IoTQuantityRequired: (int) $data['IoTQuantityRequired'],
            IoTQuantityFinished: (int) $data['IoTQuantityFinished'],
            AS4PlannedSequences: (float) $data['AS4PlannedSequences'],
            IoTPlannedSequences: (float) $data['IoTPlannedSequences'],
            AS4CompletedSequences: (float) $data['AS4CompletedSequences'],
            IoTCompletedSequences: (float) $data['IoTCompletedSequences'],
            recordConciliation: $data['recordConciliation'],
            productionOrder: (int) $data['productionOrder'],
        );
    }

    public function toArray(): array
    {
        return [
            'productionDate' => $this->productionDate->format('Ymd'),
            'shopOrderNumber' => $this->shopOrderNumber,
            'productCode' => $this->productCode,
            'productDescription' => $this->productDescription,
            'classCode' => $this->classCode,
            'classDescription' => $this->classDescription,
            'workCenterCode' => $this->workCenterCode,
            'workCenterDescription' => $this->workCenterDescription,
            'shift' => $this->shift,
            'standardPackQuantity' => $this->standardPackQuantity,
            'laborHours' => $this->laborHours,
            'sequencesPerHour' => $this->sequencesPerHour,
            'AS4QuantityRequired' => $this->AS4QuantityRequired,
            'AS4QuantityFinished' => $this->AS4QuantityFinished,
            'IoTQuantityRequired' => $this->IoTQuantityRequired,
            'IoTQuantityFinished' => $this->IoTQuantityFinished,
            'AS4PlannedSequences' => $this->AS4PlannedSequences,
            'IoTPlannedSequences' => $this->IoTPlannedSequences,
            'AS4CompletedSequences' => $this->AS4CompletedSequences,
            'IoTCompletedSequences' => $this->IoTCompletedSequences,
            'recordConciliation' => $this->recordConciliation,
        ];
    }
}
