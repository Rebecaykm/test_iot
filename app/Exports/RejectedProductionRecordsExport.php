<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Recibe los JSON del recolector ya decodificados ([campo => valor] por renglón).
 * Los encabezados son los campos del JSON, en el orden en que aparecen: si el
 * recolector agrega un campo, sale como una columna más sin cambiar nada aquí.
 * Los nombres se comparan sin distinguir mayúsculas ("Fecha" y "fecha" son la
 * misma columna).
 */
class RejectedProductionRecordsExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithTitle
{
    private array $keys;

    private Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows->map(fn ($row) => array_change_key_case($row, CASE_LOWER));
        $this->keys = $this->rows->flatMap(fn ($row) => array_keys($row))->unique()->values()->all();
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => array_map(function ($key) use ($row) {
            $value = $row[$key] ?? null;

            return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        }, $this->keys));
    }

    public function headings(): array
    {
        return array_map(fn ($key) => mb_strtoupper(str_replace('_', ' ', $key)), $this->keys);
    }

    public function title(): string
    {
        return 'Rechazos de IoT';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Encabezado en negritas
            1 => ['font' => ['bold' => true]],
        ];
    }
}
