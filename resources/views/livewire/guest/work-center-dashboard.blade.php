<div>
    <header class="w-full bg-white dark:bg-gray-900 shadow-sm">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <!-- Row principal: logo | select + clear | auth buttons -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex-shrink-0">
                    <a href="{{ url('/') }}" class="flex items-center hover:opacity-80 transition-opacity duration-200">
                        <img src="{{ asset('images/ykm.png') }}" alt="Logo" class="h-10 w-auto">
                    </a>
                </div>

                <div class="flex-1 max-w-xs mx-4">
                    <label for="workCenterSelectHeader" class="sr-only">Filtrar Centros de Trabajo</label>

                    <select
                        id="workCenterSelectHeader"
                        wire:model.live="selectedWorkCenters"
                        multiple
                        size="2"
                        class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400 h-auto min-h-[38px] bg-white dark:bg-gray-800 dark:text-gray-200 pr-3"
                        aria-describedby="wc-help">
                        @foreach($allWorkCenters as $workCenter)
                        <option value="{{ $workCenter['id'] }}">
                            {{ $workCenter['full_name'] }}
                        </option>
                        @endforeach
                    </select>

                    <div class="mt-2">
                        @if(!empty($selectedWorkCenters))
                        <button
                            type="button"
                            wire:click="$set('selectedWorkCenters', [])"
                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm text-gray-700 dark:text-gray-200 transition"
                            title="Limpiar selección">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <span>Limpiar selección</span>
                        </button>
                        @endif
                    </div>
                </div>

                <div class="flex-shrink-0 flex items-center gap-2">
                    @if (Route::has('login'))
                        @auth
                        <a href="{{ url('/dashboard') }}" class="inline-block px-4 py-2 text-sm font-medium rounded-md bg-transparent border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-200 transition-colors">
                            Inicio
                        </a>
                        @else
                        <a href="{{ route('login') }}" class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                            Iniciar sesión
                        </a>
                            @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </header>


    <div x-data="workCenterDashboard" class="px-4 py-2 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <template x-if="$wire.areasData && $wire.areasData.length > 0">
            <div class="space-y-2">
                <template x-for="area in $wire.areasData" :key="'area-' + area.name">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                        <h2 class="text-xl font-bold mb-6 text-gray-800 dark:text-gray-100 border-b-2 border-blue-200 dark:border-blue-600 pb-3" x-text="area.name"></h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
                            <template x-for="workCenter in area.workCenters" :key="'wc-' + workCenter.id">
                                <a :href="'{{ route('guest.production-records', '') }}/' + workCenter.id"
                                    class="block hover:no-underline transition-transform duration-200 hover:scale-[1.02]">
                                    <div class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-600 hover:shadow-lg dark:hover:shadow-xl transition-shadow h-full">
                                        <div class="mb-2 flex items-start">
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-800 dark:text-gray-100" x-text="workCenter.name"></h3>
                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="workCenter.line"></div>
                                            </div>
                                            <div class="text-sm font-bold"
                                                :class="{
                                                    'text-green-600 dark:text-green-400': workCenter.percentage >= 90 && workCenter.percentage <= 100,
                                                    'text-yellow-600 dark:text-yellow-400': (workCenter.percentage >= 60 && workCenter.percentage < 90) || workCenter.percentage > 100,
                                                    'text-red-600 dark:text-red-400': workCenter.percentage < 60
                                                }">
                                                <span x-text="workCenter.percentage + '%'"></span>
                                            </div>
                                        </div>
                                        <div class="chart-container bg-gray-50 dark:bg-gray-600 rounded-lg p-2" style="height: 180px;">
                                            <canvas :id="'wc-chart-' + workCenter.id" wire:ignore></canvas>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                            <div class="bg-blue-50 dark:bg-blue-900/50 p-2 rounded text-center border dark:border-blue-800">
                                                <div class="text-blue-600 dark:text-blue-300 font-semibold">Plan</div>
                                                <div class="font-bold text-gray-800 dark:text-gray-100" x-text="workCenter.planned ?? 0"></div>
                                            </div>
                                            <div class="bg-green-50 dark:bg-green-900/50 p-2 rounded text-center border dark:border-green-800">
                                                <div class="text-green-600 dark:text-green-300 font-semibold">Real</div>
                                                <div class="font-bold text-gray-800 dark:text-gray-100" x-text="workCenter.total ?? 0"></div>
                                            </div>
                                            <!-- <div class="bg-yellow-50 dark:bg-yellow-900/50 p-2 rounded text-center border dark:border-yellow-800">
                                                <div class="text-yellow-600 dark:text-yellow-300 font-semibold">No Planeado</div>
                                                <div class="font-bold text-gray-800 dark:text-gray-100" x-text="workCenter.unplanned ?? 0"></div>
                                            </div> -->
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="(!$wire.areasData || $wire.areasData.length === 0) && $wire.selectedWorkCenters.length === 0">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-gray-100 dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No hay datos de producción disponibles</p>
            </div>
        </template>

        <template x-if="(!$wire.areasData || $wire.areasData.length === 0) && $wire.selectedWorkCenters.length > 0">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-gray-100 dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No hay datos disponibles para los centros de trabajo seleccionados</p>
                <button
                    wire:click="$set('selectedWorkCenters', [])"
                    class="mt-4 px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 text-sm transition-colors">
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

                    console.log('areasData raw ->', JSON.parse(JSON.stringify($wire.areasData))); // inspección
                    $wire.areasData.forEach(area => {
                        area.workCenters.forEach(workCenter => {
                            console.log('workCenter ->', workCenter); // <-- ¿tiene planned / total?
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

                    // Detectar tema oscuro
                    const isDarkMode = document.documentElement.classList.contains('dark') ||
                                     window.matchMedia('(prefers-color-scheme: dark)').matches;

                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Plan', 'Real'],
                            datasets: [
                                {
                                    label: 'Plan',
                                    data: [workCenter.planned, 0], // Solo Plan en la primera barra
                                    backgroundColor: isDarkMode ? 'rgba(59, 130, 246, 0.3)' : 'rgba(37, 99, 235, 0.2)',
                                    borderColor: isDarkMode ? 'rgb(96, 165, 250)' : 'rgb(29, 78, 216)',
                                    borderWidth: 2,
                                    borderRadius: 6
                                },
                                {
                                    label: 'Planeado',
                                    data: [0, workCenter.produced], // Planeado en la segunda barra (parte de abajo)
                                    backgroundColor: isDarkMode ? 'rgba(34, 197, 94, 0.3)' : 'rgba(22, 163, 74, 0.2)',
                                    borderColor: isDarkMode ? 'rgb(74, 222, 128)' : 'rgb(21, 128, 61)',
                                    borderWidth: 2,
                                    borderRadius: {
                                        bottomLeft: 6,
                                        bottomRight: 6,
                                        topLeft: workCenter.unplanned > 0 ? 0 : 6,
                                        topRight: workCenter.unplanned > 0 ? 0 : 6
                                    }
                                },
                                {
                                    label: 'No Planeado',
                                    data: [0, workCenter.unplanned], // No planeado en la segunda barra (parte de arriba)
                                    backgroundColor: isDarkMode ? 'rgba(251, 191, 36, 0.3)' : 'rgba(255, 159, 64, 0.2)',
                                    borderColor: isDarkMode ? 'rgb(250, 204, 21)' : 'rgb(255, 159, 64)',
                                    borderWidth: 2,
                                    borderRadius: {
                                        bottomLeft: 0,
                                        bottomRight: 0,
                                        topLeft: 6,
                                        topRight: 6
                                    }
                                }
                            ]
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
                                    backgroundColor: isDarkMode ? 'rgba(31, 41, 55, 0.9)' : 'rgba(0, 0, 0, 0.8)',
                                    titleColor: isDarkMode ? '#f9fafb' : '#fff',
                                    bodyColor: isDarkMode ? '#f9fafb' : '#fff',
                                    callbacks: {
                                        label: function(context) {
                                            const datasetLabel = context.dataset.label;
                                            const value = context.raw;
                                            return datasetLabel + ': ' + value;
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
                                        precision: 0,
                                        color: isDarkMode ? '#9ca3af' : '#6b7280'
                                    },
                                    stacked: true // Habilitar apilamiento en el eje Y
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: isDarkMode ? '#9ca3af' : '#6b7280'
                                    },
                                    stacked: true // Habilitar apilamiento en el eje X
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
