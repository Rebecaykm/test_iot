@php
    use App\Livewire\Guest\PressEfficiency;

    $summary = $report['summary'];

    $toneText = [
        'good' => 'text-emerald-600 dark:text-emerald-400',
        'warn' => 'text-amber-600 dark:text-amber-400',
        'bad' => 'text-red-600 dark:text-red-400',
        'none' => 'text-zinc-400 dark:text-zinc-500',
    ];
    $toneBadge = [
        'good' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/30',
        'warn' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30',
        'bad' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/30',
        'none' => 'bg-zinc-50 text-zinc-500 ring-zinc-500/20 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-600/40',
    ];

    $duration = function (float $minutes): string {
        $total = (int) round($minutes);
        $hours = intdiv($total, 60);
        $mins = $total % 60;
        return $hours > 0 ? "{$hours} h " . str_pad($mins, 2, '0', STR_PAD_LEFT) . ' min' : "{$mins} min";
    };

    $summaryTone = PressEfficiency::efficiencyTone($summary['efficiency'] ?? null);
@endphp

<div class="flex flex-col gap-3 sm:gap-4">

    {{-- Barra superior: estaciones, fecha y turno a la izquierda; estado de actualización a la derecha --}}
    @php
        $control = 'h-8 border border-zinc-300 dark:border-zinc-700/70 rounded-lg bg-white dark:bg-zinc-900 text-xs text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors';
        $panel = 'absolute left-0 mt-1 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-800 z-50 overflow-hidden';
        $chevron = '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500 transition-transform" :class="{ \'rotate-180\': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>';

        $selectedStations = $stations->whereIn('id', $selectedWorkCenters);
        $stationsLabel = match (true) {
            $selectedStations->isEmpty() => 'Todas las estaciones',
            $selectedStations->count() === 1 => $selectedStations->first()->name,
            default => $selectedStations->count() . ' estaciones',
        };
        $selectedShift = $shifts->firstWhere('id', $shiftId);
        $shiftHours = fn ($shift) => substr($shift->start_time, 0, 5) . ' – ' . substr($shift->end_time, 0, 5);
    @endphp
    <div class="relative z-20 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm px-3 py-2 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-1.5">
            {{-- Multi select de estaciones del área de Estampado (vacío = todas) --}}
            <div x-data="{ open: false, search: '' }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative w-full sm:w-48">
                <button @click="open = !open" type="button" class="{{ $control }} w-full flex items-center justify-between gap-1.5 px-2.5">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                        </svg>
                        <span class="truncate font-medium">{{ $stationsLabel }}</span>
                    </span>
                    <span class="flex items-center gap-1 flex-shrink-0">
                        @if($selectedStations->isNotEmpty())
                            <span class="inline-flex items-center justify-center min-w-[1rem] h-4 px-1 text-[10px] font-bold bg-[#2563eb] dark:bg-[#3b82f6] text-white rounded-full">{{ $selectedStations->count() }}</span>
                        @endif
                        {!! $chevron !!}
                    </span>
                </button>

                <div x-show="open" x-transition.opacity.duration.100ms style="display: none;" class="{{ $panel }} w-full sm:w-64">
                    <div class="p-2 border-b border-zinc-200 dark:border-zinc-800">
                        <input x-model="search" type="text" placeholder="Buscar estación..."
                            class="w-full h-7 px-2.5 py-0 text-xs border border-zinc-300 dark:border-zinc-700/70 rounded-md bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="max-h-64 overflow-y-auto py-1">
                        @foreach($stations->groupBy('line') as $line => $lineStations)
                            <div x-show="!search || @js(\Illuminate\Support\Str::lower($line . ' ' . $lineStations->pluck('name')->implode(' ') . ' ' . $lineStations->pluck('number')->implode(' '))).includes(search.toLowerCase())"
                                class="px-3 pt-2 pb-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ $line }}
                            </div>
                            @foreach($lineStations as $station)
                                <label wire:key="station-{{ $station->id }}"
                                    x-show="!search || @js(\Illuminate\Support\Str::lower($line . ' ' . $station->name . ' ' . $station->number)).includes(search.toLowerCase())"
                                    class="flex items-center gap-2 px-3 py-1.5 text-xs cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <input type="checkbox" wire:model.live="selectedWorkCenters" value="{{ $station->id }}"
                                        class="h-3.5 w-3.5 rounded border-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                    <span class="text-zinc-700 dark:text-zinc-200">{{ $station->name }}</span>
                                    <span class="ml-auto text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">{{ $station->number }}</span>
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                    @if($selectedStations->isNotEmpty())
                        <div class="p-1.5 border-t border-zinc-200 dark:border-zinc-800">
                            <button wire:click="clearWorkCenters" type="button"
                                class="w-full px-3 py-1 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors">
                                Limpiar selección (ver todas)
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Fecha (día en que inicia el turno) y turno --}}
            <div class="flex items-center gap-1.5">
                <button wire:click="prevShift" type="button" title="Turno anterior" class="{{ $control }} w-8 flex items-center justify-center text-zinc-500 dark:text-zinc-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>

                <input type="date" wire:model.live="date" max="{{ now()->toDateString() }}"
                    class="{{ $control }} w-32 px-2 py-0 font-medium focus:border-blue-500 dark:[color-scheme:dark]">

                {{-- Turno: mismo diseño que el multi select --}}
                <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                    <button @click="open = !open" type="button" class="{{ $control }} w-36 flex items-center justify-between gap-1.5 px-2.5">
                        <span class="flex items-center gap-1.5 min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="truncate font-medium">{{ $selectedShift?->name ?? 'Turno' }}</span>
                        </span>
                        {!! $chevron !!}
                    </button>

                    <div x-show="open" x-transition.opacity.duration.100ms style="display: none;" class="{{ $panel }} w-48 py-1">
                        @foreach($shifts as $shift)
                            <button wire:key="shift-{{ $shift->id }}" type="button"
                                wire:click="$set('shiftId', {{ $shift->id }})" @click="open = false"
                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                <span class="w-3.5 flex-shrink-0 text-[#2563eb] dark:text-[#60a5fa]">
                                    @if($shift->id === $shiftId)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @endif
                                </span>
                                <span class="{{ $shift->id === $shiftId ? 'font-semibold text-zinc-900 dark:text-zinc-50' : 'text-zinc-700 dark:text-zinc-200' }}">{{ $shift->name }}</span>
                                <span class="ml-auto text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">{{ $shiftHours($shift) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <button wire:click="nextShift" type="button" title="Turno siguiente" @disabled(!$canGoNext)
                    class="{{ $control }} w-8 flex items-center justify-center text-zinc-500 dark:text-zinc-300 disabled:opacity-40 disabled:pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>

            @unless($range?->isLive)
                <button wire:click="currentShift" type="button" class="{{ $control }} px-2.5 font-medium text-zinc-600 dark:text-zinc-300">
                    Turno actual
                </button>
            @endunless

            {{-- Limpiar filtros: todas las estaciones y el turno en curso --}}
            @if($selectedStations->isNotEmpty() || !$range?->isLive)
                <button wire:click="clearFilters" type="button" title="Limpiar filtros"
                    class="h-8 inline-flex items-center gap-1 rounded-lg px-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    Limpiar filtros
                </button>
            @endif
        </div>

        <div class="flex items-center gap-2 text-xs md:justify-end text-zinc-600 dark:text-zinc-300"
            title="{{ $range?->isLive ? 'En vivo: se actualiza cada minuto' : 'Turno cerrado' }}">
            <span wire:loading.delay wire:target="date,shiftId,prevShift,nextShift,currentShift,goToPage,selectedWorkCenters,clearWorkCenters,clearFilters"
                class="text-zinc-500 dark:text-zinc-400">Cargando…</span>
            <span class="relative flex h-2 w-2">
                @if($range?->isLive)
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                @else
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                @endif
            </span>
            <span>Actualizado: <span class="font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">{{ \Carbon\Carbon::parse($report['generatedAt'])->format('d/m/Y H:i:s') }}</span></span>
        </div>
    </div>

    {{-- Indicadores del turno --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Eficiencia del turno</p>
            <p class="mt-1 text-3xl font-bold tabular-nums {{ $toneText[$summaryTone] }}">
                {{ isset($summary['efficiency']) ? number_format($summary['efficiency'], 1) . ' %' : '—' }}
            </p>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Troqueles con SPM estándar</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Piezas producidas</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-zinc-800 dark:text-zinc-100">{{ number_format($summary['produced'] ?? 0) }}</p>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                Esperadas (con estándar): {{ number_format($summary['expected'] ?? 0) }}
            </p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Troqueles producidos</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-zinc-800 dark:text-zinc-100">{{ $summary['dies'] ?? 0 }}</p>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $summary['belowStandard'] ?? 0 }} debajo del SPM estándar
            </p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Sin SPM estándar</p>
            <p class="mt-1 text-3xl font-bold tabular-nums {{ ($summary['withoutStandard'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-800 dark:text-zinc-100' }}">
                {{ $summary['withoutStandard'] ?? 0 }}
            </p>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Troqueles sin production_rate</p>
        </div>
    </div>

    {{-- Tabla por troquel --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] border-collapse text-sm">
                <thead>
                    <tr class="bg-[#1e3a5f] dark:bg-zinc-800 text-white dark:text-zinc-100 text-xs uppercase tracking-wide">
                        <th class="px-4 py-2.5 text-left font-semibold">Prensa</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Troquel (MDI)</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Número de parte</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Descripción</th>
                        <th class="px-3 py-2.5 text-right font-semibold">SPM estándar</th>
                        <th class="px-3 py-2.5 text-right font-semibold">SPM real</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Diferencia SPM</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Tiempo trabajado</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Piezas esperadas</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Piezas producidas</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Eficiencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($pageRows as $row)
                        @php $tone = PressEfficiency::efficiencyTone($row['efficiency']); @endphp
                        <tr wire:key="row-{{ $row['press'] }}-{{ $row['die'] }}" class="odd:bg-white even:bg-zinc-50/70 dark:odd:bg-zinc-900 dark:even:bg-zinc-800/40 hover:bg-blue-50/60 dark:hover:bg-zinc-800 transition-colors">
                            <td class="px-4 py-2.5 font-semibold text-zinc-800 dark:text-zinc-100 whitespace-nowrap">{{ $row['press'] }}</td>
                            <td class="px-3 py-2.5 font-medium text-zinc-700 dark:text-zinc-200 whitespace-nowrap">{{ $row['die'] }}</td>
                            <td class="px-3 py-2.5 text-zinc-700 dark:text-zinc-200">
                                @foreach($row['parts'] as $part)
                                    <span class="block whitespace-nowrap tabular-nums">{{ $part }}</span>
                                @endforeach
                            </td>
                            <td class="px-3 py-2.5 text-zinc-600 dark:text-zinc-300">
                                @foreach($row['names'] as $name)
                                    <span class="block">{{ $name }}</span>
                                @endforeach
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-zinc-700 dark:text-zinc-200">
                                @if($row['spmStandard'] !== null)
                                    {{ number_format($row['spmStandard'], 1) }}
                                @else
                                    <span class="inline-flex rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $toneBadge['none'] }}">Sin estándar</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-zinc-700 dark:text-zinc-200">
                                {{ $row['spmReal'] !== null ? number_format($row['spmReal'], 1) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-semibold {{ $row['difference'] === null ? $toneText['none'] : ($row['difference'] < 0 ? $toneText['bad'] : $toneText['good']) }}">
                                {{ $row['difference'] !== null ? ($row['difference'] > 0 ? '+' : '') . number_format($row['difference'], 1) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-zinc-700 dark:text-zinc-200 whitespace-nowrap"
                                title="{{ $row['runs'] }} {{ $row['runs'] === 1 ? 'corrida' : 'corridas' }}">
                                {{ $duration($row['minutes']) }}
                                @if($row['runs'] > 1)
                                    <span class="block text-[11px] text-zinc-500 dark:text-zinc-400">{{ $row['runs'] }} corridas</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-zinc-700 dark:text-zinc-200"
                                @if($row['piecesPerShot'] > 1) title="{{ $row['piecesPerShot'] }} piezas por golpe" @endif>
                                {{ $row['expected'] !== null ? number_format($row['expected']) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-medium text-zinc-800 dark:text-zinc-100">{{ number_format($row['produced']) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="inline-flex min-w-[4.5rem] justify-end rounded-md px-2 py-0.5 text-sm font-bold tabular-nums ring-1 ring-inset {{ $toneBadge[$tone] }}">
                                    {{ $row['efficiency'] !== null ? number_format($row['efficiency'], 1) . ' %' : '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ $range?->isFuture ? 'El turno seleccionado aún no inicia.' : 'Sin producción registrada en las estaciones seleccionadas para este turno.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación (5 troqueles por página) --}}
        @if($pagination->total > 0)
            <div class="px-4 py-2.5 border-t border-zinc-200 dark:border-zinc-800 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    Mostrando <span class="font-semibold tabular-nums">{{ $pagination->from }}</span>–<span class="font-semibold tabular-nums">{{ $pagination->to }}</span>
                    de <span class="font-semibold tabular-nums">{{ $pagination->total }}</span> troqueles
                </p>

                @if($pagination->pages > 1)
                    <nav class="flex items-center gap-1" aria-label="Paginación">
                        <button wire:click="goToPage({{ $page - 1 }})" type="button" title="Página anterior" @disabled($page <= 1)
                            class="p-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors disabled:opacity-40 disabled:pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>

                        @for($p = 1; $p <= $pagination->pages; $p++)
                            <button wire:click="goToPage({{ $p }})" type="button" @if($p === $page) aria-current="page" @endif
                                class="min-w-[2rem] h-8 px-2 rounded-lg text-sm font-semibold tabular-nums transition-colors {{ $p === $page
                                    ? 'bg-[#1e3a5f] text-white dark:bg-blue-600'
                                    : 'border border-zinc-300 dark:border-zinc-700/70 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                {{ $p }}
                            </button>
                        @endfor

                        <button wire:click="goToPage({{ $page + 1 }})" type="button" title="Página siguiente" @disabled($page >= $pagination->pages)
                            class="p-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors disabled:opacity-40 disabled:pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </nav>
                @endif
            </div>
        @endif

        {{-- Semáforo de eficiencia --}}
        <div class="px-4 py-2 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-end gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>≥ {{ PressEfficiency::GOOD_THRESHOLD }} %</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>{{ PressEfficiency::WARN_THRESHOLD }} – {{ PressEfficiency::GOOD_THRESHOLD }} %</span>
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>&lt; {{ PressEfficiency::WARN_THRESHOLD }} %</span>
        </div>
    </div>

    {{-- Gráficas Top 10 --}}
    <div x-data="pressEfficiencyCharts" wire:ignore class="grid grid-cols-1 xl:grid-cols-2 gap-3 sm:gap-4">
        @foreach([
            ['deviation', 'Top 10 · Troqueles con mayor desviación respecto al SPM estándar', 'Diferencia SPM (golpes/min)', 'Ningún troquel por debajo del SPM estándar.'],
            ['efficiency', 'Top 10 · Troqueles con menor eficiencia', 'Eficiencia (%)', 'Sin troqueles con SPM estándar en este turno.'],
        ] as [$key, $title, $axis, $emptyText])
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="bg-[#1e3a5f] dark:bg-zinc-800 px-4 py-2.5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-white dark:text-zinc-100">{{ $title }}</h2>
                </div>
                <div class="relative px-3 py-3 h-[380px]">
                    <canvas x-ref="{{ $key }}" aria-label="{{ $title }}" role="img"></canvas>
                    <div x-show="!data.{{ $key }}.length" x-cloak
                        class="absolute inset-0 flex items-center justify-center text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $emptyText }}
                    </div>
                </div>
                <p class="px-4 pb-3 -mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $axis }}</p>
            </div>
        @endforeach
    </div>
</div>

@script
<script>
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
