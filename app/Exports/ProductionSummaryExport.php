<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionSummaryExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithEvents
{
    public function __construct(private Collection $records) {}

    public function collection(): Collection
    {
        return $this->records->map(function ($rec) {
            $minutes = ($rec->production_start && $rec->production_end)
                ? (int) Carbon::parse($rec->production_start)->diffInMinutes(Carbon::parse($rec->production_end))
                : '';

            return [
                $rec->work_number,
                $rec->work_name,
                $rec->part_number,
                $rec->part_name,
                Carbon::parse($rec->planned_date)->format('d/m/Y'),
                $rec->shift_name,
                (int) $rec->planned_quantity,
                (int) $rec->produced_quantity,
                $rec->production_start ? Carbon::parse($rec->production_start)->format('H:i') : '',
                $rec->production_end   ? Carbon::parse($rec->production_end)->format('H:i')   : '',
                $minutes,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Estación (N°)',
            'Estación',
            'N° de Parte',
            'Nombre de Parte',
            'Fecha',
            'Turno',
            'Planeada',
            'Producida',
            'Inicio',
            'Término',
            'Tiempo (min)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 22,
            'C' => 14,
            'D' => 24,
            'E' => 12,
            'F' => 10,
            'G' => 12,
            'H' => 12,
            'I' => 10,
            'J' => 10,
            'K' => 14,
        ];
    }

    public function title(): string
    {
        return 'Resumen de Producción';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $lastRow   = $this->records->count() + 1;
                $totalRows = $lastRow + 1;

                // Altura del encabezado
                $sheet->getRowDimension(1)->setRowHeight(22);

                // Estilos por fila de datos
                foreach ($this->records as $i => $rec) {
                    $row  = $i + 2;
                    $prod = (int) $rec->produced_quantity;
                    $plan = (int) $rec->planned_quantity;
                    $pct  = $plan > 0 ? ($prod / $plan) * 100 : 0;

                    $sheet->getRowDimension($row)->setRowHeight(18);

                    // Fondo alterno suave
                    $bgColor = ($i % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';
                    $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                        'font' => ['size' => 9],
                    ]);

                    // Alineación centrada para columnas numéricas y de hora
                    $sheet->getStyle("E{$row}:K{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Badge turno (col F) — azul suave
                    $sheet->getStyle("F{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF1D4ED8'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFF6FF']],
                    ]);

                    // Badge planeada (col G) — gris suave
                    $sheet->getStyle("G{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF475569'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                    ]);

                    // Badge producida (col H) — color según porcentaje
                    if ($pct >= 100) {
                        $fontColor = 'FF15803D';
                        $fillColor = 'FFF0FDF4';
                    } elseif ($pct >= 80) {
                        $fontColor = 'FF92400E';
                        $fillColor = 'FFFEFCE8';
                    } else {
                        $fontColor = 'FFB91C1C';
                        $fillColor = 'FFFEF2F2';
                    }

                    $sheet->getStyle("H{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => $fontColor], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fillColor]],
                    ]);

                    // Borde inferior suave
                    $sheet->getStyle("A{$row}:K{$row}")->getBorders()->getBottom()
                        ->setBorderStyle(Border::BORDER_THIN)
                        ->getColor()->setARGB('FFF1F5F9');
                }

                // Fila de totales
                $sheet->getRowDimension($totalRows)->setRowHeight(20);
                $totalPlanned  = $this->records->sum('planned_quantity');
                $totalProduced = $this->records->sum('produced_quantity');

                $sheet->setCellValue("A{$totalRows}", 'TOTALES');
                $sheet->mergeCells("A{$totalRows}:F{$totalRows}");
                $sheet->setCellValue("G{$totalRows}", (int) $totalPlanned);
                $sheet->setCellValue("H{$totalRows}", (int) $totalProduced);

                $sheet->getStyle("A{$totalRows}:K{$totalRows}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1E293B']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFE2E8F0']],
                    ],
                ]);

                $sheet->getStyle("A{$totalRows}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Borde inferior del encabezado
                $sheet->getStyle('A1:K1')->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setARGB('FF93C5FD');

                // Freezar primera fila
                $sheet->freezePane('A2');
            },
        ];
    }
}
