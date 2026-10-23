@php
    use App\Livewire\Guest\LineProgress;

    // Controles compactos, iguales a los de /guest/press-efficiency
    $control = 'h-8 border border-zinc-300 dark:border-zinc-700/70 rounded-lg bg-white dark:bg-zinc-900 text-xs text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors';
    $panel = 'absolute left-0 mt-1 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-800 z-50 overflow-hidden';

        $nameHeader = [1 => 'Línea', 2 => 'Estación', 3 => 'Número de parte'][$level];
    $filter = [1 => ['lpLinesFilter', 'Buscar área, línea o estación...'], 2 => ['lpWcFilter', 'Buscar estación...'], 3 => null][$level];
    $colCount = count($columns) + 2;
    $loadingTargets = 'prevWeek,nextWeek,currentWeek,goToDate,selectedLines,selectedWorkCenters,clearFilters';
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
                                {{-- En el inicio, agrupadas por área: la casilla del área elige o quita todas sus líneas --}}
                                <template x-for="group in groups" :key="group.name || 'all'">
                                    <div>
                                        <label x-show="group.name" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                            <input type="checkbox" :checked="groupState(group) === 'all'" x-effect="$el.indeterminate = groupState(group) === 'some'" @change="toggleGroup(group)"
                                                class="h-3.5 w-3.5 rounded border-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                            <span class="min-w-0 uppercase tracking-wide text-zinc-800 dark:text-zinc-100" x-text="group.name"></span>
                                            <span class="ml-auto text-[11px] font-normal text-zinc-500 dark:text-zinc-400" x-text="group.options.length"></span>
                                        </label>
                                        <template x-for="option in group.options" :key="option.id">
                                            <label class="flex items-center gap-2 py-1.5 pr-3 text-xs cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors" :class="group.name ? 'pl-8' : 'pl-3'">
                                                <input type="checkbox" :checked="isSelected(option.id)" @change="toggle(option.id)"
                                                    class="h-3.5 w-3.5 rounded border-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                                <span class="min-w-0 text-zinc-700 dark:text-zinc-200" x-text="option.name"></span>
                                                <span x-show="sub(option)" class="ml-auto text-[11px] text-zinc-500 dark:text-zinc-400 truncate" x-text="sub(option)"></span>
                                            </label>
                                        </template>
                                    </div>
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

                {{--
                    Limpiar filtro: borra también la selección guardada en este navegador. Va como método:
                    Alpine evalúa @click como expresión ("resultado = ...") y un try/catch suelto ahí no es válido.
                --}}
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
                    class="h-8 inline-flex items-center gap-1 rounded-lg px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Limpiar filtros
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs xl:justify-end text-zinc-600 dark:text-zinc-300">
            {{-- Ayuda visual y gráficas de prensa: solo estaciones de estampado (igual que /guest/wc-progress) --}}
            @if($isStamping)
                <a href="{{ route('visual-aids.current-part', $wc['name']) }}" title="Ayuda visual" aria-label="Ayuda visual" class="p-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-[#2563eb] dark:hover:text-[#60a5fa] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </a>
                <a href="{{ route('press-production', $wc['name']) }}" title="Gráfica de producción" aria-label="Gráfica de producción" class="p-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-[#2563eb] dark:hover:text-[#60a5fa] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 15l4-4 3 3 5-6" />
                    </svg>
                </a>
                <a href="{{ route('press-production-timeline', $wc['name']) }}" title="Tiempo de producción" aria-label="Tiempo de producción" class="mr-1 p-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-[#2563eb] dark:hover:text-[#60a5fa] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </a>
            @endif
            <span wire:loading.delay wire:target="{{ $loadingTargets }}" class="text-zinc-500 dark:text-zinc-400">Cargando…</span>

            {{-- Semana: ‹ 19 – 25 Oct ›; al dar clic en las fechas se abre el calendario --}}
            @unless($isCurrentWeek)
                <button wire:click="currentWeek" type="button" class="{{ $control }} px-2.5 font-medium text-zinc-600 dark:text-zinc-300">Semana actual</button>
            @endunless
            <button wire:click="prevWeek" type="button" title="Semana anterior" class="{{ $control }} w-8 flex items-center justify-center text-zinc-500 dark:text-zinc-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>
            {{--
                Calendario propio (Alpine + Tailwind): el nativo escondido con showPicker() no abría igual en
                todos los navegadores. La semana mostrada va sombreada y el día elegido (o hoy) en azul.
                El panel lleva wire:ignore para que el refresco automático no lo cierre mientras está abierto.
            --}}
            <div x-data="lpDatePicker"
                @click.outside="open = false" @keydown.escape.stop="open = false" class="relative">
                <button type="button" @click="toggle()" x-ref="trigger" :aria-expanded="open" title="Elegir fecha"
                    class="{{ $control }} min-w-[8.5rem] inline-flex items-center justify-center gap-1.5 px-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 whitespace-nowrap">{{ $weekLabel }}</span>
                </button>

                <div wire:ignore x-show="open" x-transition.opacity.duration.100ms style="display: none;"
                    class="absolute right-0 mt-1 w-72 p-3 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-800 z-50" role="dialog" aria-label="Calendario">
                    {{-- Mes y flechas --}}
                    <div class="flex items-center justify-between mb-2">
                        <button type="button" @click="move(-1)" title="Mes anterior" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100" x-text="title"></span>
                        <button type="button" @click="move(1)" title="Mes siguiente" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </div>

                    {{-- Días de la semana (lunes a domingo) --}}
                    <div class="grid grid-cols-7 mb-1">
                        <template x-for="d in ['L', 'M', 'M', 'J', 'V', 'S', 'D']">
                            <span class="h-7 flex items-center justify-center text-[11px] font-semibold text-zinc-500 dark:text-zinc-400" x-text="d"></span>
                        </template>
                    </div>

                    {{-- Semanas: la semana mostrada en la tabla va sombreada --}}
                    <template x-for="week in weeks" :key="week[0].iso">
                        <div class="grid grid-cols-7 rounded-lg" :class="week[0].iso === weekStart ? 'bg-blue-50 dark:bg-blue-500/10' : ''">
                            <template x-for="day in week" :key="day.iso">
                                <button type="button" @click="pick(day.iso)"
                                    class="h-8 m-0.5 rounded-lg text-xs tabular-nums transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                    :class="day.iso === picked
                                        ? 'bg-[#2563eb] text-white font-bold hover:bg-blue-700'
                                        : (day.iso === today
                                            ? 'font-bold text-[#2563eb] dark:text-[#60a5fa] ring-1 ring-inset ring-[#2563eb] dark:ring-[#60a5fa] hover:bg-blue-100 dark:hover:bg-blue-500/20'
                                            : (day.inMonth ? 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' : 'text-zinc-400 dark:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800'))"
                                    x-text="day.day"></button>
                            </template>
                        </div>
                    </template>

                    <div class="mt-2 pt-2 border-t border-zinc-200 dark:border-zinc-800 flex justify-between">
                        <button type="button" @click="pick(today)" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-[#2563eb] dark:text-[#60a5fa] hover:bg-blue-50 dark:hover:bg-blue-500/10">Hoy</button>
                        <button type="button" @click="open = false" class="px-2.5 py-1 rounded-lg text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">Cerrar</button>
                    </div>
                </div>
            </div>
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
                            <th colspan="{{ count($shifts) }}" class="px-2 pt-1.5 pb-1 text-center border-l border-zinc-200 dark:border-zinc-700/70 {{ $day['highlight'] ? 'lp-hl-day' : 'bg-zinc-50 dark:bg-zinc-800' }}">
                                <span class="text-xs font-bold {{ $day['highlight'] ? 'text-zinc-900 dark:text-zinc-50' : 'text-zinc-700 dark:text-zinc-200' }}">{{ $day['name'] }}</span>
                                <span class="text-[11px] {{ $day['highlight'] ? 'font-semibold text-zinc-700 dark:text-zinc-200' : 'font-normal text-zinc-500 dark:text-zinc-400' }}">{{ $day['label'] }}</span>
                            </th>
                        @endforeach
                        <th rowspan="2" class="bg-zinc-50 dark:bg-zinc-800 px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-l border-zinc-200 dark:border-zinc-700/70">
                            Sin N° de orden
                        </th>
                    </tr>
                    <tr>
                        @foreach($columns as $col)
                            {{-- Turno: ámbar = diurno, índigo = nocturno (también en el día resaltado) --}}
                            <th class="pp-shift-th {{ $col['abbr'] === 'D' ? 'pp-shift-D' : 'pp-shift-N' }}" title="{{ $col['title'] }}">{{ $col['name'] }}</th>
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

                                    {{--
                                        Abajo, el estado: EN PROCESO (verde) si hay piezas en la última hora; si no, DETENIDO (rojo)
                                        con la hora de la última producción. Clic = números de parte (en la vista de números de parte no hace falta).
                                    --}}
                                    @php
                                        $a = $row['activity'];
                                        $isLive = ($a['state'] ?? null) === 'live';
                                        // EN PROCESO: "Inicio" (solo en la vista de números de parte; en las demás va en el modal).
                                        // DETENIDO: "Fin" = última pieza producida.
                                        $actTime = $a && !($isLive && $level !== 3) ? $a['time'] : '';
                                    @endphp
                                    <div class="lp-act">
                                        @if($a && $level !== 3)
                                            <button type="button" wire:click="showActivity('{{ $row['key'] }}')" title="Ver números de parte"
                                                class="lp-state {{ $isLive ? 'lp-state-live' : 'lp-state-stop' }}">@if($isLive)<span class="lp-dot lp-dot-green"></span>@else<span class="lp-dot-stop"></span>@endif{{ $isLive ? 'EN PROCESO' : 'DETENIDO' }}</button>
                                        @else
                                            <span class="lp-state {{ $isLive ? 'lp-state-live' : 'lp-state-stop' }}">@if($isLive)<span class="lp-dot lp-dot-green"></span>@else<span class="lp-dot-stop"></span>@endif{{ $isLive ? 'EN PROCESO' : 'DETENIDO' }}</span>
                                        @endif
                                        @if($actTime)
                                            <span class="lp-state-time" title="{{ $isLive ? 'Inicio de la producción' : 'Última pieza producida' }}"><span class="lp-state-label">{{ $isLive ? 'Ini' : 'Fin' }}</span> {{ $actTime }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Turnos: HTML armado en el componente (barra real / plan, % a la derecha) --}}
                                {!! LineProgress::cellsHtml($row['cells'], $columns, $row['orders'] ?? []) !!}

                                {{-- Sin número de orden: total de la semana; clic = detalle por número de parte --}}
                                <td class="lp-td-day px-1 text-center{{ $row['noOrder'] > 0 ? '' : ' pp-x' }}">
                                    @if($row['noOrder'] > 0)
                                        <button type="button" wire:click="showNoOrder('{{ $row['key'] }}')" class="lp-noorder" title="Ver números de parte sin número de orden">{{ number_format($row['noOrder']) }}</button>
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
            <span class="flex items-center gap-1.5"><span class="font-bold text-[#c2410c] dark:text-orange-400">123</span>Sin número de orden</span>
            {{-- Color del % de cada turno (mismos rangos que LineProgress::tone) --}}
            <span class="text-zinc-300 dark:text-zinc-700" aria-hidden="true">|</span>
            <span class="flex items-center gap-3">
                <span class="font-semibold lp-t-bad">≤ {{ LineProgress::WARN }}%</span>
                <span class="font-semibold lp-t-warn">{{ LineProgress::WARN + 1 }}–{{ LineProgress::GOOD - 1 }}%</span>
                <span class="font-semibold lp-t-good">≥ {{ LineProgress::GOOD }}%</span>
            </span>
            <span class="ml-auto flex items-center gap-1.5 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-[#059669] animate-pulse"></span>
                Última actualización <span class="font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">{{ $lastUpdated }}</span>
            </span>
        </div>
    </div>

    {{-- Modal: números de parte sin número de orden del renglón, total de la semana --}}
    @if($noOrderDetail)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="lp-modal-title">
            <div class="absolute inset-0 bg-zinc-900/50 dark:bg-black/60" wire:click="closeModals"></div>
            <div class="relative w-full max-w-2xl max-h-[80vh] flex flex-col bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="lp-modal-title" class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Sin número de orden · {{ $noOrderDetail['title'] }}</h2>
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
                                <th class="px-5 py-2 text-right">Total semana</th>
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
                                    <td class="px-5 py-2 text-right font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">{{ number_format($part['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-8 text-center text-zinc-500 dark:text-zinc-400">Ya no hay piezas sin número de orden en este renglón.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-sm">
                    <span class="font-medium text-zinc-600 dark:text-zinc-300">Total de la semana</span>
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
                            {{ $activityDetail['state'] === 'live' ? 'En proceso' : 'Detenido · última producción' }} · {{ $activityDetail['title'] }}
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
                                <th class="px-5 py-2 text-right">Inicio</th>
                                <th class="px-5 py-2 text-right">Última pieza</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($activityDetail['items'] as $item)
                                <tr>
                                    <td class="px-5 py-2 text-zinc-600 dark:text-zinc-300 whitespace-nowrap">{{ $item['station'] }}</td>
                                    <td class="px-5 py-2">
                                        <div class="flex items-center gap-2 font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">
                                            @if($item['live'])<span class="lp-dot lp-dot-green !relative !top-0 !right-0 flex-shrink-0" title="En proceso"></span>@endif{{ $item['number'] }}
                                        </div>
                                        @if($item['name'])<div class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-[18rem] {{ $item['live'] ? 'pl-4' : '' }}">{{ $item['name'] }}</div>@endif
                                    </td>
                                    <td class="px-5 py-2 text-right tabular-nums text-zinc-600 dark:text-zinc-300 whitespace-nowrap">{{ $item['startLabel'] }}</td>
                                    <td class="px-5 py-2 text-right tabular-nums text-zinc-600 dark:text-zinc-300 whitespace-nowrap">{{ $item['endLabel'] }}</td>
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
            return { id: l.id, name: l.name, group: l.area_name, sub: '', stations, search: [l.name, l.area_name, ...stations].join(' ').toLowerCase() };
        }).sort((a, b) => (a.group + ' ' + a.name).localeCompare(b.group + ' ' + b.name, 'es', { numeric: true, sensitivity: 'base' }));
        // ↑ catalog.lines llega como objeto con llaves numéricas (id) y JS lo recorre por id: se ordena aquí por área y línea

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
            // Opciones agrupadas (las líneas por área; las estaciones van en un solo grupo sin nombre)
            get groups() {
                const groups = [];
                for (const o of this.filtered) {
                    const name = o.group || null;
                    let g = groups.find(x => x.name === name);
                    if (!g) groups.push(g = { name, options: [] });
                    g.options.push(o);
                }
                return groups;
            },
            // 'all' | 'some' | 'none': cuántas de las líneas visibles del área están elegidas
            groupState(group) {
                const picked = group.options.filter(o => this.isSelected(o.id)).length;
                return picked === 0 ? 'none' : (picked === group.options.length ? 'all' : 'some');
            },
            toggleGroup(group) {
                const ids = group.options.map(o => o.id);
                this.selected = this.groupState(group) === 'all'
                    ? this.selected.filter(id => !ids.includes(id))
                    : [...new Set([...this.selected, ...ids])];
            },
            // A la derecha del nombre: las estaciones que coinciden con lo escrito; si no, el dato secundario
            sub(o) {
                const q = this.search.trim().toLowerCase();
                const hits = q && o.stations ? o.stations.filter(st => st.toLowerCase().includes(q)) : [];
                return hits.length ? hits.join(', ') : o.sub;
            },
            get label() {
                const picked = cfg.options().filter(o => this.selected.includes(o.id));
                if (!picked.length) return cfg.allText;
                if (picked.length === 1) return picked[0].name;
                const area = picked[0].group;
                if (area && picked.every(o => o.group === area) && cfg.options().filter(o => o.group === area).length === picked.length) return area;
                return `${picked.length} ${cfg.units}`;
            },
            isSelected(id) { return this.selected.includes(id); },
            toggle(id) {
                this.selected = this.isSelected(id)
                    ? this.selected.filter(x => x !== id)
                    : [...this.selected, id];
            },
            clear() { this.selected = []; },
        });

        // Inicio: las líneas agrupadas por área
        Alpine.data('lpLinesFilter', () => multiSelect({
            model: 'selectedLines', param: 'lines', storageKey: 'lineProgress.lines',
            allText: 'Todas las líneas', units: 'líneas', clearText: 'Mostrar todas las líneas',
            // Se puede buscar por el nombre de una de sus estaciones
            options: () => lineOptions,
        }));

        // Calendario: elegir un día lleva a su semana y lo resalta en la tabla
        const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const parseIso = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
        const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        Alpine.data('lpDatePicker', () => ({
            open: false,
            year: 0,
            month: 0,
            picked: '',
            weekStart: '',
            today: iso(new Date()),

            // Al abrir: muestra el mes del día elegido. $wire.date / $wire.week vacíos = hoy / semana actual
            toggle() {
                this.open = !this.open;
                if (!this.open) return;
                const now = new Date();
                this.today = iso(now);
                this.picked = $wire.date || this.today;
                this.weekStart = $wire.week || iso(new Date(now.getFullYear(), now.getMonth(), now.getDate() - ((now.getDay() + 6) % 7)));
                const d = parseIso(this.picked);
                this.year = d.getFullYear();
                this.month = d.getMonth();
            },
            move(step) {
                const d = new Date(this.year, this.month + step, 1);
                this.year = d.getFullYear();
                this.month = d.getMonth();
            },
            pick(date) {
                this.open = false;
                $wire.goToDate(date);
            },
            get title() { return `${months[this.month]} ${this.year}`; },

            // 6 semanas de lunes a domingo, empezando en el lunes de la semana del día 1
            get weeks() {
                const first = new Date(this.year, this.month, 1);
                const start = new Date(this.year, this.month, 1 - ((first.getDay() + 6) % 7));
                return Array.from({ length: 6 }, (_, w) => Array.from({ length: 7 }, (_, i) => {
                    const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + w * 7 + i);
                    return { iso: iso(d), day: d.getDate(), inMonth: d.getMonth() === this.month };
                }));
            },
        }));

        // Dentro de una línea: solo sus estaciones
        Alpine.data('lpWcFilter', () => multiSelect({
            model: 'selectedWorkCenters', param: 'wcs', storageKey: 'lineProgress.wcs',
            allText: 'Todas las estaciones', units: 'estaciones', clearText: 'Mostrar todas las estaciones',
            options: () => catalog.workCenters.filter(w => w.line_id === $wire.lineId)
                .map(w => ({ id: w.id, name: w.name, group: null, sub: '', search: w.name.toLowerCase() })),
        }));
    </script>
    @endscript
</div>
