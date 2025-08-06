<div>
    <div wire:ignore x-data="workCenterDashboard" class="p-4 space-y-8">
        <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4 mb-4">
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-4 w-full sm:w-auto">
                <div class="w-full sm:w-64">
                    <label for="workCenterSelect" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Centro de Trabajo</label>
                    <select
                        id="workCenterSelect"
                        wire:model.live="selectedWorkCenter"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    >
                        <option value="">Todos los centros</option>
                        @foreach($allWorkCenters as $workCenter)
                            <option value="{{ $workCenter['id'] }}">{{ $workCenter['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <template x-if="$wire.areasData && $wire.areasData.length > 0">
            <div class="space-y-8">
                <template x-for="area in $wire.areasData" :key="'area-' + area.name">
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b-2 border-blue-200 pb-3" x-text="area.name"></h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
                            <template x-for="workCenter in area.workCenters" :key="'wc-' + workCenter.id">
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
                                            <div class="text-sm font-bold"
                                                :class="{
                                                    'text-green-600': workCenter.percentage >= 90 && workCenter.percentage <= 100,
                                                    'text-yellow-600': (workCenter.percentage >= 60 && workCenter.percentage < 90) || workCenter.percentage > 100,
                                                    'text-red-600': workCenter.percentage < 60
                                                }">
                                                <span x-text="workCenter.percentage + '%'"></span>
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
                                                <div class="text-green-600 font-semibold">Planeado</div>
                                                <div class="font-bold" x-text="workCenter.produced"></div>
                                            </div>
                                            <div class="bg-yellow-50 p-2 rounded text-center">
                                                <div class="text-yellow-600 font-semibold">No Planeado</div>
                                                <div class="font-bold" x-text="workCenter.unplanned"></div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="(!$wire.areasData || $wire.areasData.length === 0) && !$wire.selectedWorkCenter">
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <p class="text-gray-500">No hay datos de producción disponibles</p>
            </div>
        </template>

        <template x-if="(!$wire.areasData || $wire.areasData.length === 0) && $wire.selectedWorkCenter">
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <p class="text-gray-500">No hay datos disponibles para el centro de trabajo seleccionado</p>
                <button
                    wire:click="selectedWorkCenter = null"
                    class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
                >
                    Mostrar todos los centros
                </button>
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
                        }, 10000);
                    }

                    // Escuchar cambios en los datos para actualizar gráficas
                    $wire.on('refresh-production-records', () => {
                        this.$nextTick(() => {
                            this.updateAllCharts();
                        });
                    });
                },

                updateAllCharts() {
                    if (!$wire.areasData || $wire.areasData.length === 0) return;

                    $wire.areasData.forEach(area => {
                        area.workCenters.forEach(workCenter => {
                            const canvasId = 'wc-chart-' + workCenter.id;
                            this.updateChart(canvasId, workCenter);
                        });
                    });
                },

                updateChart(canvasId, workCenter) {
                    const ctx = document.getElementById(canvasId);
                    if (!ctx) return;

                    if (charts.has(canvasId)) {
                        charts.get(canvasId).destroy();
                    }

                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Plan', 'Planeado', 'No Planeado'],
                            datasets: [{
                                data: [workCenter.planned, workCenter.produced, workCenter.unplanned],
                                backgroundColor: [
                                    'rgba(37, 99, 235, 0.2)',
                                    'rgba(22, 163, 74, 0.2)',
                                    'rgba(255, 159, 64, 0.2)'
                                ],
                                borderColor: [
                                    'rgb(29, 78, 216)',
                                    'rgb(21, 128, 61)',
                                    'rgb(255, 159, 64)'
                                ],
                                borderWidth: 2,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': ' + context.raw;
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

                    charts.set(canvasId, chart);
                },

                destroy() {
                    if (refreshInterval) {
                        clearInterval(refreshInterval);
                    }

                    charts.forEach(chart => chart.destroy());
                    charts.clear();
                }
            };
        });
    </script>
    @endscript
</div>
