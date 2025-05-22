<div>
    <div class="w-full h-full" x-data="chart" wire:ignore>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden  h-full flex flex-col">

            <!-- Gráfico - Ahora ocupa más espacio -->
            <div class="flex-1 p-4">
                <div class="chart-container h-full min-h-[400px]">
                    <canvas id="{{ $chartId }}" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

        @script
        <script>
            Alpine.data('chart', () => {
                return {
                    init() {
                        let plannedData = $wire.entangle("plannedData").live.initialValue;
                        let productionRate = $wire.entangle("productionRate").live.initialValue;
                        let productionStart = $wire.entangle("productionStart").live.initialValue;
                        let planProgress = new Array(plannedData.length).fill(0);

                        const ctx = document.getElementById(@json($chartId));

                        ds = [{
                            label: 'Plan',
                            data: planProgress,
                            backgroundColor: 'rgba(37, 99, 235, 0.2)', // Tailwind blue-600 con 80% opacidad
                            borderColor: 'rgb(29, 78, 216)', // Tailwind blue-700 (para borde)
                            borderWidth: 2,
                            borderRadius: 8,
                        }, {
                            label: 'Real',
                            data: $wire.entangle("producedData").live.initialValue,
                            backgroundColor: 'rgba(22, 163, 74, 0.2)', // Tailwind green-600 con 80% opacidad
                            borderColor: 'rgb(21, 128, 61)', // Tailwind green-700 (para borde)
                            borderWidth: 2,
                            borderRadius: 8,
                        }];
                        var chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: $wire.entangle("labels").live.initialValue,
                                datasets: ds,
                            },
                            plugins: [ChartDataLabels],
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                                plugins: {
                                    datalabels: {
                                        anchor: 'start',
                                        align: 'center',
                                        color: 'black',
                                        font: {
                                            weight: 'bold',
                                            size: 16
                                        }
                                    },
                                    legend: {
                                        display: false
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
                                            maxRotation: 0,
                                            minRotation: 0,
                                        }
                                    }
                                },
                                animation: {
                                    duration: 1000,
                                    easing: 'easeOutQuart'
                                }
                            }
                        });

                        if (@json($realTime)) {
                            // Real
                            setInterval(() => {
                                $wire.dispatchSelf("refresh-graph");
                                chart.data.labels = $wire.entangle("labels").live.initialValue;
                                chart.data.datasets[1].data = $wire.entangle("producedData").live.initialValue;
                                chart.update();
                            }, 1000);

                            // Plan
                            setInterval(function() {
                            let currentDate = new Date();

                            for (let i = 0; i < plannedData.length; i++) {

                                // Verifica si la tasa de producción es 0 o no válida
                                if (productionRate[i] == 0 || isNaN(productionRate[i])) {
                                    chart.data.datasets[0].data[i] = plannedData[i];
                                } else {
                                    let currentProductionRate = Math.round(3600 / productionRate[i]);
                                    let currentProductionStart = new Date(productionStart[i]);
                                    let timeDifference = Math.round((currentDate - currentProductionStart) / 1000);
                                    let currentQuantity = Math.round(timeDifference / currentProductionRate);

                                    if (planProgress[i] < plannedData[i]) {
                                        planProgress[i] = Math.min(currentQuantity, plannedData[i]);
                                    }
                                }

                                // Actualizar el valor del gráfico sin importar si hubo cambio o no
                                chart.data.datasets[0].data[i] = planProgress[i];
                            }
                            chart.update();
                        }, 1000);

                        }
                    }
                }
            });
        </script>
        @endscript
    </div>
</div>
