<div>
    <div class="w-full h-full" x-data="productionChart" wire:ignore>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden h-full flex flex-col">
            <!-- Mensaje cuando no hay datos -->
            @if(!$hasData)
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900">No hay datos de producción</h3>
                        <p class="text-gray-500">No se encontraron registros para el turno actual</p>
                    </div>
                </div>
            @else
                <!-- Gráfico -->
                <div class="flex-1 p-4">
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
            let planProgressInterval = null;

            return {
                async init() {
                    // Esperar un poco para asegurar que el DOM esté listo
                    await new Promise(resolve => setTimeout(resolve, 100));

                    if (@json($hasData)) {
                        this.createChart();
                    }

                    if (@json($realTime)) {
                        this.startRealTimeUpdates();
                    }

                    // Escuchar eventos de Livewire para recrear el gráfico
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

                    // Destruir gráfico existente si existe
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

                        const datasets = [{
                            label: 'Plan',
                            data: chartData.planProgress,
                            backgroundColor: 'rgba(37, 99, 235, 0.2)',
                            borderColor: 'rgb(29, 78, 216)',
                            borderWidth: 2,
                            borderRadius: 8,
                        }, {
                            label: 'Real',
                            data: chartData.producedData,
                            backgroundColor: 'rgba(22, 163, 74, 0.2)',
                            borderColor: 'rgb(21, 128, 61)',
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
                                plugins: {
                                    datalabels: {
                                        anchor: 'start',
                                        align: 'end',
                                        color: 'black',
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
                                        position: 'top'
                                    },
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        grid: {
                                            drawBorder: false,
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        },
                                        ticks: {
                                            font: {
                                                weight: 'bold'
                                            }
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
                                            maxRotation: 90,
                                            minRotation: 90,
                                        }
                                    }
                                },
                                animation: {
                                    duration: 1000,
                                    easing: 'easeOutQuart'
                                }
                            }
                        });

                        console.log('Chart created successfully');
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
                        this.createChart();
                    } else {
                        this.updateChartData();
                    }
                },

                startRealTimeUpdates() {
                    // Actualización de datos cada 10 segundos (optimizado)
                    refreshInterval = setInterval(() => {
                        $wire.dispatchSelf("refresh-graph");
                    }, 10000);

                    // Actualización del progreso del plan cada 5 segundos
                    planProgressInterval = setInterval(() => {
                        this.updatePlanProgress();
                    }, 5000);
                },

                async updateChartData() {
                    if (!chart) return;

                    try {
                        const chartData = await $wire.getChartData();

                        if (!chartData || !chartData.labels) {
                            return;
                        }

                        chart.data.labels = chartData.labels;
                        chart.data.datasets[0].data = chartData.planProgress;
                        chart.data.datasets[1].data = chartData.producedData;

                        chart.update('none');
                    } catch (error) {
                        console.error('Error updating chart data:', error);
                    }
                },

                async updatePlanProgress() {
                    if (!chart) return;

                    try {
                        const chartData = await $wire.getChartData();

                        if (!chartData) return;

                        chart.data.datasets[0].data = chartData.planProgress;
                        chart.update('none');
                    } catch (error) {
                        console.error('Error updating plan progress:', error);
                    }
                },

                destroy() {
                    if (refreshInterval) {
                        clearInterval(refreshInterval);
                    }
                    if (planProgressInterval) {
                        clearInterval(planProgressInterval);
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
