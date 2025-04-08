<div>
    <div x-data="chart">
        <div class="grid grid-cols-1 gap-4">
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="p-6 flex flex-col h-full">
                    <div class="chart-wrapper mb-5 flex-1">
                        <canvas wire:ignore id="{{ $chartId }}" class="w-full h-full"></canvas>
                    </div>
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
                        var ctx = document.getElementById(@json($chartId)).getContext('2d');
                        var chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: $wire.entangle("labels").live.initialValue,
                                datasets: [{
                                    label: 'Plan',
                                    data: $wire.entangle("plannedData").live.initialValue,
                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                    borderColor: 'rgb(255, 99, 132)',
                                    borderWidth: 2
                                }, {
                                    label: 'Real',
                                    data: $wire.entangle("producedData").live.initialValue,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgb(75, 192, 192)',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        stacked: true, // Las barras en el eje X estarán apiladas
                                    },
                                    y: {
                                        stacked: false, // Las barras en el eje Y no estarán apiladas
                                        ticks: {
                                            beginAtZero: true, // El eje Y comenzará en cero
                                        },
                                    }
                                },
                                plugins: {
                                    datalabels: {
                                        anchor: 'start',
                                        align: 'center',
                                        clamp: true,
                                        color: 'black',
                                        font: {
                                            weight: 'bold',
                                            size: 16
                                        },
                                        display: function (context) {
                                            // Mostrar la etiqueta solo si el valor no es 0
                                            return context.dataset.data[context.dataIndex] !== 0;
                                        },
                                        align: function (context) {
                                            // Para el dataset "Plan", se alineará arriba (bottom)
                                            // Para el dataset "Real", se alineará abajo (top)
                                            if (context.datasetIndex === 0) {
                                                return 'bottom'; // Plan, etiquetas arriba
                                            } else {
                                                return 'bottom'; // Real, etiquetas abajo
                                            }
                                        },
                                        offset: function (context) {
                                            // Ajuste de distancia para etiquetas
                                            if (context.datasetIndex === 0) {
                                                return 0; // Plan, desplazado 10 píxeles hacia abajo
                                            } else {
                                                return -20; // Real, desplazado 10 píxeles hacia arriba
                                            }
                                        }
                                    }
                                }
                            },
                            plugins: [ChartDataLabels],
                        });
                    }
                }
            });
        </script>
        @endscript
    </div>
</div>
