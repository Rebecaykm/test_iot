<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MdiReportExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithTitle
{
    public function __construct(private Collection $records) {}

    public function collection(): Collection
    {
        return $this->records->map(function ($rec) {
            return [
                $rec->area_name,
                $rec->line_name,
                $rec->work_number,
                $rec->work_name,
                $rec->part_number,
                $rec->part_name,
                $rec->mid,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'DEPARTAMENTO',
            'LÍNEA',
            'N° DE ESTACIÓN',
            'NOMBRE DE ESTACIÓN',
            'N° DE PARTE',
            'NOMBRE DE PARTE',
            'MDI',
        ];
    }

    public function title(): string
    {
        return 'Reporte de MDI';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Encabezado en negritas
            1 => ['font' => ['bold' => true]],
        ];
    }
}
