<div wire:poll.30s class="flex flex-col min-h-0">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col min-h-0">

        {{-- Encabezado: buscador y navegación de semana --}}
        <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-600 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between flex-shrink-0">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar número de parte..."
                    class="w-full sm:w-72 pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            </div>

            <div class="flex items-center justify-end gap-1.5">
                @unless($isCurrentWeek)
                    <button wire:click="currentWeek" type="button"
                        class="px-2.5 py-1 mr-1 rounded-lg border border-gray-300 dark:border-gray-600 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Semana actual
                    </button>
                @endunless
                <button wire:click="prevWeek" type="button" title="Semana anterior"
                    class="p-1 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap min-w-[10rem] text-center">{{ $weekLabel }}</span>
                <button wire:click="nextWeek" type="button" title="Semana siguiente"
                    class="p-1 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
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
                        <th class="sticky left-0 top-0 z-30 bg-gray-100 dark:bg-gray-700 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                            Número de Parte
                        </th>
                        <th class="sticky left-40 top-0 z-30 bg-gray-100 dark:bg-gray-700 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 border-b border-r border-gray-200 dark:border-gray-600">
                            Turno
                        </th>
                        @foreach($days as $day)
                            <th class="sticky top-0 z-20 px-3 py-2 text-center border-b border-l border-gray-200 dark:border-gray-600 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-blue-900/60' : 'bg-gray-100 dark:bg-gray-700' }}">
                                <div class="text-xs font-bold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-200' }}">{{ $day['name'] }}</div>
                                <div class="text-[11px] font-normal {{ $day['isToday'] ? 'text-blue-500 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $day['label'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($parts as $part)
                        @foreach($part['shifts'] as $i => $row)
                            <tr wire:key="row-{{ $part['part_number'] }}-{{ $row['shift'] }}">
                                {{-- Número de parte (agrupa los turnos con rowspan) --}}
                                @if($i === 0)
                                    <td rowspan="{{ count($part['shifts']) }}"
                                        class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-1.5 align-middle text-sm font-semibold text-gray-800 dark:text-gray-100 tracking-wide border-r border-gray-200 dark:border-gray-700 truncate"
                                        title="{{ $part['part_number'] }}">
                                        {{ $part['part_number'] }}
                                    </td>
                                @endif

                                {{-- Turno --}}
                                <td class="sticky left-40 z-10 bg-white dark:bg-gray-800 px-2 py-1.5 text-center align-middle border-r border-gray-200 dark:border-gray-700">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                        {{ $row['shift'] === 'D'
                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                            : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' }}">
                                        {{ $row['shift_name'] }}
                                    </span>
                                </td>

                                {{-- Celdas por día: una barra por orden (plan = azul, real = verde) --}}
                                @foreach($days as $day)
                                    @php $cell = $row['cells'][$day['date']] ?? []; @endphp
                                    <td class="p-0 align-middle border-l border-gray-200 dark:border-gray-700">
                                        @if(empty($cell))
                                            <div class="py-2 text-center text-gray-300 dark:text-gray-600 text-[11px]">–</div>
                                        @else
                                            {{-- Varias órdenes el mismo día van lado a lado: el renglón no crece en alto --}}
                                            <div class="flex divide-x divide-gray-200 dark:divide-gray-700">
                                                @foreach($cell as $rec)
                                                    {{-- Orden en producción ("En progreso"): contorno azul, el mismo del plan --}}
                                                    <div class="flex-1 min-w-0 px-2 py-1.5 bg-white dark:bg-gray-800 {{ $rec['inProgress'] ? 'ring-2 ring-inset ring-[#2563eb] dark:ring-[#60a5fa]' : '' }}"
                                                        title="Orden {{ $rec['order'] }} · Plan {{ number_format($rec['plan']) }} · Real {{ number_format($rec['real']) }} ({{ $rec['percent'] }}%){{ $rec['inProgress'] ? ' · En producción' : '' }}">
                                                        @if($rec['inProgress'])
                                                            <div class="mb-1 flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-[#2563eb] dark:text-[#60a5fa] whitespace-nowrap">
                                                                <span class="relative flex h-2 w-2">
                                                                    <span class="absolute inline-flex h-full w-full rounded-full bg-[#2563eb] dark:bg-[#60a5fa] opacity-75 animate-ping"></span>
                                                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-[#2563eb] dark:bg-[#60a5fa]"></span>
                                                                </span>
                                                                En producción
                                                            </div>
                                                        @endif
                                                        {{-- Barra: el total (azul) es lo planeado, el relleno (verde) es lo real --}}
                                                        {{-- Mismos colores que /shift-production-timeline: relleno translúcido + contorno sólido --}}
                                                        <div class="relative h-6 w-full rounded-[3px] border border-[#2563eb] bg-[rgba(37,99,235,0.15)] dark:border-[#60a5fa] dark:bg-[rgba(59,130,246,0.18)]">
                                                            @if($rec['percent'] > 0)
                                                                <div class="absolute -inset-y-px -left-px rounded-[3px] border border-[#059669] bg-[rgba(16,185,129,0.15)] dark:border-[#34d399] dark:bg-[rgba(52,211,153,0.18)] transition-[width] duration-500"
                                                                    style="width: calc({{ $rec['percent'] }}% + 2px)"></div>
                                                            @endif
                                                            {{-- Cantidad real: dentro del relleno; si el relleno es muy angosto, justo después de él --}}
                                                            <span class="absolute inset-y-0 flex items-center text-[11px] font-bold tabular-nums text-gray-900 dark:text-white {{ $rec['percent'] >= 35 ? 'left-0 justify-end pr-1.5' : 'pl-1' }}"
                                                                style="{{ $rec['percent'] >= 35 ? 'width' : 'left' }}: {{ $rec['percent'] }}%">
                                                                {{ number_format($rec['real']) }}
                                                            </span>
                                                        </div>
                                                        {{-- Plan y real debajo de la barra --}}
                                                        <div class="mt-0.5 flex justify-between gap-1 text-[11px] font-semibold tabular-nums whitespace-nowrap">
                                                            <span class="text-[#2563eb] dark:text-[#60a5fa]">P: {{ number_format($rec['plan']) }}</span>
                                                            <span class="text-[#059669] dark:text-[#34d399]">R: {{ number_format($rec['real']) }}</span>
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
                            <td colspan="{{ count($days) + 2 }}" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                No hay órdenes planeadas en esta semana.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie: última actualización --}}
        <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Actualización automática · {{ $lastUpdated }}
            </div>
        </div>
    </div>
</div>
