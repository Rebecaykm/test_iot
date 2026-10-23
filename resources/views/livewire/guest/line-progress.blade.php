@php
    use App\Livewire\Guest\LineProgress;

    // Controles compactos, iguales a los de /guest/press-efficiency
    $control = 'h-8 border border-zinc-300 dark:border-zinc-700/70 rounded-lg bg-white dark:bg-zinc-900 text-xs text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors';
    $panel = 'absolute left-0 mt-1 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-800 z-50 overflow-hidden';

        $nameHeader = [1 => 'Línea', 2 => 'Estación', 3 => 'Número de parte'][$level];
    $filter = [1 => ['lpLinesFilter', 'Buscar línea o estación...'], 2 => ['lpWcFilter', 'Buscar estación...'], 3 => null][$level];
    $colCount = count($columns) + 2;
    $loadingTargets = 'prevWeek,nextWeek,currentWeek,selectedLines,selectedWorkCenters,clearFilters';
@endphp

<div wire:poll.30s.visible x-data class="flex flex-col gap-3 min-h-0 flex-1" @keydown.escape.window="$wire.closeModals()">

    {{-- Barra superior: volver y filtro a la izquierda; semana a la derecha --}}
    <div class="relative z-20 flex-shrink-0 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm px-3 py-2 flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <div class="flex flex-wrap items-center gap-1.5">
                @if($backLink)
                    <a href="{{ $backLink }}" class="{{ $control }} inline-flex items-center gap-1 pl-2 pr-3 font-medium text-zinc-700 dark:text-zinc-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                        Volver
                    </a>
                @endif

                {{-- Filtro (líneas en el inicio, estaciones dentro de una línea): guardado en la URL y en el navegador --}}
                @if($filter)
                    <div wire:ignore x-data="{{ $filter[0] }}" @click.outside="open = false" class="relative w-full sm:w-48">
                        <button @click="toggleOpen()" x-ref="trigger" type="button" class="{{ $control }} w-full flex items-center justify-between gap-1.5 px-2.5">
                            <span class="flex items-center gap-1.5 min-w-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                                </svg>
                                <span x-text="label" class="truncate font-medium"></span>
                            </span>
                            <span class="flex items-center gap-1 flex-shrink-0">
                                <span x-show="selected.length > 0" x-text="selected.length"
                                    class="inline-flex items-center justify-center min-w-[1rem] h-4 px-1 text-[10px] font-bold bg-[#2563eb] dark:bg-[#3b82f6] text-white rounded-full"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </span>
                        </button>

                        <div x-show="open" x-transition.opacity.duration.100ms style="display: none;" class="{{ $panel }} w-full sm:w-72">
                            <div class="p-2 border-b border-zinc-200 dark:border-zinc-800">
                                <input x-model="search" type="text" placeholder="{{ $filter[1] }}"
                                    class="w-full h-7 px-2.5 py-0 text-xs border border-zinc-300 dark:border-zinc-700/70 rounded-md bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="overflow-y-auto py-1" :style="`max-height: ${listMaxHeight}px`">
                                <template x-for="option in filtered" :key="option.id">
                                    <label class="flex items-center gap-2 px-3 py-1.5 text-xs cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                        <input type="checkbox" :checked="isSelected(option.id)" @change="toggle(option.id)"
                                            class="h-3.5 w-3.5 rounded border-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                        <span class="min-w-0 text-zinc-700 dark:text-zinc-200" x-text="option.name"></span>
                                        <span x-show="sub(option)" class="ml-auto text-[11px] text-zinc-500 dark:text-zinc-400 truncate" x-text="sub(option)"></span>
                                    </label>
                                </template>
                                <p x-show="filtered.length === 0" class="text-center text-xs text-zinc-500 dark:text-zinc-400 py-4">Sin resultados</p>
                            </div>
                            <div x-show="selected.length > 0" style="display: none;" class="p-1.5 border-t border-zinc-200 dark:border-zinc-800">
                                <button @click="clear()" type="button" x-text="clearText"
                                    class="w-full px-3 py-1 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"></button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Limpiar filtro: borra también la selección guardada en este navegador --}}
                <button type="button" x-cloak
                    x-show="($wire.selectedLines || []).length || ($wire.selectedWorkCenters || []).length"
                    @click="try { localStorage.removeItem('lineProgress.lines'); localStorage.removeItem('lineProgress.wcs'); } catch (e) {} $wire.clearFilters()"
                    class="h-8 inline-flex items-center gap-1 rounded-lg px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Limpiar filtros
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs xl:justify-end text-zinc-600 dark:text-zinc-300">
            @if($level === 3)
                <a href="{{ route('guest.work-center-progress', $wc['name']) }}" class="mr-1 font-medium text-zinc-500 dark:text-zinc-400 hover:text-[#2563eb] dark:hover:text-[#60a5fa] hover:underline">Ver por orden →</a>
            @endif
            <span wire:loading.delay wire:target="{{ $loadingTargets }}" class="text-zinc-500 dark:text-zinc-400">Cargando…</span>

            {{-- Semana: ‹ 19 – 25 Oct › --}}
            @unless($isCurrentWeek)
                <button wire:click="currentWeek" type="button" class="{{ $control }} px-2.5 font-medium text-zinc-600 dark:text-zinc-300">Semana actual</button>
            @endunless
            <button wire:click="prevWeek" type="button" title="Semana anterior" class="{{ $control }} w-8 flex items-center justify-center text-zinc-500 dark:text-zinc-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>
            <span class="min-w-[7.5rem] text-center text-sm font-semibold text-zinc-800 dark:text-zinc-100 whitespace-nowrap">{{ $weekLabel }}</span>
            <button wire:click="nextWeek" type="button" title="Semana siguiente" class="{{ $control }} w-8 flex items-center justify-center text-zinc-500 dark:text-zinc-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
        </div>
    </div>

    {{--
        Tabla: días divididos por turno fijos arriba; el nombre fijo a la izquierda.
        isolate: sus encabezados fijos (z-20/z-30) quedan dentro de la tabla y no tapan el menú del filtro.
        Sin flex-1: la tarjeta mide lo que sus renglones (con pocas estaciones no queda un hueco en blanco);
        con muchos, min-h-0 la deja encogerse y la tabla hace scroll por dentro.
    --}}
    <div class="isolate min-h-0 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col">
        <div class="min-h-0 overflow-auto rounded-t-xl transition-opacity" wire:loading.delay.class="opacity-50" wire:target="{{ $loadingTargets }}">
            <table class="w-full min-w-[1600px] table-fixed border-collapse text-sm">
                <colgroup>
                    <col class="w-56">
                    @foreach($columns as $col)
                        <col>
                    @endforeach
                    <col class="w-24">
                </colgroup>
                <thead class="sticky top-0 z-20">
                    <tr>
                        <th rowspan="2" class="sticky left-0 z-30 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-r border-zinc-200 dark:border-zinc-700/70">
                            {{ $nameHeader }}
                        </th>
                        @foreach($days as $day)
                            <th colspan="{{ count($shifts) }}" class="px-2 pt-1.5 pb-1 text-center border-l border-zinc-200 dark:border-zinc-700/70 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-[#1c2331]' : 'bg-zinc-50 dark:bg-zinc-800' }}">
                                <span class="text-xs font-bold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-200' }}">{{ $day['name'] }}</span>
                                <span class="text-[11px] font-normal {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $day['label'] }}</span>
                            </th>
                        @endforeach
                        <th rowspan="2" class="bg-zinc-50 dark:bg-zinc-800 px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-l border-zinc-200 dark:border-zinc-700/70">
                            Sin orden
                        </th>
                    </tr>
                    <tr>
                        @foreach($columns as $col)
                            {{-- Turno: ámbar = diurno, índigo = nocturno; el turno en curso lleva una línea azul abajo --}}
                            <th class="pp-shift-th {{ $col['abbr'] === 'D' ? 'pp-shift-D' : 'pp-shift-N' }}{{ $col['state'] === 'live' ? ' lp-th-live' : '' }}"
                                title="{{ $col['title'] }}{{ $col['state'] === 'live' ? ' · En curso' : '' }}">{{ $col['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>

                @forelse($groups as $group)
                    <tbody wire:key="group-{{ $group['name'] ?? 'all' }}">
                        {{-- Líneas: separador con su área --}}
                        @if($group['name'])
                            <tr>
                                <td colspan="{{ $colCount }}" class="bg-zinc-100 dark:bg-zinc-950 px-4 py-1.5 border-b border-zinc-200 dark:border-zinc-800">
                                    <span class="sticky left-4 text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">{{ $group['name'] }}</span>
                                </td>
                            </tr>
                        @endif

                        @foreach($group['rows'] as $row)
                            <tr wire:key="row-{{ $row['key'] }}" class="border-b border-zinc-200 dark:border-zinc-800">
                                {{-- Nombre y, abajo, lo que se produce ahora (o lo último) --}}
                                <td class="lp-name">
                                    @if($row['href'])
                                        <a href="{{ $row['href'] }}" class="lp-wc-link flex items-center gap-2" title="Ver {{ $level === 1 ? 'estaciones' : 'números de parte' }} de {{ $row['name'] }}">@if($row['color'])<span class="w-1 h-4 rounded-full flex-shrink-0" style="background-color: {{ $row['color'] }}"></span>@endif<span class="truncate">{{ $row['name'] }}</span></a>
                                    @else
                                        <span class="block truncate text-sm font-semibold tabular-nums text-zinc-800 dark:text-zinc-100" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                    @endif

                                    {{-- Abajo, solo un texto: qué se produce ahora o cuándo fue la última producción; clic = números de parte --}}
                                    @if($a = $row['activity'])
                                        @php
                                            $actText = $a['state'] === 'live' ? 'Produciendo ahora' : 'Última producción · ' . $a['items'][0]['time'];
                                            if ($level === 3 && $a['state'] === 'live') $actText .= ' · ' . $a['items'][0]['time'];
                                        @endphp
                                        @if($level === 3)
                                            <div class="lp-act lp-act-link {{ $a['state'] === 'live' ? 'lp-act-live' : 'lp-act-last' }}">@if($a['state'] === 'live')<span class="lp-dot"></span>@endif{{ $actText }}</div>
                                        @else
                                            <button type="button" wire:click="showActivity('{{ $row['key'] }}')" title="Ver números de parte"
                                                class="lp-act lp-act-link lp-act-btn {{ $a['state'] === 'live' ? 'lp-act-live' : 'lp-act-last' }}">@if($a['state'] === 'live')<span class="lp-dot"></span>@endif{{ $actText }}</button>
                                        @endif
                                    @endif
                                </td>

                                {{-- Turnos: HTML armado en el componente (barra real / plan, % a la derecha) --}}
                                {!! LineProgress::cellsHtml($row['cells'], $columns) !!}

                                {{-- Sin orden: total de la semana; clic = detalle por número de parte --}}
                                <td class="lp-td-day px-1 text-center{{ $row['noOrder'] > 0 ? '' : ' pp-x' }}">
                                    @if($row['noOrder'] > 0)
                                        <button type="button" wire:click="showNoOrder('{{ $row['key'] }}')" class="lp-noorder" title="Ver números de parte sin orden">{{ number_format($row['noOrder']) }}</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="{{ $colCount }}" class="px-4 py-16 text-center text-zinc-500 dark:text-zinc-400">
                                No hay producción ni plan en esta semana.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        {{-- Pie: leyenda --}}
        <div class="px-4 py-2 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-zinc-600 dark:text-zinc-300 flex-shrink-0">
            <span class="flex items-center gap-1.5"><span class="w-4 h-2 rounded-sm border border-zinc-400 bg-[rgba(161,161,170,0.15)] dark:border-zinc-500 dark:bg-[rgba(113,113,122,0.20)]"></span>Plan</span>
            <span class="flex items-center gap-1.5"><span class="w-4 h-2 rounded-sm border border-[#059669] bg-[rgba(16,185,129,0.15)] dark:bg-[rgba(16,185,129,0.22)]"></span>Real</span>
            <span class="flex items-center gap-1.5"><span class="font-bold text-[#c2410c] dark:text-orange-400">123</span>Sin orden</span>
            <span class="ml-auto flex items-center gap-1.5 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-[#059669] animate-pulse"></span>
                Última actualización <span class="font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">{{ $lastUpdated }}</span>
            </span>
        </div>
    </div>

    {{-- Modal: números de parte sin orden del renglón en la semana --}}
    @if($noOrderDetail)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="lp-modal-title">
            <div class="absolute inset-0 bg-zinc-900/50 dark:bg-black/60" wire:click="closeModals"></div>
            <div class="relative w-full max-w-2xl max-h-[80vh] flex flex-col bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="lp-modal-title" class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Sin orden · {{ $noOrderDetail['title'] }}</h2>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $weekLabel }}</p>
                    </div>
                    <button type="button" wire:click="closeModals" title="Cerrar" class="p-1 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="min-h-0 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">
                                @if($noOrderDetail['withStation'])<th class="px-5 py-2 text-left">Estación</th>@endif
                                <th class="px-5 py-2 text-left">Número de parte</th>
                                @foreach($shifts as $shift)<th class="px-3 py-2 text-right">{{ $shift['name'] }}</th>@endforeach
                                <th class="px-5 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($noOrderDetail['parts'] as $part)
                                <tr>
                                    @if($noOrderDetail['withStation'])<td class="px-5 py-2 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">{{ $part['station'] }}</td>@endif
                                    <td class="px-5 py-2">
                                        <div class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $part['number'] }}</div>
                                        @if($part['name'])<div class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-[16rem]">{{ $part['name'] }}</div>@endif
                                    </td>
                                    @foreach($shifts as $shift)
                                        <td class="px-3 py-2 text-right tabular-nums text-zinc-600 dark:text-zinc-300">{{ isset($part['shifts'][$shift['abbr']]) ? number_format($part['shifts'][$shift['abbr']]) : '–' }}</td>
                                    @endforeach
                                    <td class="px-5 py-2 text-right font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">{{ number_format($part['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($shifts) + 3 }}" class="px-5 py-8 text-center text-zinc-500 dark:text-zinc-400">Ya no hay piezas sin orden en este renglón.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-sm">
                    <span class="font-medium text-zinc-600 dark:text-zinc-300">Total sin orden</span>
                    <span class="font-bold tabular-nums text-[#c2410c] dark:text-orange-400">{{ number_format($noOrderDetail['total']) }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal "Ver más": todos los números de parte produciendo (o los últimos) del renglón --}}
    @if($activityDetail)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="lp-activity-title">
            <div class="absolute inset-0 bg-zinc-900/50 dark:bg-black/60" wire:click="closeModals"></div>
            <div class="relative w-full max-w-xl max-h-[80vh] flex flex-col bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="lp-activity-title" class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $activityDetail['state'] === 'live' ? 'Produciendo ahora' : 'Lo último que se produjo' }} · {{ $activityDetail['title'] }}
                        </h2>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ count($activityDetail['items']) }} números de parte</p>
                    </div>
                    <button type="button" wire:click="closeModals" title="Cerrar" class="p-1 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="min-h-0 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">
                                <th class="px-5 py-2 text-left">Estación</th>
                                <th class="px-5 py-2 text-left">Número de parte</th>
                                <th class="px-5 py-2 text-right">{{ $activityDetail['state'] === 'live' ? 'Inicio' : 'Última pieza' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($activityDetail['items'] as $item)
                                <tr>
                                    <td class="px-5 py-2 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">{{ $item['station'] }}</td>
                                    <td class="px-5 py-2">
                                        <div class="flex items-center gap-2 font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">
                                            @if($item['live'])<span class="lp-dot !relative !top-0 !right-0 flex-shrink-0" title="Produciendo"></span>@endif{{ $item['number'] }}
                                        </div>
                                        @if($item['name'])<div class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-[18rem] {{ $item['live'] ? 'pl-4' : '' }}">{{ $item['name'] }}</div>@endif
                                    </td>
                                    <td class="px-5 py-2 text-right tabular-nums text-zinc-600 dark:text-zinc-300 whitespace-nowrap">{{ $item['time'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        const catalog = window.lineProgressCatalog || { lines: {}, workCenters: [] };
        // ↑ Debe ser la primera línea: Alpine solo ejecuta este script como bloque si empieza con const/let
        //   (con un comentario al inicio lo evalúa como expresión y falla). Los catálogos los entrega
        //   la página una sola vez, para que no viajen en cada refresco.

        const lineOptions = Object.values(catalog.lines).map(l => {
            const stations = catalog.workCenters.filter(w => w.line_id === l.id).map(w => w.name);
            return { id: l.id, name: l.name, sub: l.area_name, stations, search: [l.name, l.area_name, ...stations].join(' ').toLowerCase() };
        });

        // ¿La URL ya trae el filtro? Livewire manda los arreglos como ?lines[0]=1
        const inUrl = (param) => [...new URLSearchParams(window.location.search).keys()]
            .some(k => k === param || k.startsWith(param + '['));

        // Select múltiple persistente (URL + navegador)
        const multiSelect = (cfg) => ({
            open: false,
            search: '',
            selected: $wire.entangle(cfg.model).live,
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
                        const valid = stored.filter(id => cfg.options().some(o => o.id === id));
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
                return q ? options.filter(o => o.search.includes(q)) : options;
            },
            // Debajo del nombre: las estaciones que coinciden con lo escrito; si no, el dato secundario (área o línea)
            sub(o) {
                const q = this.search.trim().toLowerCase();
                const hits = q && o.stations ? o.stations.filter(st => st.toLowerCase().includes(q)) : [];
                return hits.length ? hits.join(', ') : o.sub;
            },
            get label() {
                const picked = cfg.options().filter(o => this.selected.includes(o.id));
                if (!picked.length) return cfg.allText;
                return picked.length === 1 ? picked[0].name : `${picked.length} ${cfg.units}`;
            },
            isSelected(id) { return this.selected.includes(id); },
            toggle(id) {
                this.selected = this.isSelected(id)
                    ? this.selected.filter(x => x !== id)
                    : [...this.selected, id];
            },
            clear() { this.selected = []; },
        });

        // Inicio: las líneas (con su área como dato secundario)
        Alpine.data('lpLinesFilter', () => multiSelect({
            model: 'selectedLines', param: 'lines', storageKey: 'lineProgress.lines',
            allText: 'Todas las líneas', units: 'líneas', clearText: 'Mostrar todas las líneas',
            // Se puede buscar por el nombre de una de sus estaciones
            options: () => lineOptions,
        }));

        // Dentro de una línea: solo sus estaciones
        Alpine.data('lpWcFilter', () => multiSelect({
            model: 'selectedWorkCenters', param: 'wcs', storageKey: 'lineProgress.wcs',
            allText: 'Todas las estaciones', units: 'estaciones', clearText: 'Mostrar todas las estaciones',
            options: () => catalog.workCenters.filter(w => w.line_id === $wire.lineId)
                .map(w => ({ id: w.id, name: w.name, sub: '', search: w.name.toLowerCase() })),
        }));
    </script>
    @endscript
</div>
