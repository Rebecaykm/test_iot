<div class="w-full">
    <div x-data="productionChart" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
        <div class="relative h-[450px]">
            <canvas id="{{ $chartId }}" wire:ignore></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productionChart', () => ({
                chart: null,
                init() {
                    const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
                    let pData = @json($plannedData);
                    let rData = @json($producedData);
                    let dData = @json($differenceData);

                    // Detectar modo oscuro
                    const isDarkMode = document.documentElement.classList.contains('dark');

                    // Colores para modo oscuro
                    const textColor = isDarkMode ? '#e5e7eb' : '#111827';
                    const gridColor = isDarkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(0, 0, 0, 0.1)';

                    this.chart = new Chart(ctx, {
                        plugins: [ChartDataLabels],
                        data: {
                            labels: @json($labels),
                            datasets: [{
                                    type: 'line',
                                    label: 'Plan',
                                    data: pData,
                                    borderColor: isDarkMode ? '#60a5fa' : '#1e40af',
                                    borderWidth: 3,
                                    pointRadius: 6,
                                    fill: false,
                                    tension: 0.2,
                                    datalabels: {
                                        align: 'top',
                                        anchor: 'end',
                                        offset: 8
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Real',
                                    data: rData.map((v, i) => v > pData[i] ? pData[i] : v),
                                    backgroundColor: isDarkMode ? 'rgba(34, 197, 94, 0.3)' : 'rgba(34, 197, 94, 0.2)',
                                    borderColor: isDarkMode ? '#4ade80' : '#16a34a',
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    stack: 's1',
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Faltante',
                                    data: dData.map(v => v < 0 ? Math.abs(v) : 0),
                                    backgroundColor: isDarkMode ? 'rgba(239, 68, 68, 0.3)' : 'rgba(239, 68, 68, 0.2)',
                                    borderColor: isDarkMode ? '#f87171' : '#dc2626',
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    stack: 's1',
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Excedente',
                                    data: dData.map(v => v > 0 ? v : 0),
                                    backgroundColor: isDarkMode ? 'rgba(59, 130, 246, 0.3)' : 'rgba(59, 130, 246, 0.2)',
                                    borderColor: isDarkMode ? '#93c5fd' : '#2563eb',
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    stack: 's1',
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                    }
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    stacked: true,
                                    ticks: {
                                        color: textColor,
                                        font: {
                                            weight: 'bold',
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        color: gridColor
                                    }
                                },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    ticks: {
                                        color: textColor,
                                        font: {
                                            weight: 'bold',
                                            size: 11
                                        }
                                    },
                                    grid: {
                                        color: gridColor
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        color: textColor,
                                        font: {
                                            size: 12,
                                        },
                                        padding: 15,
                                        usePointStyle: true,
                                        pointStyle: 'rectRounded',
                                        generateLabels: function(chart) {
                                            const datasets = chart.data.datasets;
                                            return datasets.map((dataset, i) => ({
                                                text: dataset.label,
                                                fillStyle: dataset.backgroundColor,
                                                strokeStyle: dataset.borderColor,
                                                lineWidth: dataset.borderWidth,
                                                hidden: !chart.isDatasetVisible(i),
                                                index: i,
                                                pointStyle: 'rectRounded'
                                            }));
                                        }
                                    },
                                    onClick: function(e, legendItem, legend) {
                                        const index = legendItem.index;
                                        const chart = legend.chart;

                                        if (chart.isDatasetVisible(index)) {
                                            chart.hide(index);
                                            legendItem.hidden = true;
                                        } else {
                                            chart.show(index);
                                            legendItem.hidden = false;
                                        }
                                    }
                                },
                                datalabels: {
                                    color: textColor,
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    formatter: function(value, context) {
                                        if (!context.chart.isDatasetVisible(context.datasetIndex)) {
                                            return '';
                                        }
                                        return value !== 0 ? value : '';
                                    }
                                }
                            }
                        }
                    });

                    window.addEventListener('update-chart', (e) => {
                        const d = e.detail[0];
                        pData = d.planned;
                        rData = d.produced.map(v => v === null ? 0 : v);
                        dData = d.difference;

                        this.chart.data.labels = d.labels;
                        this.chart.data.datasets[0].data = pData;
                        this.chart.data.datasets[1].data = rData.map((v, i) => v > pData[i] ? pData[i] : v);
                        this.chart.data.datasets[2].data = dData.map(v => v < 0 ? Math.abs(v) : 0);
                        this.chart.data.datasets[3].data = dData.map(v => v > 0 ? v : 0);

                        this.chart.update();
                    });
                }
            }));
        });
    </script>
</div>
