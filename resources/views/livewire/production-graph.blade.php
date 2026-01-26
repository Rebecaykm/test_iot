<div>
    <div class="w-full h-full" x-data="productionChart" wire:ignore>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden h-full flex flex-col">
            @if(!$hasData)
                <div class="flex-1 flex items-center justify-center p-8">
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">No hay datos de producción</h3>
                        <p class="text-gray-500 dark:text-gray-400">No se encontraron registros para mostrar</p>
                    </div>
                </div>
            @else
                <div class="flex-1 p-6">
                    <div class="chart-container h-full min-h-[400px]">
                        <canvas id="{{ $chartId }}" class="w-full h-full"></canvas>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    @script
    <script>
        Alpine.data('productionChart', () => {
            let chart = null;
            let refreshInterval = null;

            return {
                async init() {
                    await new Promise(resolve => setTimeout(resolve, 100));

                    if (@json($hasData)) {
                        this.createChart();
                    }

                    if (@json($realTime)) {
                        this.startRealTimeUpdates();
                    }

                    $wire.on('refresh-graph', () => {
                        setTimeout(() => {
                            this.updateOrCreateChart();
                        }, 100);
                    });
                },

                async createChart() {
                    const ctx = document.getElementById(@json($chartId));

                    if (!ctx) {
                        console.error('Canvas element not found');
                        return;
                    }

                    if (chart) {
                        chart.destroy();
                        chart = null;
                    }

                    try {
                        const chartData = await $wire.getChartData();

                        if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                            console.log('No chart data available');
                            return;
                        }

                        const isDarkMode = document.documentElement.classList.contains('dark');

                        const datasets = [{
                            label: 'Plan',
                            data: chartData.planProgress,
                            backgroundColor: isDarkMode ? 'rgba(59, 130, 246, 0.3)' : 'rgba(37, 99, 235, 0.2)',
                            borderColor: isDarkMode ? 'rgb(96, 165, 250)' : 'rgb(29, 78, 216)',
                            borderWidth: 2,
                            borderRadius: 8,
                        }, {
                            label: 'Real',
                            data: chartData.producedData,
                            backgroundColor: chartData.realBgColors,
                            borderColor: chartData.realBorderColors,
                            borderWidth: 2,
                            borderRadius: 8,
                        }];

                        chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: chartData.labels,
                                datasets: datasets,
                            },
                            plugins: [ChartDataLabels],
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                                animation: false,
                                plugins: {
                                    datalabels: {
                                        anchor: 'start',
                                        align: 'center',
                                        color: isDarkMode ? '#f9fafb' : '#000',
                                        font: {
                                            weight: 'bold',
                                            size: 16
                                        },
                                        formatter: (value) => {
                                            return value > 0 ? value : '';
                                        }
                                    },
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            color: isDarkMode ? '#e5e7eb' : '#374151',
                                            font: {
                                                size: 14,
                                                weight: 'bold'
                                            }
                                        }
                                    },
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        grid: {
                                            drawBorder: false,
                                            color: isDarkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(0, 0, 0, 0.05)'
                                        },
                                        ticks: {
                                            font: {
                                                weight: 'bold'
                                            },
                                            color: isDarkMode ? '#9ca3af' : '#6b7280'
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            font: {
                                                weight: 'bold'
                                            },
                                            color: isDarkMode ? '#9ca3af' : '#6b7280',
                                            maxRotation: 90,
                                            minRotation: 90,
                                        }
                                    }
                                }
                            }
                        });
                    } catch (error) {
                        console.error('Error creating chart:', error);
                    }
                },

                async updateOrCreateChart() {
                    const hasData = @json($hasData);

                    if (!hasData) {
                        if (chart) {
                            chart.destroy();
                            chart = null;
                        }
                        return;
                    }

                    if (!chart) {
                        await this.createChart();
                    } else {
                        await this.updateChartData();
                    }
                },

                async updateChartData() {
                    if (!chart) return;

                    try {
                        const chartData = await $wire.getChartData();

                        if (!chartData || !chartData.labels) return;

                        const sameLength = chartData.labels.length === chart.data.labels.length;
                        let sameLabels = true;

                        if (sameLength) {
                            for (let i = 0; i < chartData.labels.length; i++) {
                                if (chartData.labels[i] !== chart.data.labels[i]) {
                                    sameLabels = false;
                                    break;
                                }
                            }
                        }

                        if (sameLength && sameLabels) {
                            chart.data.datasets[0].data = [...chartData.planProgress];
                            chart.data.datasets[1].data = [...chartData.producedData];
                            chart.data.datasets[1].backgroundColor = [...chartData.realBgColors];
                            chart.data.datasets[1].borderColor = [...chartData.realBorderColors];

                            chart.update('none');
                        } else {
                            await this.createChart();
                        }
                    } catch (error) {
                        console.error('Error updating chart data:', error);
                    }
                },

                startRealTimeUpdates() {
                    refreshInterval = setInterval(() => {
                        $wire.dispatchSelf("refresh-graph");
                    }, 10000);
                },

                destroy() {
                    if (refreshInterval) {
                        clearInterval(refreshInterval);
                    }
                    if (chart) {
                        chart.destroy();
                        chart = null;
                    }
                }
            }
        });
    </script>
    @endscript
</div>
