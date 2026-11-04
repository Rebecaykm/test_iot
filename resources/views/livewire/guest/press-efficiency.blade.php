@php
    use App\Livewire\Guest\PressEfficiency;

    // Estilo TailAdmin: texto y badges redondos con fondo suave
    $toneText = [
        'good' => 'text-success-600 dark:text-success-400',
        'warn' => 'text-warning-600 dark:text-warning-400',
        'bad' => 'text-danger-600 dark:text-danger-400',
        'none' => 'text-gray-400 dark:text-ta-gray-500',
    ];
    $toneBadge = [
        'good' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
        'warn' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
        'bad' => 'bg-danger-50 text-danger-600 dark:bg-danger-500/15 dark:text-danger-400',
        'none' => 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-ta-gray-400',
    ];

    // Duración exacta (h, min, s): con los segundos, SPM × minutos × piezas por golpe cuadra con las esperadas
    $duration = function (int $seconds): string {
        $hours = intdiv($seconds, 3600);
        $mins = intdiv($seconds % 3600, 60);
        $secs = str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);
        return $hours > 0
            ? "{$hours} h " . str_pad($mins, 2, '0', STR_PAD_LEFT) . " min {$secs} s"
            : "{$mins} min {$secs} s";
    };

    // Piezas de TailAdmin: tarjeta, control (botón/selector) y panel desplegable
    $card = 'rounded-2xl border border-gray-200 bg-white dark:border-ta-gray-800 dark:bg-white/[0.03]';
    $control = 'h-9 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 shadow-theme-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-600/20 focus:border-brand-600 dark:border-ta-gray-700 dark:bg-ta-gray-900 dark:text-ta-gray-300 dark:hover:bg-white/[0.03] transition-colors';
    $panel = 'absolute left-0 mt-2 rounded-xl border border-gray-200 bg-white shadow-lg dark:border-ta-gray-800 dark:bg-ta-gray-900 z-50 overflow-hidden';
    $chevron = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 transition-transform" :class="{ \'rotate-180\': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>';

    $selectedStations = $stations->whereIn('id', $selectedWorkCenters);
    $stationsLabel = match (true) {
        $selectedStations->isEmpty() => 'Todas las estaciones',
        $selectedStations->count() === 1 => $selectedStations->first()->name,
        default => $selectedStations->count() . ' estaciones',
    };
    $periodNoun = ['week' => 'semana', 'month' => 'mes', 'year' => 'año'][$period] ?? 'semana';
    // Etiquetas de navegación por periodo (flechas y botón "actual")
    $periodNav = [
        'week' => ['prev' => 'Semana anterior', 'next' => 'Semana siguiente', 'current' => 'Ir a la semana actual'],
        'month' => ['prev' => 'Mes anterior', 'next' => 'Mes siguiente', 'current' => 'Ir al mes actual'],
        'year' => ['prev' => 'Año anterior', 'next' => 'Año siguiente', 'current' => 'Ir al año actual'],
    ][$period] ?? [];
    $loadingTargets = 'goToPage,selectedWorkCenters,clearWorkCenters,clearFilters,setPeriod,currentPeriod,prevPeriod,nextPeriod,setWeek,setMonth,setYear';
    // Barra de esqueleto (respeta "reducir movimiento" del sistema)
    $bone = 'rounded bg-gray-200 dark:bg-ta-gray-800 motion-safe:animate-pulse';
@endphp

<div class="flex flex-col gap-4 md:gap-6">

    {{-- Filtros: estaciones a la izquierda; Excel y periodo (semana o mes con su calendario) a la derecha --}}
    <div class="relative z-20 {{ $card }} px-4 py-3 sm:px-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Multi select de estaciones del área de Estampado (vacío = todas) --}}
            <div x-data="{ open: false, search: '' }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative w-full sm:w-56">
                <button @click="open = !open" type="button" class="{{ $control }} w-full flex items-center justify-between gap-2 px-3"
                    :aria-expanded="open" aria-haspopup="true" aria-controls="pe-stations-panel" aria-label="Estaciones: {{ $stationsLabel }}">
                    <span class="flex items-center gap-2 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                        </svg>
                        <span class="truncate font-medium">{{ $stationsLabel }}</span>
                    </span>
                    <span class="flex items-center gap-1.5 flex-shrink-0">
                        @if($selectedStations->isNotEmpty())
                            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[11px] font-semibold bg-brand-600 text-white rounded-full">{{ $selectedStations->count() }}</span>
                        @endif
                        {!! $chevron !!}
                    </span>
                </button>

                <div id="pe-stations-panel" x-show="open" x-transition.opacity.duration.100ms style="display: none;" class="{{ $panel }} w-full sm:w-72">
                    <div class="p-2 border-b border-gray-200 dark:border-ta-gray-800">
                        <input x-model="search" type="search" placeholder="Buscar estación..." aria-label="Buscar estación"
                            class="w-full h-9 px-3 text-sm rounded-lg border border-gray-300 bg-transparent text-gray-800 placeholder:text-gray-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20 dark:border-ta-gray-700 dark:text-white/90 dark:placeholder:text-white/30">
                    </div>
                    <div class="max-h-72 overflow-y-auto py-1">
                        @foreach($stations->groupBy('line') as $line => $lineStations)
                            <div x-show="!search || @js(\Illuminate\Support\Str::lower($line . ' ' . $lineStations->pluck('name')->implode(' ') . ' ' . $lineStations->pluck('number')->implode(' '))).includes(search.toLowerCase())"
                                class="px-3 pt-2.5 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-ta-gray-400">
                                {{ $line }}
                            </div>
                            @foreach($lineStations as $station)
                                <label wire:key="station-{{ $station->id }}"
                                    x-show="!search || @js(\Illuminate\Support\Str::lower($line . ' ' . $station->name . ' ' . $station->number)).includes(search.toLowerCase())"
                                    class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.03] transition-colors">
                                    <input type="checkbox" wire:model.live="selectedWorkCenters" value="{{ $station->id }}"
                                        class="h-4 w-4 rounded border-gray-300 dark:border-ta-gray-700 dark:bg-ta-gray-900 text-brand-600 focus:ring-brand-600/20 focus:ring-offset-0 flex-shrink-0">
                                    <span class="text-gray-700 dark:text-ta-gray-300">{{ $station->name }}</span>
                                    <span class="ml-auto text-xs tabular-nums text-gray-500 dark:text-ta-gray-400">{{ $station->number }}</span>
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                    @if($selectedStations->isNotEmpty())
                        <div class="p-2 border-t border-gray-200 dark:border-ta-gray-800">
                            <button wire:click="clearWorkCenters" type="button"
                                class="w-full h-8 rounded-lg text-sm font-medium text-danger-600 dark:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-500/10 transition-colors">
                                Limpiar selección (ver todas)
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Limpiar filtros: todas las estaciones y la semana en curso --}}
            @if($selectedStations->isNotEmpty() || !$range?->isLive || $period !== 'week')
                <button wire:click="clearFilters" type="button"
                    class="h-9 inline-flex items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-gray-500 hover:bg-danger-50 hover:text-danger-600 dark:text-ta-gray-400 dark:hover:bg-danger-500/10 dark:hover:text-danger-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    Limpiar filtros
                </button>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            {{-- Exportar a Excel: tabla completa (todas las páginas) y gráficas Top 10 --}}
            <button wire:click="export" wire:loading.attr="disabled" wire:target="export" type="button" title="Descargar la tabla y las gráficas en Excel"
                @disabled(empty($report['rows']))
                class="{{ $control }} inline-flex items-center gap-2 px-3.5 font-medium disabled:opacity-40 disabled:pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success-600 dark:text-success-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span wire:loading.remove wire:target="export">Exportar</span>
                <span wire:loading wire:target="export">Generando…</span>
            </button>

            {{-- Anuncio para lectores de pantalla; lo visual es el esqueleto de la tabla y las gráficas --}}
            <span class="sr-only" role="status" aria-live="polite">
                <span wire:loading.delay wire:target="{{ $loadingTargets }}">Cargando datos del periodo…</span>
            </span>

            {{-- Periodo: Semana, Mes o Año (selector tipo pastilla de TailAdmin) --}}
            <div class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-ta-gray-900" role="group" aria-label="Periodo">
                @foreach(PressEfficiency::PERIODS as $value => $text)
                    <button wire:click="setPeriod('{{ $value }}')" type="button" aria-pressed="{{ $period === $value ? 'true' : 'false' }}"
                        class="h-8 px-3.5 rounded-md text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40 {{ $period === $value ? 'bg-white text-gray-900 shadow-theme-sm dark:bg-ta-gray-800 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-ta-gray-400 dark:hover:text-white' }}">
                        {{ $text }}
                    </button>
                @endforeach
            </div>

            {{-- Flechas y calendario (el regreso al periodo en curso vive dentro del calendario: una opción menos en la barra) --}}
            <div class="flex items-center gap-1.5">
                <button wire:click="prevPeriod" type="button" aria-label="{{ $periodNav['prev'] }}" title="{{ $periodNav['prev'] }}" class="{{ $control }} w-9 flex items-center justify-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>

                {{--
                    Calendario (Alpine + Tailwind): semana = clic en cualquier día; mes = cuadrícula de meses;
                    año = cuadrícula de años. El panel lleva wire:ignore para que el refresco no lo cierre;
                    lee el periodo de $wire al abrirse.
                --}}
                <div x-data="peCalendar" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="toggle()" :aria-expanded="open" aria-haspopup="dialog" aria-controls="pe-calendar"
                        aria-label="Elegir {{ $periodNoun }}. Actual: {{ $periodLabel }}"
                        class="{{ $control }} min-w-[10rem] inline-flex items-center justify-center gap-2 px-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        <span class="font-medium text-gray-800 dark:text-white/90 whitespace-nowrap">{{ $periodLabel }}</span>
                    </button>

                    <div id="pe-calendar" wire:ignore x-show="open" x-transition.opacity.duration.100ms style="display: none;"
                        class="{{ $panel }} !left-auto right-0 w-72 p-3" role="dialog" aria-label="Calendario">
                        <div class="flex items-center justify-between mb-2">
                            <button type="button" @click="move(-1)" :aria-label="navLabel(-1)"
                                class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-ta-gray-400 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="title" aria-live="polite"></span>
                            <button type="button" @click="move(1)" :aria-label="navLabel(1)"
                                class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-ta-gray-400 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        </div>

                        {{-- Año: cuadrícula de 12 años que termina en el último visible --}}
                        <div x-show="mode === 'year'" class="grid grid-cols-3 gap-1">
                            <template x-for="y in years" :key="y">
                                <button type="button" @click="pickYear(y)" :disabled="y > todayYear" :aria-pressed="yearSelected(y)"
                                    class="h-10 rounded-lg text-sm font-medium tabular-nums transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40 disabled:opacity-30 disabled:pointer-events-none"
                                    :class="yearSelected(y) ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-ta-gray-300 dark:hover:bg-white/5'"
                                    x-text="y"></button>
                            </template>
                        </div>

                        {{-- Mes: cuadrícula de meses del año --}}
                        <div x-show="mode === 'month'" class="grid grid-cols-3 gap-1">
                            <template x-for="(name, i) in monthNames" :key="i">
                                <button type="button" @click="pickMonth(i)" :disabled="monthDisabled(i)" :aria-pressed="monthSelected(i)" :aria-label="name + ' ' + year"
                                    class="h-10 rounded-lg text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40 disabled:opacity-30 disabled:pointer-events-none"
                                    :class="monthSelected(i) ? 'bg-brand-600 text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-ta-gray-300 dark:hover:bg-white/5'"
                                    x-text="name.slice(0, 3)"></button>
                            </template>
                        </div>

                        {{-- Semana: días de lunes a domingo; la semana elegida va sombreada --}}
                        <div x-show="mode === 'week'">
                            <div class="grid grid-cols-7 mb-1" aria-hidden="true">
                                <template x-for="d in ['L', 'M', 'M', 'J', 'V', 'S', 'D']">
                                    <span class="h-8 flex items-center justify-center text-xs font-medium text-gray-500 dark:text-ta-gray-400" x-text="d"></span>
                                </template>
                            </div>
                            <template x-for="week in weeks" :key="week[0].iso">
                                <div class="grid grid-cols-7 rounded-lg" :class="week[0].iso === $wire.from ? 'bg-brand-600/10 dark:bg-brand-600/20' : 'hover:bg-gray-50 dark:hover:bg-white/[0.03]'">
                                    <template x-for="day in week" :key="day.iso">
                                        <button type="button" @click="pickDay(day.iso)" :disabled="day.iso > today" :aria-label="'Semana del ' + day.day + ' de ' + monthNames[day.month]"
                                            class="h-9 text-sm tabular-nums rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40 disabled:opacity-30 disabled:pointer-events-none"
                                            :class="day.iso === today ? 'font-bold text-brand-600 dark:text-brand-400' : (day.inMonth ? 'text-gray-700 dark:text-ta-gray-300' : 'text-gray-500 dark:text-ta-gray-500')"
                                            x-text="day.day"></button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Regresar al periodo en curso --}}
                        <button type="button" @click="open = false; $wire.currentPeriod()"
                            class="mt-2 w-full h-9 rounded-lg text-sm font-medium text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40 transition-colors"
                            x-text="currentLabel"></button>
                    </div>
                </div>

                <button wire:click="nextPeriod" type="button" aria-label="{{ $periodNav['next'] }}" title="{{ $periodNav['next'] }}" @disabled(!$canGoNext)
                    class="{{ $control }} w-9 flex items-center justify-center text-gray-500 disabled:opacity-40 disabled:pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla por troquel --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="overflow-x-auto" wire:loading.delay.attr="aria-busy" wire:target="{{ $loadingTargets }}">
            <table class="w-full min-w-[1100px] text-xs">
                <caption class="sr-only">Eficiencia por troquel (MDI), {{ $periodNoun }} {{ $periodLabel }}</caption>
                <thead>
                    {{-- Renglón de títulos: mayúsculas sobre azul suave, igual que los títulos de las gráficas --}}
                    <tr class="border-b border-brand-100 bg-brand-50 text-[11px] uppercase tracking-wide text-brand-900 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-100">
                        <th scope="col" class="px-5 py-3 text-left font-bold sm:pl-6">Prensa</th>
                        <th scope="col" class="px-3 py-3 text-left font-bold">Troquel (MDI)</th>
                        <th scope="col" class="px-3 py-3 text-left font-bold">Número de parte</th>
                        <th scope="col" class="px-3 py-3 text-left font-bold">Descripción</th>
                        <th scope="col" class="px-3 py-3 text-right font-bold">SPM estándar</th>
                        <th scope="col" class="px-3 py-3 text-right font-bold">SPM real</th>
                        <th scope="col" class="px-3 py-3 text-right font-bold">Diferencia SPM</th>
                        <th scope="col" class="px-3 py-3 text-right font-bold">Tiempo trabajado</th>
                        <th scope="col" class="px-3 py-3 text-right font-bold">Piezas esperadas</th>
                        <th scope="col" class="px-3 py-3 text-right font-bold">Piezas producidas</th>
                        <th scope="col" class="px-5 py-3 text-right font-bold sm:pr-6">
                            <span class="inline-flex items-center justify-end gap-1.5">
                                Eficiencia
                                {{-- "?" : al pasar el mouse (o con el teclado) muestra cómo se calcula --}}
                                <span x-data="{ show: false }" class="relative normal-case tracking-normal"
                                    @mouseenter="show = true" @mouseleave="show = false" @focusin="show = true" @focusout="show = false">
                                    <button type="button" aria-label="Cómo se calcula la eficiencia"
                                        class="flex h-4 w-4 items-center justify-center rounded-full bg-brand-200 text-[10px] font-bold leading-none text-brand-900 hover:bg-brand-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600/40 dark:bg-brand-500/30 dark:text-brand-100">?</button>
                                    <span x-show="show" x-transition.opacity.duration.100ms style="display: none;" role="tooltip"
                                        class="absolute right-0 top-full z-30 mt-2 w-80 rounded-xl border border-gray-200 bg-white p-3 text-left text-xs font-normal leading-5 text-gray-600 shadow-lg dark:border-ta-gray-800 dark:bg-ta-gray-900 dark:text-ta-gray-300">
                                        <span class="block font-bold text-gray-800 dark:text-white/90">Eficiencia = Piezas producidas ÷ Piezas esperadas × 100</span>
                                        <span class="mt-1.5 block">Piezas esperadas = SPM estándar × Tiempo trabajado (min) × Piezas por golpe</span>
                                        <span class="block">SPM estándar = production_rate (golpes/hora) ÷ 60</span>
                                        <span class="mt-1.5 block text-gray-500 dark:text-ta-gray-400">Meta: {{ PressEfficiency::GOOD_THRESHOLD }} % o más</span>
                                    </span>
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>
                {{-- Esqueleto: reemplaza las filas mientras cambia un filtro (con retraso, para no parpadear en cargas rápidas) --}}
                <tbody class="hidden divide-y divide-gray-100 dark:divide-ta-gray-800" aria-hidden="true"
                    wire:loading.delay.class.remove="hidden" wire:target="{{ $loadingTargets }}">
                    @include('livewire.guest.partials.press-efficiency-skeleton-rows', ['bone' => $bone])
                </tbody>
                <tbody class="divide-y divide-gray-100 dark:divide-ta-gray-800" wire:loading.delay.class="hidden" wire:target="{{ $loadingTargets }}">
                    @forelse($pageRows as $row)
                        @php $tone = PressEfficiency::efficiencyTone($row['efficiency']); @endphp
                        <tr wire:key="row-{{ $row['press'] }}-{{ $row['die'] }}" class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-white/90 whitespace-nowrap sm:pl-6">{{ $row['press'] }}</td>
                            <td class="px-3 py-2.5 text-gray-700 dark:text-ta-gray-300 whitespace-nowrap">{{ $row['die'] }}</td>
                            <td class="px-3 py-2.5 text-gray-500 dark:text-ta-gray-400">
                                @foreach($row['parts'] as $part)
                                    <span class="block whitespace-nowrap tabular-nums">{{ $part }}</span>
                                @endforeach
                            </td>
                            <td class="px-3 py-2.5 text-gray-500 dark:text-ta-gray-400">
                                @foreach($row['names'] as $name)
                                    <span class="block">{{ $name }}</span>
                                @endforeach
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-ta-gray-400">
                                @if($row['spmStandard'] !== null)
                                    {{ number_format($row['spmStandard'], 1) }}
                                @else
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $toneBadge['none'] }}">Sin estándar</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-ta-gray-400">
                                {{ $row['spmReal'] !== null ? number_format($row['spmReal'], 1) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-medium {{ $row['difference'] === null ? $toneText['none'] : ($row['difference'] < 0 ? $toneText['bad'] : $toneText['good']) }}">
                                {{ $row['difference'] !== null ? ($row['difference'] > 0 ? '+' : '') . number_format($row['difference'], 1) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-ta-gray-400 whitespace-nowrap"
                                title="{{ $row['runs'] }} {{ $row['runs'] === 1 ? 'corrida' : 'corridas' }}">
                                {{ $duration($row['seconds']) }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-ta-gray-400"
                                @if($row['piecesPerShot'] > 1) title="{{ $row['piecesPerShot'] }} piezas por golpe" @endif>
                                {{ $row['expected'] !== null ? number_format($row['expected']) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-medium text-gray-800 dark:text-white/90">{{ number_format($row['produced']) }}</td>
                            <td class="px-5 py-2.5 text-right sm:pr-6">
                                <span class="inline-flex min-w-[4rem] justify-center rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums {{ $toneBadge[$tone] }}">
                                    {{ $row['efficiency'] !== null ? number_format($row['efficiency'], 1) . ' %' : '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-5 py-14 text-center text-gray-500 dark:text-ta-gray-400">
                                {{ $range?->isFuture ? 'El periodo seleccionado aún no inicia.' : 'Sin producción registrada en las estaciones seleccionadas para este periodo.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie compacto: semáforo de eficiencia (de menor a mayor) a la izquierda; paginación (5 troqueles por página) a la derecha --}}
        <div class="flex flex-col gap-2 border-t border-gray-100 dark:border-ta-gray-800 px-5 py-2 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500 dark:text-ta-gray-400">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-danger-500" aria-hidden="true"></span>&lt; {{ PressEfficiency::WARN_THRESHOLD }} %</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-warning-500" aria-hidden="true"></span>{{ PressEfficiency::WARN_THRESHOLD }} – {{ PressEfficiency::GOOD_THRESHOLD }} %</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-success-500" aria-hidden="true"></span>≥ {{ PressEfficiency::GOOD_THRESHOLD }} %</span>
            </div>

            @if($pagination->total > 0)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                <p class="text-xs text-gray-500 dark:text-ta-gray-400">
                    Mostrando <span class="font-medium text-gray-800 dark:text-white/90 tabular-nums">{{ $pagination->from }}</span>
                    a <span class="font-medium text-gray-800 dark:text-white/90 tabular-nums">{{ $pagination->to }}</span>
                    de <span class="font-medium text-gray-800 dark:text-white/90 tabular-nums">{{ $pagination->total }}</span> troqueles
                </p>

                @if($pagination->pages > 1)
                    @php
                        // Páginas visibles: la primera, la última y dos alrededor de la actual
                        $visible = collect(range(1, $pagination->pages))
                            ->filter(fn ($p) => $p === 1 || $p === $pagination->pages || abs($p - $page) <= 1)
                            ->values();
                    @endphp
                    <nav class="flex items-center gap-1.5" aria-label="Paginación">
                        <button wire:click="goToPage({{ $page - 1 }})" type="button" @disabled($page <= 1)
                            class="{{ $control }} !h-7 !text-xs inline-flex items-center gap-1 px-2.5 font-medium disabled:opacity-40 disabled:pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                            Anterior
                        </button>

                        <div class="hidden sm:flex items-center gap-1">
                            @foreach($visible as $i => $p)
                                @if($i > 0 && $p - $visible[$i - 1] > 1)
                                    <span class="w-7 text-center text-xs text-gray-400">…</span>
                                @endif
                                <button wire:click="goToPage({{ $p }})" type="button" aria-label="Página {{ $p }}" @if($p === $page) aria-current="page" @endif
                                    class="h-7 min-w-[1.75rem] px-1.5 rounded-md text-xs font-medium tabular-nums transition-colors {{ $p === $page
                                        ? 'bg-brand-600 text-white'
                                        : 'text-gray-700 hover:bg-brand-600/10 hover:text-brand-600 dark:text-ta-gray-400 dark:hover:text-brand-400' }}">
                                    {{ $p }}
                                </button>
                            @endforeach
                        </div>

                        <button wire:click="goToPage({{ $page + 1 }})" type="button" @disabled($page >= $pagination->pages)
                            class="{{ $control }} !h-7 !text-xs inline-flex items-center gap-1 px-2.5 font-medium disabled:opacity-40 disabled:pointer-events-none">
                            Siguiente
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </button>
                    </nav>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- Gráficas Top 10: título en franja azul suave y la unidad del eje abajo --}}
    <div x-data="pressEfficiencyCharts" wire:ignore class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6">
        @foreach([
            ['deviation', 'Top 10 · Troqueles con mayor desviación respecto al SPM estándar', 'Diferencia SPM (golpes/min)', 'Ningún troquel por debajo del SPM estándar.'],
            ['efficiency', 'Top 10 · Troqueles con menor eficiencia', 'Eficiencia (%)', 'Sin troqueles con SPM estándar en este periodo.'],
        ] as [$key, $title, $axis, $emptyText])
            <div class="{{ $card }} overflow-hidden">
                <div class="flex items-center gap-2.5 border-b border-brand-100 bg-brand-50 px-5 py-2.5 sm:px-6 dark:border-brand-500/20 dark:bg-brand-500/10">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white text-brand-600 shadow-theme-xs dark:bg-brand-500/20 dark:text-brand-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </span>
                    <h3 class="text-sm font-bold uppercase tracking-wide text-brand-900 dark:text-brand-100">{{ $title }}</h3>
                </div>
                <div class="relative px-4 pt-4 h-[380px] sm:px-5">
                    <canvas x-ref="{{ $key }}" aria-label="{{ $title }}" role="img" aria-describedby="pe-chart-{{ $key }}-text"></canvas>
                    <p id="pe-chart-{{ $key }}-text" class="sr-only" x-text="summary('{{ $key }}')"></p>
                    <div wire:loading.delay.class="!flex" wire:target="{{ $loadingTargets }}" aria-hidden="true"
                        class="absolute inset-0 z-10 hidden items-end gap-3 bg-white px-6 pb-6 pt-4 dark:bg-ta-gray-900">
                        @foreach([70, 45, 85, 30, 60, 50, 75, 40] as $h)
                            <div class="flex-1 rounded-t {{ $bone }}" style="height: {{ $h }}%"></div>
                        @endforeach
                    </div>
                    <div x-show="!data.{{ $key }}.length" x-cloak
                        class="absolute inset-0 flex items-center justify-center text-sm text-gray-500 dark:text-ta-gray-400">
                        {{ $emptyText }}
                    </div>
                </div>
                <p class="px-5 pb-4 pt-1 text-center text-xs text-gray-500 dark:text-ta-gray-400 sm:px-6">{{ $axis }}</p>
            </div>
        @endforeach

        {{-- Volumen y eficiencia por troquel: barras = piezas producidas, línea = eficiencia (eje derecho) --}}
        <div class="{{ $card }} overflow-hidden xl:col-span-2">
            <div class="flex flex-col gap-1 border-b border-brand-100 bg-brand-50 px-5 py-3.5 sm:px-6 sm:flex-row sm:items-center sm:justify-between dark:border-brand-500/20 dark:bg-brand-500/10">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white text-brand-600 shadow-theme-xs dark:bg-brand-500/20 dark:text-brand-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </span>
                    <h3 class="text-sm font-bold uppercase tracking-wide text-brand-900 dark:text-brand-100">Piezas producidas y eficiencia por troquel (MDI)</h3>
                </div>
            </div>
            {{-- Todos los troqueles del periodo: ~34 px por barra; si no caben, se desplaza a lo ancho --}}
            <div class="relative px-4 pt-4 pb-3 sm:px-5">
                <div x-ref="volumeScroll" class="overflow-x-auto pb-2">
                    <div x-ref="volumeWrap" class="relative h-[460px]">
                        <canvas x-ref="volume" aria-label="Piezas producidas y eficiencia por troquel" role="img" aria-describedby="pe-chart-volume-text"></canvas>
                    </div>
                </div>
                <p id="pe-chart-volume-text" class="sr-only" x-text="summary('volume')"></p>
                <div wire:loading.delay.class="!flex" wire:target="{{ $loadingTargets }}" aria-hidden="true"
                    class="absolute inset-0 z-10 hidden items-end gap-3 bg-white px-6 pb-6 pt-4 dark:bg-ta-gray-900">
                    @foreach([70, 45, 85, 30, 60, 50, 75, 40] as $h)
                        <div class="flex-1 rounded-t {{ $bone }}" style="height: {{ $h }}%"></div>
                    @endforeach
                    </div>
                <div x-show="!data.volume.length" x-cloak
                    class="absolute inset-0 flex items-center justify-center text-sm text-gray-500 dark:text-ta-gray-400">
                    Sin producción en este periodo.
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    // Calendario del periodo: semana (cualquier día lleva a su semana), mes (cuadrícula de meses) o año (cuadrícula de años)
    Alpine.data('peCalendar', () => ({
        open: false,
        mode: 'week',
        year: 0,
        month: 0,
        today: '',
        todayYear: 0,
        // Último año visible en la cuadrícula de años (se muestran 12 hacia atrás)
        lastYear: 0,
        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],

        iso(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; },
        parse(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); },

        toggle() {
            this.open = !this.open;
            if (!this.open) return;
            this.mode = $wire.period;
            this.today = this.iso(new Date());
            this.todayYear = new Date().getFullYear();
            const d = this.parse($wire.from || this.today);
            this.year = d.getFullYear();
            this.month = d.getMonth();
            this.lastYear = Math.max(this.year, this.todayYear);
        },
        move(step) {
            if (this.mode === 'year') { this.lastYear += step * 12; return; }
            if (this.mode === 'month') { this.year += step; return; }
            const d = new Date(this.year, this.month + step, 1);
            this.year = d.getFullYear();
            this.month = d.getMonth();
        },
        navLabel(step) {
            const labels = { year: ['Años anteriores', 'Años siguientes'], month: ['Año anterior', 'Año siguiente'], week: ['Mes anterior', 'Mes siguiente'] };
            return (labels[this.mode] || labels.week)[step < 0 ? 0 : 1];
        },
        get currentLabel() {
            return { year: 'Ir al año actual', month: 'Ir al mes actual' }[this.mode] || 'Ir a la semana actual';
        },
        get title() {
            if (this.mode === 'year') return `${this.lastYear - 11} – ${this.lastYear}`;
            return this.mode === 'month' ? String(this.year) : `${this.monthNames[this.month]} ${this.year}`;
        },

        get years() { return Array.from({ length: 12 }, (_, i) => this.lastYear - 11 + i); },
        yearSelected(y) { return Number(($wire.from || '').slice(0, 4)) === y; },
        pickYear(y) { this.open = false; $wire.setYear(y); },

        // 6 semanas de lunes a domingo, empezando en el lunes de la semana del día 1
        get weeks() {
            const first = new Date(this.year, this.month, 1);
            const start = new Date(this.year, this.month, 1 - ((first.getDay() + 6) % 7));
            return Array.from({ length: 6 }, (_, w) => Array.from({ length: 7 }, (_, i) => {
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + w * 7 + i);
                return { iso: this.iso(d), day: d.getDate(), month: d.getMonth(), inMonth: d.getMonth() === this.month };
            }));
        },
        pickDay(iso) { this.open = false; $wire.setWeek(iso); },

        monthKey(i) { return `${this.year}-${String(i + 1).padStart(2, '0')}`; },
        monthDisabled(i) { return this.monthKey(i) > this.today.slice(0, 7); },
        monthSelected(i) { return ($wire.from || '').slice(0, 7) === this.monthKey(i); },
        pickMonth(i) { this.open = false; $wire.setMonth(this.monthKey(i)); },
    }));

    Alpine.data('pressEfficiencyCharts', () => {
        const GOOD = {{ PressEfficiency::GOOD_THRESHOLD }};
        const WARN = {{ PressEfficiency::WARN_THRESHOLD }};
        const charts = {};
        let observer = null;
        let timer = null;

        const isDark = () => document.documentElement.classList.contains('dark');

        // Barras con borde sólido y relleno claro, igual que las gráficas de las vistas de invitados.
        const palette = () => isDark()
            ? {
                text: '#d0d5dd', muted: '#98a2b3', grid: 'rgba(152,162,179,0.15)', goal: '#60a5fa',
                line: '#e4e7ec', pointRing: '#171f2f', bar: { fill: 'rgba(96,165,250,0.25)', border: 'rgb(96,165,250)' },
                barNoStd: { fill: 'rgba(152,162,179,0.18)', border: 'rgb(102,112,133)' },
                bad: { fill: 'rgba(239,68,68,0.3)', border: 'rgb(248,113,113)' },
                warn: { fill: 'rgba(245,158,11,0.3)', border: 'rgb(251,191,36)' },
                good: { fill: 'rgba(34,197,94,0.3)', border: 'rgb(74,222,128)' },
            }
            : {
                text: '#3f3f46', muted: '#71717a', grid: 'rgba(113,113,122,0.15)', goal: '#2563eb',
                line: '#3f3f46', pointRing: '#ffffff', bar: { fill: 'rgba(37,99,235,0.15)', border: 'rgb(37,99,235)' },
                barNoStd: { fill: 'rgba(161,161,170,0.25)', border: 'rgb(161,161,170)' },
                bad: { fill: 'rgba(220,38,38,0.2)', border: 'rgb(185,28,28)' },
                warn: { fill: 'rgba(217,119,6,0.2)', border: 'rgb(180,83,9)' },
                good: { fill: 'rgba(22,163,74,0.2)', border: 'rgb(21,128,61)' },
            };

        const tone = (value, c) => value >= GOOD ? c.good : (value >= WARN ? c.warn : c.bad);

        // Valor al final de cada barra y línea de meta (solo en eficiencia).
        const valueLabels = {
            id: 'peValueLabels',
            afterDatasetsDraw(chart, args, opts) {
                const { ctx, chartArea, scales } = chart;
                const c = palette();
                const meta = chart.getDatasetMeta(0);
                const values = chart.data.datasets[0].data;

                ctx.save();
                ctx.font = '600 12px Figtree, ui-sans-serif, system-ui, sans-serif';
                ctx.fillStyle = c.text;
                ctx.textBaseline = 'middle';

                meta.data.forEach((bar, i) => {
                    const value = values[i];
                    const negative = value < 0;
                    ctx.textAlign = negative ? 'right' : 'left';
                    ctx.fillText(value.toFixed(1) + (opts.suffix || ''), bar.x + (negative ? -6 : 6), bar.y);
                });

                if (opts.goal && values.length) {
                    const x = scales.x.getPixelForValue(opts.goal);
                    ctx.strokeStyle = c.goal;
                    ctx.setLineDash([5, 4]);
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(x, chartArea.top);
                    ctx.lineTo(x, chartArea.bottom);
                    ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.fillStyle = c.goal;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.font = '600 11px Figtree, ui-sans-serif, system-ui, sans-serif';
                    ctx.fillText('Meta ' + opts.goal + ' %', x, chartArea.top - 2);
                }
                ctx.restore();
            },
        };

        const options = (key, items) => {
            const c = palette();
            const values = items.map(i => i.value);
            const isEfficiency = key === 'efficiency';
            const min = isEfficiency ? 0 : Math.floor(Math.min(0, ...values) * 1.2);
            const max = isEfficiency ? Math.max(100, Math.ceil(Math.max(0, ...values) * 1.12)) : 0;

            return {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                // Estática: sin animación al cargar ni en cada refresco.
                animation: false,
                layout: { padding: { top: isEfficiency ? 16 : 4, right: isEfficiency ? 8 : 4 } },
                scales: {
                    x: {
                        min, max,
                        grid: { color: c.grid },
                        border: { display: false },
                        ticks: { color: c.muted, font: { size: 11 } },
                    },
                    y: {
                        grid: { display: false },
                        border: { color: c.grid },
                        ticks: { color: c.text, font: { size: 12, weight: '600' } },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (ctx) => items[ctx[0].dataIndex]?.label ?? '',
                            afterTitle: (ctx) => items[ctx[0].dataIndex]?.press ?? '',
                            label: (ctx) => isEfficiency
                                ? ` Eficiencia: ${ctx.raw.toFixed(1)} %`
                                : ` Diferencia: ${ctx.raw.toFixed(1)} golpes/min`,
                        },
                    },
                    peValueLabels: { suffix: isEfficiency ? ' %' : '', goal: isEfficiency ? GOOD : null },
                },
            };
        };

        const dataset = (key, items) => {
            const c = palette();
            const colors = items.map(i => key === 'efficiency' ? tone(i.value, c) : c.bad);
            return {
                labels: items.map(i => i.label),
                datasets: [{
                    data: items.map(i => i.value),
                    backgroundColor: colors.map(color => color.fill),
                    borderColor: colors.map(color => color.border),
                    borderWidth: 2,
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.75,
                    categoryPercentage: 0.85,
                    maxBarThickness: 26,
                }],
            };
        };

        // Volumen vs eficiencia: barras (piezas producidas, eje izquierdo) y línea (eficiencia, eje derecho).
        // Sin línea de meta (confundía con dos ejes); los puntos llevan el color de su eficiencia.
        const volumeChart = (items) => {
            const c = palette();
            const fmt = (n) => Number(n).toLocaleString('es-MX');
            const effMax = Math.max(100, Math.ceil(Math.max(0, ...items.map(i => i.value ?? 0)) / 10) * 10 + 10);
            const pointColors = items.map(i => i.value === null ? 'transparent' : tone(i.value, c).border);
            const hasStd = items.map(i => i.value !== null);

            return {
                data: {
                    labels: items.map(i => i.label),
                    datasets: [
                        {
                            type: 'line',
                            label: 'Eficiencia (%)',
                            data: items.map(i => i.value),
                            yAxisID: 'y1',
                            order: 0,
                            borderColor: c.line,
                            borderWidth: 1.5,
                            tension: 0.25,
                            // Sin estándar no hay punto; la línea une los troqueles que sí tienen eficiencia
                            spanGaps: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: pointColors,
                            pointBorderColor: c.pointRing,
                            pointBorderWidth: 2,
                        },
                        {
                            type: 'bar',
                            label: 'Piezas producidas',
                            data: items.map(i => i.produced),
                            yAxisID: 'y',
                            order: 1,
                            backgroundColor: hasStd.map(ok => ok ? c.bar.fill : c.barNoStd.fill),
                            borderColor: hasStd.map(ok => ok ? c.bar.border : c.barNoStd.border),
                            borderWidth: 1.5,
                            borderRadius: 3,
                            borderSkipped: false,
                            maxBarThickness: 28,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: { mode: 'index', intersect: false },
                    layout: { padding: { top: 8 } },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { color: c.grid },
                            ticks: { color: c.text, font: { size: 10, weight: '600' }, maxRotation: 90, minRotation: 90, autoSkip: false },
                        },
                        y: {
                            position: 'left',
                            beginAtZero: true,
                            grid: { color: c.grid },
                            border: { display: false },
                            title: { display: true, text: 'Piezas producidas', color: c.muted, font: { size: 11 } },
                            ticks: { color: c.muted, font: { size: 11 }, callback: (v) => fmt(v) },
                        },
                        y1: {
                            position: 'right',
                            min: 0,
                            max: effMax,
                            grid: { drawOnChartArea: false },
                            border: { display: false },
                            title: { display: true, text: 'Eficiencia (%)', color: c.muted, font: { size: 11 } },
                            ticks: { color: c.muted, font: { size: 11 }, callback: (v) => v + ' %' },
                        },
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { color: c.text, usePointStyle: true, boxWidth: 8, boxHeight: 8, font: { size: 12 } },
                        },
                        tooltip: {
                            callbacks: {
                                title: (ctx) => items[ctx[0].dataIndex]?.label ?? '',
                                afterTitle: (ctx) => items[ctx[0].dataIndex]?.press ?? '',
                                label: () => null,
                                afterBody: (ctx) => {
                                    const i = items[ctx[0].dataIndex];
                                    if (!i) return [];
                                    return i.value === null
                                        ? [`Piezas producidas: ${fmt(i.produced)}`, 'Sin SPM estándar: no se calcula la eficiencia']
                                        : [
                                            `Piezas producidas: ${fmt(i.produced)}`,
                                            // El troquel corre también en prensas sin estándar: solo esas piezas cuentan en la eficiencia
                                            ...(i.producedStd < i.produced ? [`  con SPM estándar: ${fmt(i.producedStd)}`] : []),
                                            `Piezas esperadas: ${fmt(i.expected)}`,
                                            `Eficiencia: ${i.value.toFixed(1)} %`,
                                            `Piezas no producidas: ${fmt(i.lost)}`,
                                        ];
                                },
                            },
                        },
                    },
                },
            };
        };

        return {
            data: @js($charts),

            // Texto alternativo de cada gráfica (lectores de pantalla): los mismos datos que dibuja
            summary(key) {
                const items = this.data[key] || [];
                if (!items.length) return 'Sin datos en este periodo.';
                const fmt = (n) => Number(n).toLocaleString('es-MX');
                if (key === 'volume') {
                    return items.slice(0, 15).map(i => `${i.label}: ${fmt(i.produced)} piezas` + (i.value === null ? ', sin SPM estándar' : `, eficiencia ${i.value.toFixed(1)} %`)).join('; ')
                        + (items.length > 15 ? `; y ${items.length - 15} troqueles más.` : '.');
                }
                return items.map(i => `${i.label} (${i.press}): ${i.value.toFixed(1)}${key === 'efficiency' ? ' %' : ' golpes/min'}`).join('; ') + '.';
            },

            init() {
                // Misma fuente que el resto de la vista (Figtree) en ejes, tooltips y etiquetas
                Chart.defaults.font.family = getComputedStyle(this.$el).fontFamily;

                // Refresco cada minuto con setInterval (como press-production): wire:poll se
                // frena ~95 % cuando la pestaña no está al frente y la vista parecía congelada.
                timer = setInterval(() => $wire.tick(), 60000);

                // Carga diferida: Livewire convierte el esqueleto en la vista y este init corre antes de
                // que existan los <canvas>; se dibuja en el siguiente ciclo, con el DOM ya completo.
                this.$nextTick(() => this.draw());

                $wire.on('press-efficiency-charts', ({ charts: next }) => {
                    this.data = next;
                    this.draw();
                });

                // Al cambiar de tema se repintan con los colores correspondientes.
                observer = new MutationObserver(() => this.draw(true));
                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            },

            destroy() {
                clearInterval(timer);
                observer?.disconnect();
                Object.values(charts).forEach(chart => chart.destroy());
            },

            draw(rebuild = false) {
                // Sin Chart.js (assets sin cargar) se omiten las gráficas; el resto sigue funcionando.
                if (typeof Chart === 'undefined') return;
                if (!this.$refs.volumeScroll || !this.$refs.deviation) return;

                ['deviation', 'efficiency'].forEach(key => {
                    const items = this.data[key] || [];

                    if (charts[key] && !rebuild) {
                        charts[key].data = dataset(key, items);
                        charts[key].options = options(key, items);
                        charts[key].update('none');
                        return;
                    }

                    charts[key]?.destroy();
                    charts[key] = new Chart(this.$refs[key], {
                        type: 'bar',
                        data: dataset(key, items),
                        options: options(key, items),
                        plugins: [valueLabels],
                    });
                });

                const volumeItems = this.data.volume || [];
                const width = Math.max(this.$refs.volumeScroll.clientWidth, volumeItems.length * 34 + 120);
                const resized = this.$refs.volumeWrap.style.width !== width + 'px';
                this.$refs.volumeWrap.style.width = width + 'px';

                const volume = volumeChart(volumeItems);
                if (charts.volume && !rebuild && !resized) {
                    charts.volume.data = volume.data;
                    charts.volume.options = volume.options;
                    charts.volume.update('none');
                } else {
                    charts.volume?.destroy();
                    charts.volume = new Chart(this.$refs.volume, { type: 'bar', ...volume });
                }
            },
        };
    });
</script>
@endscript
