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

    private function orDash($value): string
    {
        return ($value === null || $value === '') ? '-' : (string) $value;
    }

    public function collection(): Collection
    {
        return $this->records->map(function ($rec) {
            $minutes = ($rec->production_start && $rec->production_end)
                ? (int) Carbon::parse($rec->production_start)->diffInMinutes(Carbon::parse($rec->production_end))
                : null;

            $diff = (int) $rec->produced_quantity - (int) $rec->planned_quantity;

            return [
                $rec->work_number,
                $rec->work_name,
                $rec->part_number,
                $rec->part_name,
                $this->orDash($rec->shop_order_number),
                Carbon::parse($rec->planned_date)->format('d/m/Y'),
                $rec->shift_name,
                (int) $rec->planned_quantity,
                (int) $rec->produced_quantity,
                $diff,
                $rec->production_start ? Carbon::parse($rec->production_start)->format('H:i') : '-',
                $rec->production_end   ? Carbon::parse($rec->production_end)->format('H:i')   : '-',
                $this->orDash($minutes),
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
            'N° de Orden',
            'Fecha',
            'Turno',
            'Cant. Plan',
            'Cant. Real',
            'Diferencia',
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
            'E' => 16,
            'F' => 12,
            'G' => 10,
            'H' => 12,
            'I' => 12,
            'J' => 12,
            'K' => 10,
            'L' => 10,
            'M' => 14,
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
                'font'      => ['bold' => true],
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
                $sheet->getRowDimension(1)->setRowHeight(20);

                // Estilos por fila de datos
                foreach ($this->records as $i => $rec) {
                    $row  = $i + 2;
                    $prod = (int) $rec->produced_quantity;
                    $plan = (int) $rec->planned_quantity;
                    $pct  = $plan > 0 ? ($prod / $plan) * 100 : 0;

                    // Negrita para el número de orden
                    $sheet->getStyle("E{$row}")->getFont()->setBold(true);

                    // Alineación centrada para columnas numéricas y de hora
                    $sheet->getStyle("F{$row}:M{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Color de cantidad planeada (col H)
                    $sheet->getStyle("H{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF475569']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                    ]);

                    // Color de cantidad producida (col I) según porcentaje
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

                    $sheet->getStyle("I{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => $fontColor]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fillColor]],
                    ]);
                }

                // Fila de totales
                $totalPlanned  = $this->records->sum('planned_quantity');
                $totalProduced = $this->records->sum('produced_quantity');

                $sheet->setCellValue("A{$totalRows}", 'TOTALES');
                $sheet->mergeCells("A{$totalRows}:G{$totalRows}");
                $sheet->setCellValue("H{$totalRows}", (int) $totalPlanned);
                $sheet->setCellValue("I{$totalRows}", (int) $totalProduced);
                $sheet->setCellValue("J{$totalRows}", (int) $totalProduced - (int) $totalPlanned);

                $sheet->getStyle("A{$totalRows}:M{$totalRows}")->applyFromArray([
                    'font'      => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$totalRows}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Freezar primera fila
                $sheet->freezePane('A2');
            },
        ];
    }
}
