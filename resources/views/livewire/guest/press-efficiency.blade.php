@php
    use App\Livewire\Guest\PressEfficiency;

    // Estilo TailAdmin: texto y badges redondos con fondo suave
    $toneText = [
        'good' => 'text-emerald-600 dark:text-emerald-400',
        'warn' => 'text-amber-600 dark:text-amber-400',
        'bad' => 'text-red-600 dark:text-red-400',
        'none' => 'text-gray-400 dark:text-gray-500',
    ];
    $toneBadge = [
        'good' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
        'warn' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'bad' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
        'none' => 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400',
    ];

    $duration = function (float $minutes): string {
        $total = (int) round($minutes);
        $hours = intdiv($total, 60);
        $mins = $total % 60;
        return $hours > 0 ? "{$hours} h " . str_pad($mins, 2, '0', STR_PAD_LEFT) . ' min' : "{$mins} min";
    };

    // Piezas de TailAdmin: tarjeta, control (botón/selector) y panel desplegable
    $card = 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]';
    $control = 'h-9 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 shadow-[0px_1px_2px_0px_rgba(16,24,40,0.05)] hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03] transition-colors';
    $panel = 'absolute left-0 mt-2 rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900 z-50 overflow-hidden';
    $chevron = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 transition-transform" :class="{ \'rotate-180\': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>';

    $selectedStations = $stations->whereIn('id', $selectedWorkCenters);
    $stationsLabel = match (true) {
        $selectedStations->isEmpty() => 'Todas las estaciones',
        $selectedStations->count() === 1 => $selectedStations->first()->name,
        default => $selectedStations->count() . ' estaciones',
    };
    $periodNoun = $period === 'month' ? 'mes' : 'semana';
    $loadingTargets = 'goToPage,selectedWorkCenters,clearWorkCenters,clearFilters,setPeriod,currentPeriod,prevPeriod,nextPeriod,setWeek,setMonth';
@endphp

<div class="flex flex-col gap-4 md:gap-6">

    {{-- Filtros: estaciones a la izquierda; Excel y periodo (semana o mes con su calendario) a la derecha --}}
    <div class="relative z-20 {{ $card }} px-4 py-3 sm:px-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Multi select de estaciones del área de Estampado (vacío = todas) --}}
            <div x-data="{ open: false, search: '' }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative w-full sm:w-56">
                <button @click="open = !open" type="button" class="{{ $control }} w-full flex items-center justify-between gap-2 px-3">
                    <span class="flex items-center gap-2 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                        </svg>
                        <span class="truncate font-medium">{{ $stationsLabel }}</span>
                    </span>
                    <span class="flex items-center gap-1.5 flex-shrink-0">
                        @if($selectedStations->isNotEmpty())
                            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[11px] font-semibold bg-[#2563eb] text-white rounded-full">{{ $selectedStations->count() }}</span>
                        @endif
                        {!! $chevron !!}
                    </span>
                </button>

                <div x-show="open" x-transition.opacity.duration.100ms style="display: none;" class="{{ $panel }} w-full sm:w-72">
                    <div class="p-2 border-b border-gray-200 dark:border-gray-800">
                        <input x-model="search" type="text" placeholder="Buscar estación..."
                            class="w-full h-9 px-3 text-sm rounded-lg border border-gray-300 bg-transparent text-gray-800 placeholder:text-gray-400 focus:border-[#2563eb] focus:outline-none focus:ring-2 focus:ring-[#2563eb]/20 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">
                    </div>
                    <div class="max-h-72 overflow-y-auto py-1">
                        @foreach($stations->groupBy('line') as $line => $lineStations)
                            <div x-show="!search || @js(\Illuminate\Support\Str::lower($line . ' ' . $lineStations->pluck('name')->implode(' ') . ' ' . $lineStations->pluck('number')->implode(' '))).includes(search.toLowerCase())"
                                class="px-3 pt-2.5 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                {{ $line }}
                            </div>
                            @foreach($lineStations as $station)
                                <label wire:key="station-{{ $station->id }}"
                                    x-show="!search || @js(\Illuminate\Support\Str::lower($line . ' ' . $station->name . ' ' . $station->number)).includes(search.toLowerCase())"
                                    class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.03] transition-colors">
                                    <input type="checkbox" wire:model.live="selectedWorkCenters" value="{{ $station->id }}"
                                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-[#2563eb] focus:ring-[#2563eb]/20 focus:ring-offset-0 flex-shrink-0">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $station->name }}</span>
                                    <span class="ml-auto text-xs tabular-nums text-gray-400 dark:text-gray-500">{{ $station->number }}</span>
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                    @if($selectedStations->isNotEmpty())
                        <div class="p-2 border-t border-gray-200 dark:border-gray-800">
                            <button wire:click="clearWorkCenters" type="button"
                                class="w-full h-8 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                                Limpiar selección (ver todas)
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Limpiar filtros: todas las estaciones y la semana en curso --}}
            @if($selectedStations->isNotEmpty() || !$range?->isLive || $period !== 'week')
                <button wire:click="clearFilters" type="button" title="Limpiar filtros"
                    class="h-9 inline-flex items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-gray-500 hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-500/10 dark:hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563eb]/40 transition-colors">
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
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span wire:loading.remove wire:target="export">Exportar</span>
                <span wire:loading wire:target="export">Generando…</span>
            </button>

            <span wire:loading.delay wire:target="{{ $loadingTargets }}" class="text-sm text-gray-500 dark:text-gray-400">Cargando…</span>

            {{-- Periodo: Semana o Mes (selector tipo pastilla de TailAdmin) --}}
            <div class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900" role="group" aria-label="Periodo">
                @foreach(PressEfficiency::PERIODS as $value => $text)
                    <button wire:click="setPeriod('{{ $value }}')" type="button" aria-pressed="{{ $period === $value ? 'true' : 'false' }}"
                        class="h-8 px-3.5 rounded-md text-sm font-medium transition-colors {{ $period === $value ? 'bg-white text-gray-900 shadow-[0px_1px_3px_0px_rgba(16,24,40,0.1)] dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">
                        {{ $text }}
                    </button>
                @endforeach
            </div>

            {{-- Flechas y calendario --}}
            <div class="flex items-center gap-1.5">
                <button wire:click="prevPeriod" type="button" title="{{ $period === 'month' ? 'Mes anterior' : 'Semana anterior' }}" class="{{ $control }} w-9 flex items-center justify-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>

                {{--
                    Calendario (Alpine + Tailwind): semana = clic en cualquier día; mes = cuadrícula de meses.
                    El panel lleva wire:ignore para que el refresco no lo cierre; lee el periodo de $wire al abrirse.
                --}}
                <div x-data="peCalendar" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button type="button" @click="toggle()" :aria-expanded="open" title="Elegir {{ $periodNoun }}"
                        class="{{ $control }} min-w-[10rem] inline-flex items-center justify-center gap-2 px-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        <span class="font-medium text-gray-800 dark:text-white/90 whitespace-nowrap">{{ $periodLabel }}</span>
                    </button>

                    <div wire:ignore x-show="open" x-transition.opacity.duration.100ms style="display: none;"
                        class="{{ $panel }} !left-auto right-0 w-72 p-3" role="dialog" aria-label="Calendario">
                        <div class="flex items-center justify-between mb-2">
                            <button type="button" @click="move(-1)" title="Anterior" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563eb]/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="title"></span>
                            <button type="button" @click="move(1)" title="Siguiente" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563eb]/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        </div>

                        {{-- Mes: cuadrícula de meses del año --}}
                        <div x-show="mode === 'month'" class="grid grid-cols-3 gap-1">
                            <template x-for="(name, i) in monthNames" :key="i">
                                <button type="button" @click="pickMonth(i)" :disabled="monthDisabled(i)"
                                    class="h-10 rounded-lg text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563eb]/40 disabled:opacity-30 disabled:pointer-events-none"
                                    :class="monthSelected(i) ? 'bg-[#2563eb] text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5'"
                                    x-text="name.slice(0, 3)"></button>
                            </template>
                        </div>

                        {{-- Semana: días de lunes a domingo; la semana elegida va sombreada --}}
                        <div x-show="mode === 'week'">
                            <div class="grid grid-cols-7 mb-1">
                                <template x-for="d in ['L', 'M', 'M', 'J', 'V', 'S', 'D']">
                                    <span class="h-8 flex items-center justify-center text-xs font-medium text-gray-400" x-text="d"></span>
                                </template>
                            </div>
                            <template x-for="week in weeks" :key="week[0].iso">
                                <div class="grid grid-cols-7 rounded-lg" :class="week[0].iso === $wire.from ? 'bg-[#2563eb]/10 dark:bg-[#2563eb]/20' : 'hover:bg-gray-50 dark:hover:bg-white/[0.03]'">
                                    <template x-for="day in week" :key="day.iso">
                                        <button type="button" @click="pickDay(day.iso)" :disabled="day.iso > today"
                                            class="h-9 text-sm tabular-nums rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563eb]/40 disabled:opacity-30 disabled:pointer-events-none"
                                            :class="day.iso === today ? 'font-bold text-[#2563eb] dark:text-[#60a5fa]' : (day.inMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-600')"
                                            x-text="day.day"></button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <button wire:click="nextPeriod" type="button" title="{{ $period === 'month' ? 'Mes siguiente' : 'Semana siguiente' }}" @disabled(!$canGoNext)
                    class="{{ $control }} w-9 flex items-center justify-center text-gray-500 disabled:opacity-40 disabled:pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>

            @unless($range?->isLive)
                <button wire:click="currentPeriod" type="button" class="{{ $control }} px-3 font-medium">
                    {{ $period === 'month' ? 'Mes actual' : 'Semana actual' }}
                </button>
            @endunless
        </div>
    </div>

    {{-- Tabla por troquel --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="overflow-x-auto transition-opacity" wire:loading.delay.class="opacity-50" wire:target="{{ $loadingTargets }}">
            <table class="w-full min-w-[1100px] text-xs">
                <thead>
                    {{-- Renglón de títulos: mayúsculas sobre azul suave, igual que los títulos de las gráficas --}}
                    <tr class="border-b border-blue-100 bg-blue-50 text-[11px] uppercase tracking-wide text-blue-900 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-100">
                        <th class="px-5 py-3 text-left font-bold sm:pl-6">Prensa</th>
                        <th class="px-3 py-3 text-left font-bold">Troquel (MDI)</th>
                        <th class="px-3 py-3 text-left font-bold">Número de parte</th>
                        <th class="px-3 py-3 text-left font-bold">Descripción</th>
                        <th class="px-3 py-3 text-right font-bold">SPM estándar</th>
                        <th class="px-3 py-3 text-right font-bold">SPM real</th>
                        <th class="px-3 py-3 text-right font-bold">Diferencia SPM</th>
                        <th class="px-3 py-3 text-right font-bold">Tiempo trabajado</th>
                        <th class="px-3 py-3 text-right font-bold">Piezas esperadas</th>
                        <th class="px-3 py-3 text-right font-bold">Piezas producidas</th>
                        <th class="px-5 py-3 text-right font-bold sm:pr-6">
                            <span class="inline-flex items-center justify-end gap-1.5">
                                Eficiencia
                                {{-- "?" : al pasar el mouse (o con el teclado) muestra cómo se calcula --}}
                                <span x-data="{ show: false }" class="relative normal-case tracking-normal"
                                    @mouseenter="show = true" @mouseleave="show = false" @focusin="show = true" @focusout="show = false">
                                    <button type="button" aria-label="Cómo se calcula la eficiencia"
                                        class="flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold leading-none text-blue-900 hover:bg-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563eb]/40 dark:bg-blue-500/30 dark:text-blue-100">?</button>
                                    <span x-show="show" x-transition.opacity.duration.100ms style="display: none;" role="tooltip"
                                        class="absolute right-0 top-full z-30 mt-2 w-80 rounded-xl border border-gray-200 bg-white p-3 text-left text-xs font-normal leading-5 text-gray-600 shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                        <span class="block font-bold text-gray-800 dark:text-white/90">Eficiencia = Piezas producidas ÷ Piezas esperadas × 100</span>
                                        <span class="mt-1.5 block">Piezas esperadas = SPM estándar × Tiempo trabajado (min) × Piezas por golpe</span>
                                        <span class="block">SPM estándar = production_rate (golpes/hora) ÷ 60</span>
                                        <span class="mt-1.5 block text-gray-400 dark:text-gray-500">Meta: {{ PressEfficiency::GOOD_THRESHOLD }} % o más</span>
                                    </span>
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($pageRows as $row)
                        @php $tone = PressEfficiency::efficiencyTone($row['efficiency']); @endphp
                        <tr wire:key="row-{{ $row['press'] }}-{{ $row['die'] }}" class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-white/90 whitespace-nowrap sm:pl-6">{{ $row['press'] }}</td>
                            <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $row['die'] }}</td>
                            <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400">
                                @foreach($row['parts'] as $part)
                                    <span class="block whitespace-nowrap tabular-nums">{{ $part }}</span>
                                @endforeach
                            </td>
                            <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400">
                                @foreach($row['names'] as $name)
                                    <span class="block">{{ $name }}</span>
                                @endforeach
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                @if($row['spmStandard'] !== null)
                                    {{ number_format($row['spmStandard'], 1) }}
                                @else
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $toneBadge['none'] }}">Sin estándar</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                {{ $row['spmReal'] !== null ? number_format($row['spmReal'], 1) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-medium {{ $row['difference'] === null ? $toneText['none'] : ($row['difference'] < 0 ? $toneText['bad'] : $toneText['good']) }}">
                                {{ $row['difference'] !== null ? ($row['difference'] > 0 ? '+' : '') . number_format($row['difference'], 1) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                title="{{ $row['runs'] }} {{ $row['runs'] === 1 ? 'corrida' : 'corridas' }}">
                                {{ $duration($row['minutes']) }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 dark:text-gray-400"
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
                            <td colspan="11" class="px-5 py-14 text-center text-gray-500 dark:text-gray-400">
                                {{ $range?->isFuture ? 'El periodo seleccionado aún no inicia.' : 'Sin producción registrada en las estaciones seleccionadas para este periodo.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie compacto: semáforo de eficiencia (de menor a mayor) a la izquierda; paginación (5 troqueles por página) a la derecha --}}
        <div class="flex flex-col gap-2 border-t border-gray-100 dark:border-gray-800 px-5 py-2 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>&lt; {{ PressEfficiency::WARN_THRESHOLD }} %</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ PressEfficiency::WARN_THRESHOLD }} – {{ PressEfficiency::GOOD_THRESHOLD }} %</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>≥ {{ PressEfficiency::GOOD_THRESHOLD }} %</span>
            </div>

            @if($pagination->total > 0)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
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
                                <button wire:click="goToPage({{ $p }})" type="button" @if($p === $page) aria-current="page" @endif
                                    class="h-7 min-w-[1.75rem] px-1.5 rounded-md text-xs font-medium tabular-nums transition-colors {{ $p === $page
                                        ? 'bg-[#2563eb] text-white'
                                        : 'text-gray-700 hover:bg-[#2563eb]/10 hover:text-[#2563eb] dark:text-gray-400 dark:hover:text-[#60a5fa]' }}">
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
                <div class="flex items-center gap-2.5 border-b border-blue-100 bg-blue-50 px-5 py-2.5 sm:px-6 dark:border-blue-500/20 dark:bg-blue-500/10">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white text-[#2563eb] shadow-[0px_1px_2px_0px_rgba(16,24,40,0.05)] dark:bg-blue-500/20 dark:text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </span>
                    <h3 class="text-sm font-bold uppercase tracking-wide text-blue-900 dark:text-blue-100">{{ $title }}</h3>
                </div>
                <div class="relative px-4 pt-4 h-[380px] sm:px-5">
                    <canvas x-ref="{{ $key }}" aria-label="{{ $title }}" role="img"></canvas>
                    <div x-show="!data.{{ $key }}.length" x-cloak
                        class="absolute inset-0 flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                        {{ $emptyText }}
                    </div>
                </div>
                <p class="px-5 pb-4 pt-1 text-center text-xs text-gray-500 dark:text-gray-400 sm:px-6">{{ $axis }}</p>
            </div>
        @endforeach
    </div>
</div>

@script
<script>
    // Calendario del periodo: semana (cualquier día lleva a su semana) o mes (cuadrícula de meses)
    Alpine.data('peCalendar', () => ({
        open: false,
        mode: 'week',
        year: 0,
        month: 0,
        today: '',
        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],

        iso(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; },
        parse(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); },

        toggle() {
            this.open = !this.open;
            if (!this.open) return;
            this.mode = $wire.period;
            this.today = this.iso(new Date());
            const d = this.parse($wire.from || this.today);
            this.year = d.getFullYear();
            this.month = d.getMonth();
        },
        move(step) {
            if (this.mode === 'month') { this.year += step; return; }
            const d = new Date(this.year, this.month + step, 1);
            this.year = d.getFullYear();
            this.month = d.getMonth();
        },
        get title() { return this.mode === 'month' ? String(this.year) : `${this.monthNames[this.month]} ${this.year}`; },

        // 6 semanas de lunes a domingo, empezando en el lunes de la semana del día 1
        get weeks() {
            const first = new Date(this.year, this.month, 1);
            const start = new Date(this.year, this.month, 1 - ((first.getDay() + 6) % 7));
            return Array.from({ length: 6 }, (_, w) => Array.from({ length: 7 }, (_, i) => {
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + w * 7 + i);
                return { iso: this.iso(d), day: d.getDate(), inMonth: d.getMonth() === this.month };
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
                text: '#d4d4d8', muted: '#a1a1aa', grid: 'rgba(161,161,170,0.15)', goal: '#60a5fa',
                bad: { fill: 'rgba(239,68,68,0.3)', border: 'rgb(248,113,113)' },
                warn: { fill: 'rgba(245,158,11,0.3)', border: 'rgb(251,191,36)' },
                good: { fill: 'rgba(34,197,94,0.3)', border: 'rgb(74,222,128)' },
            }
            : {
                text: '#3f3f46', muted: '#71717a', grid: 'rgba(113,113,122,0.15)', goal: '#2563eb',
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

        return {
            data: @js($charts),

            init() {
                // Misma fuente que el resto de la vista (Figtree) en ejes, tooltips y etiquetas
                Chart.defaults.font.family = getComputedStyle(this.$el).fontFamily;

                // Refresco cada minuto con setInterval (como press-production): wire:poll se
                // frena ~95 % cuando la pestaña no está al frente y la vista parecía congelada.
                timer = setInterval(() => $wire.tick(), 60000);

                this.draw();

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
            },
        };
    });
</script>
@endscript
