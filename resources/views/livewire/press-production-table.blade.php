<div x-data="pressProductionTable" class="w-full h-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden h-full flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-600">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <h2 class="text-lg font-bold text-blue-800 dark:text-blue-200 flex items-center">
                    @if($shiftActive)
                        <span class="w-2 h-2 bg-blue-500 rounded-full inline-block mr-2"></span>
                    @endif
                    {{ $workCenter }}
                </h2>
                <div class="flex items-center gap-2">
                    @if($shiftActive)
                        <div class="flex flex-wrap gap-2 text-sm font-medium">
                            <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">
                                {{ $plannedDate }}
                            </span>
                            <span class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded-full uppercase">
                                {{ $shiftName }}
                            </span>
                            {{-- @if($shiftStart && $shiftEnd)
                                <span class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded-full">
                                    {{ $shiftStart }} – {{ $shiftEnd }}
                                </span>
                            @endif --}}
                        </div>
                    @endif

                    <!-- Menú de opciones -->
                    <div class="relative"
                        x-data="{
                            open: false,
                            top: 0,
                            left: 0,
                            toggle(e) {
                                this.open = !this.open;
                                if (this.open) {
                                    const r = e.currentTarget.getBoundingClientRect();
                                    this.top = r.bottom + 4;
                                    this.left = Math.max(8, Math.min(r.right - 192, window.innerWidth - 192 - 8));
                                }
                            }
                        }">
                        <button @click="toggle($event)" @click.away="open = false"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <!-- Dropdown menu: fixed (no absolute) para que no lo recorte el
                             overflow-hidden de la tarjeta que lo contiene -->
                        <div x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="fixed w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                            :style="`top: ${top}px; left: ${left}px;`"
                            style="display: none;">

                            <a href="{{ url('/') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                </svg>
                                <span>Inicio</span>
                            </a>

                            @if($workCenterId)
                                <a href="{{ route('guest.production-records', $workCenterId) }}"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Detalles de Producción</span>
                                </a>
                            @endif

                            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/home') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
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
                                @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($shiftActive)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-gray-200 dark:bg-gray-600 border-b border-gray-200 dark:border-gray-600">
                <div class="bg-white dark:bg-gray-800 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Plan Turno</span>
                    <span class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($planTurno) }}</span>
                    {{-- <span class="text-xs text-gray-400 dark:text-gray-500">Piezas Totales</span> --}}
                </div>
                <div class="bg-white dark:bg-gray-800 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Plan Actual</span>
                    <span class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($planActual) }}</span>
                    {{-- <span class="text-xs text-gray-400 dark:text-gray-500">A las {{ $nowTime }}</span> --}}
                </div>

                {{-- Total Producido --}}
                <div class="bg-white dark:bg-gray-800 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Producido</span>
                    <span class="text-2xl font-bold text-green-700 dark:text-green-300">{{ number_format($totalProducido) }}</span>
                    {{-- <span class="text-xs text-gray-400 dark:text-gray-500">Piezas Reales</span> --}}
                </div>

                {{-- Diferencia --}}
                <div class="bg-white dark:bg-gray-800 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Diferencia</span>
                    <span class="text-2xl font-bold
                        @if($diferencia > 0)    text-green-700 dark:text-green-300
                        @elseif($diferencia < 0) text-red-700 dark:text-red-300
                        @else                    text-gray-700 dark:text-gray-300
                        @endif">
                        {{ $diferencia > 0 ? '+' : '' }}{{ number_format($diferencia) }}
                    </span>
                    {{-- <span class="text-xs
                        @if($diferencia > 0)    text-green-400
                        @elseif($diferencia < 0) text-red-400
                        @else                    text-gray-400 dark:text-gray-500
                        @endif">
                        @if($diferencia > 0)     Adelantado
                        @elseif($diferencia < 0) Atrasado
                        @else                    En tiempo
                        @endif
                    </span> --}}
                </div>

            </div>

            @if(!empty($data))
                <div class="grid grid-cols-4 gap-px bg-gray-200 dark:bg-gray-600">
                    {{-- Número de Parte — 2 columnas, un solo recuadro para todos los números --}}
                    <div class="col-span-2 bg-white dark:bg-gray-800 px-5 py-3 flex flex-col items-center gap-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Número de Parte
                        </span>
                        <div class="w-full divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($data as $index => $record)
                                <div wire:key="pn-{{ $index }}"
                                     class="flex items-center justify-center py-1.5 text-xl font-bold text-blue-700 dark:text-blue-300 tracking-wider">
                                    {{ $record['part_number'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Cantidad Producida — 2 columnas, un solo recuadro alineado con los números de parte --}}
                    <div class="col-span-2 bg-white dark:bg-gray-800 px-5 py-3 flex flex-col items-center gap-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Cantidad Producida
                        </span>
                        <div class="w-full divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($data as $index => $record)
                                <div wire:key="qty-{{ $index }}"
                                     class="flex items-center justify-center py-1.5 text-xl font-bold text-green-700 dark:text-green-300">
                                    {{ number_format($record['produced_quantity']) }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="flex items-center justify-center h-full">
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p>No hay datos de producción disponibles</p>
                </div>
            </div>
        @endif

    </div>

    @script
    <script>
        Alpine.data('pressProductionTable', () => ({
            init() {
                if (@json($realTime)) {
                    this.startAutoRefresh();
                }
            },
            startAutoRefresh() {
                setInterval(() => {
                    $wire.dispatchSelf('refresh-table');
                }, 30000);
            }
        }));
    </script>
    @endscript
</div>
