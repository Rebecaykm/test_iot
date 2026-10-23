<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Una gráfica Top 10 de PressEfficiency: los datos en A:C y la gráfica de barras a un lado.
 * Cada item es ['label' => troquel, 'press' => prensa, 'value' => valor].
 */
class PressEfficiencyChartSheet implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithCharts
{
    public function __construct(
        private string $sheetTitle,
        private string $chartTitle,
        private string $valueHeading,
        private array $items,
    ) {}

    public function collection(): Collection
    {
        return collect($this->items)->map(fn ($item) => [$item['label'], $item['press'], $item['value']]);
    }

    public function headings(): array
    {
        return ['Troquel (MDI)', 'Prensa', $this->valueHeading];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Encabezado en negritas
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function charts()
    {
        // Sin datos no hay rango que graficar.
        if (empty($this->items)) {
            return [];
        }

        $sheet = "'{$this->sheetTitle}'";
        $last = count($this->items) + 1;

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheet}!\$C\$1", null, 1)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$sheet}!\$A\$2:\$A\${$last}", null, $last - 1)],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$sheet}!\$C\$2:\$C\${$last}", null, $last - 1)]
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $chart = new Chart(
            'chart',
            new Title($this->chartTitle),
            null,
            new PlotArea(null, [$series]),
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            new Title($this->valueHeading)
        );
        $chart->setTopLeftPosition('E2');
        $chart->setBottomRightPosition('P24');

        return $chart;
    }
}
