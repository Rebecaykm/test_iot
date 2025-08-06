<div>
    <div wire:ignore x-data="workCenterDashboard" class="p-4 space-y-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-bold text-gray-800">Producción por Centro de Trabajo</h1>
            <div class="text-sm text-gray-500">
                Actualizado: <span x-text="$wire.now" class="font-medium"></span>
            </div>
        </div>

        <template x-if="$wire.workCentersData && $wire.workCentersData.length > 0">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="(workCenter, index) in $wire.workCentersData" :key="'wc-' + workCenter.id">
                    <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
                        <div class="mb-2">
                            <h3 class="font-semibold text-lg mb-3 text-gray-800" x-text="workCenter.name"></h3>
                        </div>
                        <div class="chart-container" style="height: 180px;">
                            <canvas :id="'wc-chart-' + workCenter.id"></canvas>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                            <div class="bg-blue-50 p-2 rounded text-center">
                                <div class="text-blue-600 font-semibold">Plan</div>
                                <div class="font-bold" x-text="workCenter.planned"></div>
                            </div>
                            <div class="bg-green-50 p-2 rounded text-center">
                                <div class="text-green-600 font-semibold">Real</div>
                                <div class="font-bold" x-text="workCenter.produced"></div>
                            </div>
                            <div class="bg-gray-50 p-2 rounded text-center">
                                <div class="text-gray-600 font-semibold">Eficiencia</div>
                                <div class="font-bold" x-text="workCenter.percentage + '%'"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="!$wire.workCentersData || $wire.workCentersData.length === 0">
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <p class="text-gray-500">No hay datos de producción disponibles</p>
            </div>
        </template>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @script
    <script>
        Alpine.data('workCenterDashboard', () => {
            let charts = new Map();
            let refreshInterval = null;

            return {
                init() {
                    // Cargar datos iniciales
                    $wire.dispatchSelf('refresh-production-records');

                    // Configurar actualización en tiempo real si está habilitado
                    if (@json($realTime)) {
                        refreshInterval = setInterval(() => {
                            $wire.dispatchSelf('refresh-production-records');
                        }, 10000); // Actualizar cada 10 segundos
                    }

                    // Escuchar cambios en los datos para actualizar gráficas
                    $wire.on('refresh-production-records', () => {
                        this.$nextTick(() => {
                            this.updateAllCharts();
                        });
                    });
                },

                updateAllCharts() {
                    if (!$wire.workCentersData || $wire.workCentersData.length === 0) return;

                    $wire.workCentersData.forEach(workCenter => {
                        const canvasId = 'wc-chart-' + workCenter.id;
                        this.updateChart(canvasId, workCenter);
                    });
                },

                updateChart(canvasId, workCenter) {
                    const ctx = document.getElementById(canvasId);
                    if (!ctx) return;

                    // Destruir gráfica existente si la hay
                    if (charts.has(canvasId)) {
                        charts.get(canvasId).destroy();
                    }

                    // Crear nueva gráfica
                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Planeado', 'Producido'],
                            datasets: [{
                                data: [workCenter.planned, workCenter.produced],
                                backgroundColor: [
                                    'rgba(37, 99, 235, 0.2)',
                                    'rgba(22, 163, 74, 0.2)'
                                ],
                                borderColor: [
                                    'rgb(29, 78, 216)',
                                    'rgb(21, 128, 61)'
                                ],
                                borderWidth: 2,
                                borderRadius: 8,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false, // Desactivar animaciones
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        precision: 0
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });

                    // Guardar referencia a la gráfica
                    charts.set(canvasId, chart);
                },

                destroy() {
                    // Limpiar intervalos
                    if (refreshInterval) {
                        clearInterval(refreshInterval);
                    }

                    // Destruir todas las gráficas
                    charts.forEach(chart => chart.destroy());
                    charts.clear();
                }
            };
        });
    </script>
    @endscript
</div>
