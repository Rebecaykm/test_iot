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
                    <a
                        :href="'{{ route('guest.production-records', '') }}/' + workCenter.id"
                        class="block hover:no-underline transition-transform duration-200 hover:scale-[1.02]"
                    >
                        <div class="bg-white rounded-lg shadow p-4 border border-gray-200 hover:shadow-lg transition-shadow h-full">
                            <div class="mb-2 flex items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-800" x-text="workCenter.name"></h3>
                                    <div class="text-xs text-gray-500" x-text="workCenter.line"></div>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <template x-if="workCenter.percentage >= 100">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </template>
                                    <template x-if="workCenter.percentage >= 90 && workCenter.percentage < 100">
                                        <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>
                                        </svg>
                                    </template>
                                    <template x-if="workCenter.percentage < 90">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </template>
                                </div>
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
                                <div class="p-2 rounded text-center font-bold"
                                     :class="{
                                         'bg-red-50 text-red-600': workCenter.percentage < 60,
                                         'bg-yellow-50 text-yellow-600': workCenter.percentage >= 60 && workCenter.percentage < 90,
                                         'bg-green-50 text-green-600': workCenter.percentage >= 90 && workCenter.percentage <= 100,
                                         'bg-yellow-50 text-yellow-600': workCenter.percentage > 100
                                     }">
                                    <div class="font-semibold">Eficiencia</div>
                                    <div x-text="workCenter.percentage + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </a>
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
                                borderRadius: 4,
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
