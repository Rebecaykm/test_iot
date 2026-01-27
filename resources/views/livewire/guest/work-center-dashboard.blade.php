<div>
    <header class="w-full bg-white dark:bg-gray-900 shadow-sm">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between gap-4">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ url('/') }}" class="flex items-center hover:opacity-80 transition-opacity duration-200">
                        <img src="{{ asset('images/ykm.png') }}" alt="Logo" class="h-10 w-auto">
                    </a>
                </div>

                <!-- Select de Líneas con botón X integrado -->
                <div class="flex-1 max-w-xs mx-4">
                    <label for="lineSelectHeader" class="sr-only">Filtrar Líneas</label>

                    <div class="relative">
                        <select id="lineSelectHeader" wire:model.live="selectedLines" multiple size="2"
                            class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400 h-auto min-h-[38px] bg-white dark:bg-gray-800 dark:text-gray-200 pr-10"
                            aria-describedby="line-help">
                            @foreach ($allLines as $line)
                                <option value="{{ $line['id'] }}">
                                    {{ $line['full_name'] }}
                                </option>
                            @endforeach
                        </select>

                        @if (!empty($selectedLines))
                            <button type="button" wire:click="$set('selectedLines', [])"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                                title="Limpiar selección">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Info del Turno y Menú de opciones -->
                <div class="flex-shrink-0 flex items-center gap-2">
                    {{-- @if ($realTime)
                        <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                    @endif --}}

                    @if ($shift)
                        <span class="px-3 py-1.5 text-sm font-semibold bg-blue-50 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}
                        </span>
                        <span class="px-3 py-1.5 text-sm font-semibold bg-blue-50 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full whitespace-nowrap">
                            {{ $shift->name }}
                        </span>
                        <span class="px-3 py-1.5 text-sm font-semibold bg-blue-50 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}
                        </span>
                    @endif

                    <!-- Menú de opciones (tres puntos) -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <!-- Dropdown menu -->
                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                            style="display: none;">

                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/home') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                        </svg>
                                        <span>Panel Administrativo</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Iniciar Sesión</span>
                                    </a>

                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                                            </svg>
                                            <span>Registrarse</span>
                                        </a>
                                    @endif
                                @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div x-data="workCenterDashboard" class="px-4 py-2 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <template x-if="$wire.areasData && $wire.areasData.length > 0">
            <div class="space-y-4">
                <!-- Áreas -->
                <template x-for="area in $wire.areasData" :key="'area-' + area.name">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white border-b-2 border-blue-200 dark:border-blue-500 pb-3" x-text="area.name"></h2>

                        <!-- Líneas dentro del Área -->
                        <div class="space-y-4">
                            <template x-for="line in area.lines" :key="'line-' + line.id">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center mb-4">
                                        <div class="w-1 h-6 rounded-full mr-3" :style="'background-color: ' + line.color"></div>
                                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white" x-text="line.name"></h3>
                                    </div>

                                    <!-- Centros de Trabajo de la Línea -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                        <template x-for="workCenter in line.workCenters" :key="'wc-' + workCenter.id">
                                            <a :href="'{{ route('guest.production-records', '') }}/' + workCenter.id"
                                                class="block hover:no-underline transition-transform duration-200 hover:scale-[1.02]">
                                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-600 hover:shadow-lg dark:hover:shadow-xl transition-shadow h-full">
                                                    <div class="mb-2 flex items-start justify-between">
                                                        <h4 class="font-medium text-gray-800 dark:text-white text-sm" x-text="workCenter.name"></h4>
                                                        <div class="text-sm font-bold ml-2"
                                                            :class="{
                                                                'text-green-600 dark:text-green-400': workCenter.percentage >= 90 && workCenter.percentage <= 100,
                                                                'text-yellow-600 dark:text-yellow-400': (workCenter.percentage >= 60 && workCenter.percentage < 90) || workCenter.percentage > 100,
                                                                'text-red-600 dark:text-red-400': workCenter.percentage < 60
                                                            }">
                                                            <span x-text="workCenter.percentage + '%'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="chart-container bg-gray-50 dark:bg-gray-700 rounded-lg p-2" style="height: 150px;">
                                                        <canvas :id="'wc-chart-' + workCenter.id" wire:ignore></canvas>
                                                    </div>
                                                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                                        <div class="bg-blue-50 dark:bg-blue-900/50 p-2 rounded text-center border dark:border-blue-800">
                                                            <div class="text-blue-600 dark:text-blue-300 font-semibold">Plan</div>
                                                            <div class="font-bold text-gray-800 dark:text-white" x-text="workCenter.planned ?? 0"></div>
                                                        </div>
                                                        <div class="bg-green-50 dark:bg-green-900/50 p-2 rounded text-center border dark:border-green-800">
                                                            <div class="text-green-600 dark:text-green-300 font-semibold">Real</div>
                                                            <div class="font-bold text-gray-800 dark:text-white" x-text="workCenter.total ?? 0"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="(!$wire.areasData || $wire.areasData.length === 0) && $wire.selectedLines.length === 0">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-gray-100 dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No hay datos de producción disponibles</p>
            </div>
        </template>

        <template x-if="(!$wire.areasData || $wire.areasData.length === 0) && $wire.selectedLines.length > 0">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-gray-100 dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No hay datos disponibles para las líneas seleccionadas</p>
                <button wire:click="$set('selectedLines', [])"
                    class="mt-4 px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 text-sm transition-colors">
                    Mostrar todas las líneas
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
                        $wire.dispatchSelf('refresh-production-records');

                        if (@json($realTime)) {
                            refreshInterval = setInterval(() => {
                                $wire.dispatchSelf('refresh-production-records');
                            }, 30000);
                        }

                        $wire.on('refresh-production-records', () => {
                            this.$nextTick(() => {
                                this.updateAllCharts();
                            });
                        });
                    },

                    updateAllCharts() {
                        if (!$wire.areasData || $wire.areasData.length === 0) return;

                        $wire.areasData.forEach(area => {
                            area.lines.forEach(line => {
                                line.workCenters.forEach(workCenter => {
                                    const canvasId = 'wc-chart-' + workCenter.id;
                                    this.updateChart(canvasId, workCenter);
                                });
                            });
                        });
                    },

                    updateChart(canvasId, workCenter) {
                        const ctx = document.getElementById(canvasId);
                        if (!ctx) return;

                        if (charts.has(canvasId)) {
                            charts.get(canvasId).destroy();
                        }

                        const isDarkMode = document.documentElement.classList.contains('dark') ||
                            window.matchMedia('(prefers-color-scheme: dark)').matches;

                        const chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Plan', 'Real'],
                                datasets: [{
                                        label: 'Plan',
                                        data: [workCenter.planned, 0],
                                        backgroundColor: isDarkMode ? 'rgba(59, 130, 246, 0.3)' :
                                            'rgba(37, 99, 235, 0.2)',
                                        borderColor: isDarkMode ? 'rgb(96, 165, 250)' : 'rgb(29, 78, 216)',
                                        borderWidth: 2,
                                        borderRadius: 6
                                    },
                                    {
                                        label: 'Planeado',
                                        data: [0, workCenter.produced],
                                        backgroundColor: isDarkMode ? 'rgba(34, 197, 94, 0.3)' :
                                            'rgba(22, 163, 74, 0.2)',
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
                                        data: [0, workCenter.unplanned],
                                        backgroundColor: isDarkMode ? 'rgba(251, 191, 36, 0.3)' :
                                            'rgba(255, 159, 64, 0.2)',
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
                                        backgroundColor: isDarkMode ? 'rgba(31, 41, 55, 0.9)' :
                                            'rgba(0, 0, 0, 0.8)',
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
                                        stacked: true
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            color: isDarkMode ? '#9ca3af' : '#6b7280'
                                        },
                                        stacked: true
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
