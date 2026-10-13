<div x-data="pressProductionTable" class="w-full h-full">
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden h-full flex flex-col">
        <div class="px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700/70">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <h1 class="truncate text-xl font-bold text-zinc-800 dark:text-zinc-50 flex items-center">
                    <span class="w-2.5 h-2.5 flex-shrink-0 bg-[#2563eb] dark:bg-[#3b82f6] rounded-full inline-block mr-2.5"></span>
                    {{ $workCenter }}
                </h1>
                <div class="flex items-center gap-2">
                    @if($shiftActive)
                        <div class="flex flex-wrap gap-2 text-sm font-medium">
                            <span class="bg-zinc-600 dark:bg-zinc-600 text-white px-3 py-1 rounded-full">
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
                </div>
            </div>
        </div>

        @if($shiftActive)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-zinc-200 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-700">
                <div class="bg-white dark:bg-zinc-900 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Plan Turno</span>
                    <span class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($planTurno) }}</span>
                    {{-- <span class="text-xs text-zinc-500 dark:text-zinc-400">Piezas Totales</span> --}}
                </div>
                <div class="bg-white dark:bg-zinc-900 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Plan Actual</span>
                    <span class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($planActual) }}</span>
                    {{-- <span class="text-xs text-zinc-500 dark:text-zinc-400">A las {{ $nowTime }}</span> --}}
                </div>

                {{-- Total Producido --}}
                <div class="bg-white dark:bg-zinc-900 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Producido</span>
                    <span class="text-2xl font-bold text-green-700 dark:text-green-300">{{ number_format($totalProducido) }}</span>
                    {{-- <span class="text-xs text-zinc-500 dark:text-zinc-400">Piezas Reales</span> --}}
                </div>

                {{-- Diferencia --}}
                <div class="bg-white dark:bg-zinc-900 px-3 py-2 flex flex-col items-center justify-center gap-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Diferencia</span>
                    <span class="text-2xl font-bold
                        @if($diferencia > 0)    text-green-700 dark:text-green-300
                        @elseif($diferencia < 0) text-red-700 dark:text-red-300
                        @else                    text-zinc-700 dark:text-zinc-300
                        @endif">
                        {{ $diferencia > 0 ? '+' : '' }}{{ number_format($diferencia) }}
                    </span>
                    {{-- <span class="text-xs
                        @if($diferencia > 0)    text-green-400
                        @elseif($diferencia < 0) text-red-400
                        @else                    text-zinc-500 dark:text-zinc-400
                        @endif">
                        @if($diferencia > 0)     Adelantado
                        @elseif($diferencia < 0) Atrasado
                        @else                    En tiempo
                        @endif
                    </span> --}}
                </div>

            </div>

            @if(!empty($data))
                <div class="grid grid-cols-4 gap-px bg-zinc-200 dark:bg-zinc-700">
                    {{-- MDI (troquel) — 2 columnas, un solo recuadro para todos los troqueles en progreso --}}
                    <div class="col-span-2 bg-white dark:bg-zinc-900 px-5 py-3 flex flex-col items-center gap-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            NÚMERO DE PARTE
                        </span>
                        <div class="w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($data as $index => $record)
                                <div wire:key="pn-{{ $index }}"
                                     class="flex items-center justify-center py-1.5 text-xl font-bold text-blue-700 dark:text-blue-300 tracking-wider">
                                    {{ $record['mdi'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Golpes del turno — 2 columnas, un solo recuadro alineado con los MDI --}}
                    <div class="col-span-2 bg-white dark:bg-zinc-900 px-5 py-3 flex flex-col items-center gap-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Cant. Golpes
                        </span>
                        <div class="w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($data as $index => $record)
                                <div wire:key="qty-{{ $index }}"
                                     class="flex items-center justify-center py-1.5 text-xl font-bold text-green-700 dark:text-green-300">
                                    {{ number_format($record['shots']) }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="flex items-center justify-center h-full">
                <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                    <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
