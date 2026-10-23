<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte de eficiencia de prensas (/guest/press-efficiency): la tabla por troquel
 * y una hoja por cada gráfica Top 10, con sus datos y la gráfica de Excel.
 */
class PressEfficiencyExport implements WithMultipleSheets
{
    public function __construct(private array $report) {}

    public function sheets(): array
    {
        return [
            new PressEfficiencyRowsSheet($this->report['rows']),
            new PressEfficiencyChartSheet(
                'Top 10 desviación',
                'Troqueles con mayor desviación respecto al SPM estándar',
                'Diferencia SPM (golpes/min)',
                $this->report['deviation']
            ),
            new PressEfficiencyChartSheet(
                'Top 10 eficiencia',
                'Troqueles con menor eficiencia',
                'Eficiencia (%)',
                $this->report['efficiency']
            ),
        ];
    }
}
