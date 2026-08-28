<div>
    <!-- ===== ENCABEZADO ===== -->
    <header class="w-full bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center gap-3">

                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ url('/') }}"
                        class="flex items-center hover:opacity-80 transition-opacity duration-200">
                        <img src="{{ asset('images/ykm.png') }}" alt="Logo" class="h-10 w-auto">
                    </a>
                </div>

                <!-- Espaciador -->
                <div class="flex-1"></div>

                <!-- Multi-select personalizado de líneas -->
                <div x-data="lineFilter" @click.away="open = false" class="w-44 relative">

                    <!-- Botón disparador -->
                    <button @click="open = !open" type="button"
                        class="w-full flex items-center justify-between gap-1.5 px-2.5 py-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:border-blue-400 dark:hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors text-left">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500 flex-shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                            </svg>
                            <span x-text="label" class="truncate text-gray-700 dark:text-gray-200 font-medium"></span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <span x-show="selected.length > 0" x-text="selected.length"
                                class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 text-xs font-bold bg-blue-600 text-white rounded-full">
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>

                    <!-- Dropdown -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-1.5 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
                        style="display: none;">
                        <!-- Búsqueda -->
                        <div class="p-2.5 border-b border-gray-100 dark:border-gray-700">
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <input x-model="search" type="text" placeholder="Buscar línea..."
                                    class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400">
                            </div>
                        </div>

                        <!-- Opciones -->
                        <div class="max-h-56 overflow-y-auto py-1">
                            <template x-for="line in filtered" :key="line.id">
                                <label
                                    class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
                                    :class="isSelected(line.id) ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                    <input type="checkbox" :checked="isSelected(line.id)" @change="toggle(line.id)"
                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                    <span class="text-gray-700 dark:text-gray-200 select-none leading-tight"
                                        x-text="line.full_name"></span>
                                </label>
                            </template>
                            <p x-show="filtered.length === 0"
                                class="text-center text-sm text-gray-400 dark:text-gray-500 py-4">
                                Sin resultados
                            </p>
                        </div>

                        <!-- Pie: limpiar -->
                        <div x-show="selected.length > 0" class="p-2 border-t border-gray-100 dark:border-gray-700"
                            style="display: none;">
                            <button @click="clear()" type="button"
                                class="w-full px-3 py-1.5 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors font-medium">
                                Limpiar selección
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menú de opciones -->
                <div class="flex-shrink-0 relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-400"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                        </svg>
                    </button>

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
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path
                                            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                    </svg>
                                    <span>Panel Administrativo</span>
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span>Iniciar Sesión</span>
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path
                                                d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
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
    </header>



    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div x-data="workCenterDashboard" class="px-4 py-2 bg-gray-50 dark:bg-gray-900 min-h-screen">
        @if ($shift)
            <div class="w-full py-2">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <span
                        class="px-3 py-1.5 text-sm font-semibold bg-blue-50 border border-blue-500 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}
                    </span>
                    <span
                        class="px-3 py-1.5 text-sm font-semibold bg-blue-50 border border-blue-500 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full whitespace-nowrap">
                        {{ $shift->name }}
                    </span>
                    <span
                        class="px-3 py-1.5 text-sm font-semibold bg-blue-50 border border-blue-500 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}
                    </span>
                </div>
            </div>
        @endif

        @if (!empty($areasData))
            <div class="space-y-4">
                @foreach ($areasData as $area)
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-gray-100 dark:border-gray-700">
                        <h2
                            class="text-2xl font-bold mb-6 text-gray-800 dark:text-white border-b-2 border-blue-200 dark:border-blue-500 pb-3">
                            {{ $area['name'] }}
                        </h2>

                        <div class="space-y-4">
                            @foreach ($area['lines'] as $line)
                                <div
                                    class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center mb-4">
                                        <div class="w-1 h-6 rounded-full mr-3"
                                            style="background-color: {{ $line['color'] }}"></div>
                                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                                            {{ $line['name'] }}</h3>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                        @foreach ($line['workCenters'] as $workCenter)
                                            <a href="{{ route('guest.production-records', $workCenter['id']) }}"
                                                class="block hover:no-underline transition-transform duration-200 hover:scale-[1.02]">
                                                <div
                                                    class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-600 hover:shadow-lg dark:hover:shadow-xl transition-shadow h-full">
                                                    <div class="mb-2 flex items-start justify-between">
                                                        <h4 class="font-medium text-gray-800 dark:text-white text-sm">
                                                            {{ $workCenter['name'] }}</h4>
                                                        <div
                                                            class="text-sm font-bold ml-2
                                                            @if ($workCenter['percentage'] >= 90 && $workCenter['percentage'] <= 100) text-green-600 dark:text-green-400
                                                            @elseif(($workCenter['percentage'] >= 60 && $workCenter['percentage'] < 90) || $workCenter['percentage'] > 100)
                                                                text-yellow-600 dark:text-yellow-400
                                                            @else
                                                                text-red-600 dark:text-red-400 @endif">
                                                            {{ $workCenter['percentage'] }}%
                                                        </div>
                                                    </div>
                                                    <div class="chart-container bg-gray-50 dark:bg-gray-700 rounded-lg p-2"
                                                        style="height: 150px;">
                                                        <canvas id="wc-chart-{{ $workCenter['id'] }}"
                                                            data-wc-id="{{ $workCenter['id'] }}"
                                                            data-planned="{{ $workCenter['planned'] }}"
                                                            data-produced="{{ $workCenter['produced'] }}"
                                                            data-unplanned="{{ $workCenter['unplanned'] }}"
                                                            wire:ignore>
                                                        </canvas>
                                                    </div>
                                                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                                        <div
                                                            class="bg-blue-50 dark:bg-blue-900/50 p-2 rounded text-center border dark:border-blue-800">
                                                            <div
                                                                class="text-blue-600 dark:text-blue-300 font-semibold">
                                                                Plan</div>
                                                            <div class="font-bold text-gray-800 dark:text-white">
                                                                {{ $workCenter['planned'] ?? 0 }}</div>
                                                        </div>
                                                        <div
                                                            class="bg-green-50 dark:bg-green-900/50 p-2 rounded text-center border dark:border-green-800">
                                                            <div
                                                                class="text-green-600 dark:text-green-300 font-semibold">
                                                                Real</div>
                                                            <div class="font-bold text-gray-800 dark:text-white">
                                                                {{ $workCenter['total'] ?? 0 }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif (!empty($selectedLines))
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-gray-100 dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No hay datos disponibles para las líneas seleccionadas</p>
                <button wire:click="$set('selectedLines', [])"
                    class="mt-4 px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 text-sm transition-colors">
                    Mostrar todas las líneas
                </button>
            </div>
        @else
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-gray-100 dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400">No hay datos de producción disponibles</p>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @script
        <script>
            Alpine.data('lineFilter', () => ({
                open: false,
                search: '',
                lines: @json($allLines),
                selected: @json($selectedLines),

                get label() {
                    if (this.selected.length === 0) return 'Todas las líneas';
                    if (this.selected.length === 1) {
                        const l = this.lines.find(l => l.id == this.selected[0]);
                        return l ? l.full_name : '1 línea seleccionada';
                    }
                    return this.selected.length + ' líneas seleccionadas';
                },

                get filtered() {
                    if (!this.search) return this.lines;
                    const q = this.search.toLowerCase();
                    return this.lines.filter(l => l.full_name.toLowerCase().includes(q));
                },

                toggle(id) {
                    const idx = this.selected.indexOf(id);
                    idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
                    $wire.set('selectedLines', this.selected);
                },

                clear() {
                    this.selected = [];
                    $wire.set('selectedLines', []);
                },

                isSelected(id) {
                    return this.selected.includes(id);
                }
            }));

            Alpine.data('workCenterDashboard', () => {
                let charts = new Map();
                let refreshInterval = null;

                return {
                    init() {
                        this.$nextTick(() => {
                            this.initializeAllCharts();
                        });

                        if (@json($realTime)) {
                            refreshInterval = setInterval(() => {
                                this.$wire.dispatchSelf('refresh-production-records');
                            }, 30000);
                        }

                        this.$watch(() => this.$wire.chartKey, () => {
                            this.$nextTick(() => this.updateAllCharts());
                        });
                    },

                    initializeAllCharts() {
                        document.querySelectorAll('canvas[id^="wc-chart-"]').forEach(canvas => {
                            this.createChart(canvas.id, {
                                id: canvas.dataset.wcId,
                                planned: parseInt(canvas.dataset.planned) || 0,
                                produced: parseInt(canvas.dataset.produced) || 0,
                                unplanned: parseInt(canvas.dataset.unplanned) || 0
                            });
                        });
                    },

                    updateAllCharts() {
                        const existingIds = new Set(
                            Array.from(document.querySelectorAll('canvas[id^="wc-chart-"]')).map(c => c.id)
                        );

                        charts.forEach((chart, id) => {
                            if (!existingIds.has(id)) {
                                chart.destroy();
                                charts.delete(id);
                            }
                        });

                        document.querySelectorAll('canvas[id^="wc-chart-"]').forEach(canvas => {
                            const wc = {
                                id: canvas.dataset.wcId,
                                planned: parseInt(canvas.dataset.planned) || 0,
                                produced: parseInt(canvas.dataset.produced) || 0,
                                unplanned: parseInt(canvas.dataset.unplanned) || 0
                            };
                            const existing = charts.get(canvas.id);
                            if (existing && existing.canvas === canvas) {
                                this.updateChart(canvas.id, wc);
                            } else {
                                this.createChart(canvas.id, wc);
                            }
                        });
                    },

                    createChart(canvasId, wc) {
                        const ctx = document.getElementById(canvasId);
                        if (!ctx) return;

                        if (charts.has(canvasId)) charts.get(canvasId).destroy();

                        const dark = document.documentElement.classList.contains('dark');

                        charts.set(canvasId, new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Plan', 'Real'],
                                datasets: [{
                                    label: 'Plan',
                                    data: [wc.planned, 0],
                                    backgroundColor: dark ? 'rgba(59,130,246,0.3)' :
                                        'rgba(37,99,235,0.2)',
                                    borderColor: dark ? 'rgb(96,165,250)' : 'rgb(29,78,216)',
                                    borderWidth: 2,
                                    borderRadius: 6
                                }, {
                                    label: 'Producido',
                                    data: [0, wc.produced],
                                    backgroundColor: dark ? 'rgba(34,197,94,0.3)' :
                                        'rgba(22,163,74,0.2)',
                                    borderColor: dark ? 'rgb(74,222,128)' : 'rgb(21,128,61)',
                                    borderWidth: 2,
                                    borderRadius: {
                                        bottomLeft: 6,
                                        bottomRight: 6,
                                        topLeft: wc.unplanned > 0 ? 0 : 6,
                                        topRight: wc.unplanned > 0 ? 0 : 6
                                    }
                                }, {
                                    label: 'No Planeado',
                                    data: [0, wc.unplanned],
                                    backgroundColor: dark ? 'rgba(251,191,36,0.3)' :
                                        'rgba(255,159,64,0.2)',
                                    borderColor: dark ? 'rgb(250,204,21)' : 'rgb(255,159,64)',
                                    borderWidth: 2,
                                    borderRadius: {
                                        bottomLeft: 0,
                                        bottomRight: 0,
                                        topLeft: 6,
                                        topRight: 6
                                    }
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
                                        backgroundColor: dark ? 'rgba(31,41,55,0.9)' : 'rgba(0,0,0,0.8)',
                                        titleColor: dark ? '#f9fafb' : '#fff',
                                        bodyColor: dark ? '#f9fafb' : '#fff',
                                        callbacks: {
                                            label: ctx => ctx.dataset.label + ': ' + ctx.raw
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        stacked: true,
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            precision: 0,
                                            color: dark ? '#9ca3af' : '#6b7280'
                                        }
                                    },
                                    x: {
                                        stacked: true,
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            color: dark ? '#9ca3af' : '#6b7280'
                                        }
                                    }
                                }
                            }
                        }));
                    },

                    updateChart(canvasId, wc) {
                        const chart = charts.get(canvasId);
                        if (!chart) return;
                        chart.data.datasets[0].data = [wc.planned, 0];
                        chart.data.datasets[1].data = [0, wc.produced];
                        chart.data.datasets[2].data = [0, wc.unplanned];
                        chart.update('none');
                    },

                    destroy() {
                        if (refreshInterval) clearInterval(refreshInterval);
                        charts.forEach(chart => chart.destroy());
                        charts.clear();
                    }
                };
            });
        </script>
    @endscript
</div>
