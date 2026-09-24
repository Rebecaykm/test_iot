<div wire:poll.30s class="flex flex-col min-h-0">
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden flex flex-col min-h-0">

        {{-- Encabezado: buscador y navegación de semana --}}
        <div class="px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700/70 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between flex-shrink-0">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar número de parte..."
                    class="w-full sm:w-72 pl-8 pr-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-700/70 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 placeholder-zinc-500 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                </div>

                {{-- Switch: solo renglones con órdenes en producción --}}
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" wire:model.live="onlyInProgress" class="peer sr-only">
                    <span class="relative h-5 w-9 flex-shrink-0 rounded-full bg-zinc-500 dark:bg-zinc-500 transition-colors peer-checked:bg-[#2563eb] dark:peer-checked:bg-[#3b82f6] peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-2 dark:peer-focus-visible:ring-offset-zinc-900
                        after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-4"></span>
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200 whitespace-nowrap">En Proceso</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-1.5">
                @unless($isCurrentWeek)
                    <button wire:click="currentWeek" type="button"
                        class="px-2.5 py-1 mr-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Semana actual
                    </button>
                @endunless
                <button wire:click="prevWeek" type="button" title="Semana anterior"
                    class="p-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 whitespace-nowrap min-w-[10rem] text-center">{{ $weekLabel }}</span>
                <button wire:click="nextWeek" type="button" title="Semana siguiente"
                    class="p-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>

        {{-- Tabla: ocupa todo el alto disponible y hace scroll interno --}}
        <div class="min-h-0 overflow-auto">
            <table class="w-full min-w-[1100px] table-fixed border-collapse text-sm">
                <colgroup>
                    <col class="w-40">
                    <col class="w-24">
                    @foreach($days as $day)
                        <col>
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        <th class="sticky left-0 top-0 z-30 bg-zinc-50 dark:bg-zinc-800 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700/70">
                            Número de Parte
                        </th>
                        <th class="sticky left-40 top-0 z-30 bg-zinc-50 dark:bg-zinc-800 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-r border-zinc-200 dark:border-zinc-700/70">
                            Turno
                        </th>
                        @foreach($days as $day)
                            <th class="sticky top-0 z-20 px-3 py-2 text-center border-b border-l border-zinc-200 dark:border-zinc-700/70 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-blue-500/10' : 'bg-zinc-50 dark:bg-zinc-800' }}">
                                <div class="text-xs font-bold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-200' }}">{{ $day['name'] }}</div>
                                <div class="text-[11px] font-normal {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $day['label'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($parts as $part)
                        @foreach($part['shifts'] as $i => $row)
                            <tr wire:key="row-{{ $part['part_number'] }}-{{ $row['shift'] }}">
                                {{-- Número de parte (agrupa los turnos con rowspan) --}}
                                @if($i === 0)
                                    <td rowspan="{{ count($part['shifts']) }}"
                                        class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-1.5 align-middle text-sm font-semibold text-zinc-800 dark:text-zinc-100 tracking-wide border-r border-zinc-200 dark:border-zinc-800 truncate"
                                        title="{{ $part['part_number'] }}">
                                        {{ $part['part_number'] }}
                                    </td>
                                @endif

                                {{-- Turno --}}
                                <td class="sticky left-40 z-10 bg-white dark:bg-zinc-900 px-2 py-1.5 text-center align-middle border-r border-zinc-200 dark:border-zinc-800">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                        {{ $row['shift'] === 'D'
                                            ? 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:ring-1 dark:ring-inset dark:ring-amber-400/20 dark:text-amber-300'
                                            : 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20 dark:bg-indigo-400/10 dark:ring-1 dark:ring-inset dark:ring-indigo-400/20 dark:text-indigo-300' }}">
                                        {{ $row['shift_name'] }}
                                    </span>
                                </td>

                                {{-- Celdas por día: una barra por orden (plan = azul, real = verde) --}}
                                @foreach($days as $day)
                                    @php $cell = $row['cells'][$day['date']] ?? []; @endphp
                                    <td class="p-0 align-middle border-l border-zinc-200 dark:border-zinc-800">
                                        @if(empty($cell))
                                            <div class="py-2 text-center text-zinc-300 dark:text-zinc-700 text-[11px]">–</div>
                                        @else
                                            {{-- Varias órdenes el mismo día van lado a lado: el renglón no crece en alto --}}
                                            <div class="flex divide-x divide-zinc-200 dark:divide-zinc-800">
                                                @foreach($cell as $rec)
                                                    {{-- Orden en producción ("En progreso"): contorno azul, el mismo del plan --}}
                                                    <div class="flex-1 min-w-0 px-2 py-1.5 bg-white dark:bg-zinc-900 {{ $rec['inProgress'] ? 'ring-2 ring-inset ring-[#2563eb] dark:ring-[#3b82f6]' : '' }}"
                                                        title="Orden {{ $rec['order'] }} · Plan {{ number_format($rec['plan']) }} · Real {{ number_format($rec['real']) }} ({{ $rec['percent'] }}%){{ $rec['inProgress'] ? ' · En producción' : '' }}">
                                                        @if($rec['inProgress'])
                                                            <div class="mb-1 flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-[#2563eb] dark:text-[#60a5fa] whitespace-nowrap">
                                                                <span class="relative flex h-2 w-2">
                                                                    <span class="absolute inline-flex h-full w-full rounded-full bg-[#2563eb] dark:bg-[#3b82f6] opacity-75 animate-ping"></span>
                                                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-[#2563eb] dark:bg-[#3b82f6]"></span>
                                                                </span>
                                                                En Proceso
                                                            </div>
                                                        @endif
                                                        {{-- Barra: el total (azul) es lo planeado, el relleno (verde) es lo real --}}
                                                        {{-- Mismos colores que /shift-production-timeline: relleno translúcido + contorno sólido --}}
                                                        <div class="relative h-6 w-full rounded-[3px] border border-[#2563eb] bg-[rgba(37,99,235,0.15)] dark:border-[#3b82f6] dark:bg-[rgba(59,130,246,0.16)]">
                                                            @if($rec['percent'] > 0)
                                                                <div class="absolute -inset-y-px -left-px rounded-[3px] border border-[#059669] bg-[rgba(16,185,129,0.15)] dark:border-[#059669] dark:bg-[rgba(16,185,129,0.22)] transition-[width] duration-500"
                                                                    style="width: calc({{ $rec['percent'] }}% + 2px)"></div>
                                                            @endif
                                                            {{-- Cantidad real: dentro del relleno; si el relleno es muy angosto, justo después de él --}}
                                                            <span class="absolute inset-y-0 flex items-center text-[11px] font-bold tabular-nums text-zinc-900 dark:text-zinc-50 {{ $rec['percent'] >= 35 ? 'left-0 justify-end pr-1.5' : 'pl-1' }}"
                                                                style="{{ $rec['percent'] >= 35 ? 'width' : 'left' }}: {{ $rec['percent'] }}%">
                                                                {{ number_format($rec['real']) }}
                                                            </span>
                                                        </div>
                                                        {{-- Plan y real debajo de la barra: texto en tinta neutra, el color lo lleva la muestra --}}
                                                        <div class="mt-0.5 flex justify-between gap-1 text-[11px] font-semibold tabular-nums whitespace-nowrap">
                                                            <span class="flex items-center gap-1 text-zinc-600 dark:text-zinc-300"><span class="w-2 h-2 rounded-[2px] bg-[#2563eb] dark:bg-[#3b82f6]"></span>P: {{ number_format($rec['plan']) }}</span>
                                                            <span class="flex items-center gap-1 text-zinc-800 dark:text-zinc-100"><span class="w-2 h-2 rounded-[2px] bg-[#059669]"></span>R: {{ number_format($rec['real']) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ count($days) + 2 }}" class="px-4 py-16 text-center text-zinc-500 dark:text-zinc-400">
                                <svg class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ $onlyInProgress ? 'No hay órdenes en producción en esta semana.' : 'No hay órdenes planeadas en esta semana.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie: última actualización --}}
        <div class="px-4 py-2 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-end text-xs text-zinc-500 dark:text-zinc-400 flex-shrink-0">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#059669] animate-pulse"></span>
                Actualización automática · {{ $lastUpdated }}
            </div>
        </div>
    </div>
</div>
