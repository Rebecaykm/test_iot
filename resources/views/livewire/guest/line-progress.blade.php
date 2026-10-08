<div wire:poll.30s.visible class="flex flex-col min-h-0">
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col min-h-0">

        {{-- Encabezado: filtros (línea → estación) y navegación de semana --}}
        <div class="px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700/70 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between flex-shrink-0">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3" wire:ignore>
                @foreach([['lpLinesFilter', 'Buscar línea...'], ['lpWcFilter', 'Buscar estación...']] as [$filter, $placeholder])
                    {{-- Select múltiple persistente (URL + navegador) --}}
                    <div x-data="{{ $filter }}" @click.away="open = false" class="relative w-full sm:w-64">
                        <button @click="toggleOpen()" x-ref="trigger" type="button"
                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700/70 rounded-lg text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            <span class="flex items-center gap-2 min-w-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" x-bind:d="icon" />
                                </svg>
                                <span x-text="label" class="truncate font-medium"></span>
                            </span>
                            <span class="flex items-center gap-1.5 flex-shrink-0">
                                <span x-show="selected.length > 0" x-text="selected.length"
                                    class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 text-xs font-bold bg-[#2563eb] dark:bg-[#3b82f6] text-white rounded-full"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </button>

                        <div x-show="open" x-transition.opacity.duration.100ms style="display: none;"
                            class="absolute left-0 mt-1.5 w-80 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-800 z-50 overflow-hidden">
                            <div class="p-2.5 border-b border-zinc-200 dark:border-zinc-800">
                                <input x-model="search" type="text" placeholder="{{ $placeholder }}"
                                    class="w-full px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-700/70 rounded-md bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="overflow-y-auto py-1" :style="`max-height: ${listMaxHeight}px`">
                                <template x-for="option in filtered" :key="option.id">
                                    <label class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                        <input type="checkbox" :checked="isSelected(option.id)" @change="toggle(option.id)"
                                            class="rounded border-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                        <span x-show="option.color !== undefined" class="w-1 h-4 rounded-full flex-shrink-0" :style="`background-color: ${option.color || '#71717a'}`"></span>
                                        <span class="min-w-0 leading-tight">
                                            <span class="block text-zinc-700 dark:text-zinc-200" x-text="text(option)"></span>
                                            <span x-show="sub(option)" class="block text-xs text-zinc-500 dark:text-zinc-400" x-text="sub(option)"></span>
                                        </span>
                                    </label>
                                </template>
                                <p x-show="filtered.length === 0" class="text-center text-sm text-zinc-500 dark:text-zinc-400 py-4">Sin resultados</p>
                            </div>
                            <div x-show="selected.length > 0" style="display: none;" class="p-2 border-t border-zinc-200 dark:border-zinc-800">
                                <button @click="clear()" type="button" x-text="clearText"
                                    class="w-full px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"></button>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Limpiar filtros: vuelve a toda la planta y borra la selección guardada en este navegador --}}
                <button type="button" x-cloak
                    x-data="{
                        clear() {
                            try {
                                localStorage.removeItem('lineProgress.lines');
                                localStorage.removeItem('lineProgress.wcs');
                            } catch (e) {}
                            $wire.clearFilters();
                        },
                    }"
                    x-show="($wire.selectedLines || []).length || ($wire.selectedWorkCenters || []).length"
                    @click="clear()"
                    class="inline-flex h-[34px] items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    Limpiar filtros
                </button>
            </div>

            <div class="flex items-center justify-end gap-1.5">
                {{-- Vista desglosada por número de parte y turno --}}
                <a href="{{ route('guest.part-progress') }}"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 mr-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-[#2563eb] dark:hover:text-[#60a5fa] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" />
                    </svg>
                    Por número de parte
                </a>
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

        {{-- Tabla: los días quedan fijos arriba; área y línea son renglones separadores --}}
        <div class="min-h-0 overflow-auto">
            {{-- Fondo del arco: se define una vez y cada medidor lo reutiliza con <use> --}}
            <svg width="0" height="0" class="absolute" aria-hidden="true"><defs><path id="lp-bg" d="M3 50A47 47 0 0 1 97 50L87 50A37 37 0 0 0 13 50Z" vector-effect="non-scaling-stroke"/></defs></svg>
            <table class="w-full min-w-[1150px] table-fixed border-collapse text-sm">
                <colgroup>
                    <col class="w-44">
                    <col class="w-24">
                    @foreach($days as $day)
                        <col>
                    @endforeach
                    <col class="w-28">
                </colgroup>
                <thead>
                    <tr>
                        <th class="sticky left-0 top-0 z-30 bg-zinc-50 dark:bg-zinc-800 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700/70">
                            Work Center
                        </th>
                        <th class="sticky left-44 top-0 z-30 bg-zinc-50 dark:bg-zinc-800 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-r border-zinc-200 dark:border-zinc-700/70">
                            Turno
                        </th>
                        @foreach($days as $day)
                            <th class="sticky top-0 z-20 px-3 py-2 text-center border-b border-l border-zinc-200 dark:border-zinc-700/70 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-[#1c2331]' : 'bg-zinc-50 dark:bg-zinc-800' }}">
                                <div class="text-xs font-bold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-200' }}">{{ $day['name'] }}</div>
                                <div class="text-[11px] font-normal {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $day['label'] }}</div>
                            </th>
                        @endforeach
                        <th class="sticky top-0 z-20 bg-zinc-50 dark:bg-zinc-800 px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-l border-zinc-200 dark:border-zinc-700/70">
                            Cant. Sin Orden
                        </th>
                    </tr>
                </thead>

                @forelse($areas as $area)
                    <tbody wire:key="area-{{ $area['name'] }}">
                        {{-- Área --}}
                        <tr>
                            <td colspan="{{ count($days) + 3 }}" class="bg-zinc-100 dark:bg-zinc-950 px-4 py-2 border-b border-zinc-200 dark:border-zinc-800">
                                <span class="sticky left-4 text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">{{ $area['name'] }}</span>
                            </td>
                        </tr>

                        @foreach($area['lines'] as $line)
                            {{-- Línea: con su color, igual que en / --}}
                            <tr wire:key="line-{{ $line['id'] }}">
                                <td colspan="{{ count($days) + 3 }}" class="bg-white dark:bg-zinc-900 px-4 pt-3 pb-2 border-b border-zinc-200 dark:border-zinc-800">
                                    <span class="sticky left-4 inline-flex items-center gap-2.5">
                                        <span class="w-1 h-5 rounded-full" style="background-color: {{ $line['color'] ?: '#71717a' }}"></span>
                                        <span class="text-base font-semibold text-zinc-800 dark:text-zinc-100">{{ $line['name'] }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($line['workCenters']) }} {{ count($line['workCenters']) === 1 ? 'estación' : 'estaciones' }}</span>
                                    </span>
                                </td>
                            </tr>
{{-- Renglones de estación: markup compacto a propósito (se repite cientos de veces por refresco; ver clases lp-* en app.css) --}}
@foreach($line['workCenters'] as $wc)
@foreach($wc['shifts'] as $i => $row)
<tr wire:key="wc-{{ $wc['id'] }}-{{ $row['shift'] }}"@if($loop->last) class="border-b border-zinc-200 dark:border-zinc-800"@endif>
@if($i === 0)
<td rowspan="{{ count($wc['shifts']) }}" class="lp-wc"><a href="{{ route('guest.work-center-progress', $wc['name']) }}" class="lp-wc-link" title="Ver detalle de {{ $wc['name'] }}">{{ $wc['name'] }}</a>@if($wc['inProgress'])<span class="lp-wc-live"><span class="lp-dot"></span>En Proceso</span>@endif</td>
@endif
<td class="lp-shift{{ $i ? ' lp-td-sep' : '' }}"><span class="lp-badge {{ $row['shift'] === 'D' ? 'lp-badge-D' : 'lp-badge-N' }}">{{ $row['shift_name'] }}</span></td>
@foreach($days as $day)
@php $c = $row['cells'][$day['date']] ?? null; @endphp
<td class="lp-td-day{{ $i ? ' lp-td-sep' : '' }}">@if(!$c || ($c['plan'] === 0 && $c['real'] === 0 && !$c['inProgress']))<div class="lp-empty">–</div>@else<div class="lp-cell{{ $c['inProgress'] ? ' lp-live' : '' }}" title="Plan {{ number_format($c['plan']) }} · Real {{ number_format($c['real']) }}{{ $c['inProgress'] ? ' · En Proceso' : '' }}">@if($c['inProgress'])<span class="lp-dot"></span>@endif<svg viewBox="0 0 100 56" class="lp-gauge" aria-hidden="true"><use href="#lp-bg" class="lp-arc-bg"/>@if($c['arc']['plan'])<path d="{{ $c['arc']['plan'] }}" class="lp-arc-plan"/>@endif @if($c['arc']['real'])<path d="{{ $c['arc']['real'] }}" class="lp-arc-real"/>@endif<text x="50" y="49" text-anchor="middle" class="lp-pct" font-size="{{ $c['percent'] === null ? 10 : 15 }}">{{ $c['percent'] === null ? 'Sin plan' : $c['percent'] . '%' }}</text></svg><div class="lp-qty"><span class="lp-qty-p">P: {{ number_format($c['plan']) }}</span><span class="lp-qty-r">R: {{ number_format($c['real']) }}</span></div></div>@endif</td>
@endforeach
{{-- Cant. Sin Orden: acumulado de la semana del renglón; clic = detalle por número de parte --}}
<td class="lp-td-day p-1 text-center{{ $i ? ' lp-td-sep' : '' }}">@if($row['noOrderTotal'] > 0)<button type="button" wire:click="showNoOrder({{ $wc['id'] }}, '{{ $row['shift'] }}')" title="Ver números de parte sin orden" class="lp-noorder">{{ number_format($row['noOrderTotal']) }}</button>@else<div class="lp-empty">–</div>@endif</td>
</tr>
@endforeach
@endforeach
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="{{ count($days) + 3 }}" class="px-4 py-16 text-center text-zinc-500 dark:text-zinc-400">
                                No hay producción ni plan en esta semana para los filtros seleccionados.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        {{-- Pie: leyenda y última actualización --}}
        <div class="pl-4 pr-16 py-2 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400 flex-shrink-0">
            <div class="flex items-center gap-3 font-medium text-zinc-600 dark:text-zinc-300">
                <span class="flex items-center gap-1.5"><span class="w-3 h-2.5 rounded-[2px] border border-[#2563eb] bg-[rgba(37,99,235,0.15)] dark:border-[#3b82f6] dark:bg-[rgba(59,130,246,0.16)]"></span>Plan</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-2.5 rounded-[2px] border border-[#059669] bg-[rgba(16,185,129,0.15)] dark:bg-[rgba(16,185,129,0.22)]"></span>Real</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-2.5 rounded-[2px] border border-[#ea580c] bg-[rgba(234,88,12,0.15)] dark:bg-[rgba(234,88,12,0.22)]"></span>Sin orden</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#059669] animate-pulse"></span>
                Actualización automática · {{ $lastUpdated }}
            </div>
        </div>
    </div>

    {{-- Modal: números de parte sin orden del renglón (estación + turno) en la semana --}}
    @if($noOrderDetail)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="no-order-title"
            x-data @keydown.escape.window="$wire.closeNoOrder()">
            <div class="absolute inset-0 bg-zinc-900/50 dark:bg-black/60" wire:click="closeNoOrder"></div>

            <div class="relative w-full max-w-lg max-h-[80vh] flex flex-col bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="no-order-title" class="flex items-center gap-2 text-base font-semibold text-zinc-800 dark:text-zinc-100">
                            <span class="w-3 h-3 rounded-[2px] border border-[#ea580c] bg-[rgba(234,88,12,0.15)] dark:bg-[rgba(234,88,12,0.22)]"></span>
                            Sin orden · {{ $noOrderDetail['workCenter'] }}
                        </h2>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Turno {{ $noOrderDetail['shift'] }} · {{ $weekLabel }}</p>
                    </div>
                    <button type="button" wire:click="closeNoOrder" title="Cerrar"
                        class="p-1 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="min-h-0 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Número de Parte</th>
                                <th class="px-5 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($noOrderDetail['parts'] as $part)
                                <tr>
                                    <td class="px-5 py-2">
                                        <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $part['number'] }}</div>
                                        @if($part['name'])
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-[18rem]">{{ $part['name'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-2 text-right font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">{{ number_format($part['quantity']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-5 py-8 text-center text-zinc-500 dark:text-zinc-400">Ya no hay piezas sin orden en este renglón.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-sm">
                    <span class="font-medium text-zinc-600 dark:text-zinc-300">Total sin orden</span>
                    <span class="font-bold tabular-nums text-zinc-800 dark:text-zinc-100">{{ number_format($noOrderDetail['total']) }}</span>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        const catalog = window.lineProgressCatalog || { lines: [], workCenters: [] };
        // ↑ Debe ser la primera línea: Alpine solo ejecuta este script como bloque si empieza con const/let
        //   (con un comentario al inicio lo evalúa como expresión y falla). Los catálogos los entrega
        //   la página una sola vez, para que no viajen en cada refresco.

        // ¿La URL ya trae el filtro? Livewire manda los arreglos como ?lines[0]=1
        const inUrl = (param) => [...new URLSearchParams(window.location.search).keys()]
            .some(k => k === param || k.startsWith(param + '['));

        // Select múltiple persistente (URL + navegador), compartido por los dos filtros
        const multiSelect = (cfg) => ({
            open: false,
            search: '',
            selected: $wire.entangle(cfg.model).live,
            icon: cfg.icon,
            clearText: cfg.clearText,
            listMaxHeight: 288,

            // La lista usa el espacio libre hasta el borde inferior de la ventana (entre 160 y 448 px);
            // 110 px = buscador + botón de limpiar + margen
            toggleOpen() {
                this.open = !this.open;
                if (this.open) {
                    const bottom = this.$refs.trigger.getBoundingClientRect().bottom;
                    this.listMaxHeight = Math.max(160, Math.min(448, window.innerHeight - bottom - 110));
                }
            },

            init() {
                // Sin el filtro en la URL: recuperar la selección guardada en este navegador
                if (!inUrl(cfg.param)) {
                    try {
                        const stored = JSON.parse(localStorage.getItem(cfg.storageKey) || '[]');
                        const valid = stored.filter(id => cfg.all().some(o => o.id === id));
                        if (valid.length) this.selected = valid;
                    } catch (e) { /* almacenamiento no disponible: se muestra todo */ }
                }

                this.$watch('selected', value => {
                    try { localStorage.setItem(cfg.storageKey, JSON.stringify(value || [])); } catch (e) {}
                });
            },

            get filtered() {
                const q = this.search.trim().toLowerCase();
                const options = cfg.options();
                return q ? options.filter(o => (cfg.text(o) + ' ' + (cfg.sub(o) || '')).toLowerCase().includes(q)) : options;
            },
            get label() {
                if (!this.selected.length) return cfg.allText;
                if (this.selected.length === 1) {
                    const o = cfg.all().find(o => o.id === this.selected[0]);
                    return o ? cfg.text(o) : `1 ${cfg.unit}`;
                }
                return `${this.selected.length} ${cfg.units}`;
            },
            text(o) { return cfg.text(o); },
            sub(o) { return cfg.sub(o); },
            isSelected(id) { return this.selected.includes(id); },
            toggle(id) {
                this.selected = this.isSelected(id)
                    ? this.selected.filter(x => x !== id)
                    : [...this.selected, id];
            },
            clear() { this.selected = []; },
        });

        Alpine.data('lpLinesFilter', () => multiSelect({
            model: 'selectedLines', param: 'lines', storageKey: 'lineProgress.lines',
            allText: 'Todas las líneas', unit: 'línea', units: 'líneas', clearText: 'Mostrar todas las líneas',
            icon: 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z',
            all: () => catalog.lines,
            options: () => catalog.lines,
            text: o => o.full_name,
            sub: () => '',
        }));

        // Estaciones: si hay líneas elegidas, solo se ofrecen las de esas líneas
        Alpine.data('lpWcFilter', () => multiSelect({
            model: 'selectedWorkCenters', param: 'wcs', storageKey: 'lineProgress.wcs',
            allText: 'Todas las estaciones', unit: 'estación', units: 'estaciones', clearText: 'Mostrar todas las estaciones',
            icon: 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437',
            all: () => catalog.workCenters,
            options: () => {
                const lines = $wire.selectedLines || [];
                return lines.length ? catalog.workCenters.filter(w => lines.includes(w.line_id)) : catalog.workCenters;
            },
            text: o => o.name,
            sub: o => o.line_name,
        }));
    </script>
    @endscript
</div>
