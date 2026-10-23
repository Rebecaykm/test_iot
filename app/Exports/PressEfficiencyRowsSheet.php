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
 * Tabla por troquel de PressEfficiency, con las mismas columnas que la vista.
 */
class PressEfficiencyRowsSheet implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithTitle
{
    public function __construct(private array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn ($row) => [
            $row['press'],
            $row['die'],
            implode(', ', $row['parts']),
            implode(', ', $row['names']),
            $row['spmStandard'],
            $row['spmReal'],
            $row['difference'],
            $row['minutes'],
            $row['piecesPerShot'],
            $row['expected'],
            $row['produced'],
            $row['efficiency'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Prensa',
            'Troquel (MDI)',
            'Número de parte',
            'Descripción',
            'SPM estándar',
            'SPM real',
            'Diferencia SPM',
            'Tiempo trabajado (min)',
            'Piezas por golpe',
            'Piezas esperadas',
            'Piezas producidas',
            'Eficiencia (%)',
        ];
    }

    public function title(): string
    {
        return 'Eficiencia por troquel';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Encabezado en negritas
            1 => ['font' => ['bold' => true]],
        ];
    }
}
