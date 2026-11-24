@php
    use App\Livewire\Guest\LineProgress;

    $control = 'h-8 border border-zinc-300 dark:border-ta-gray-700/70 rounded-lg bg-white dark:bg-ta-gray-900 text-xs text-zinc-700 dark:text-ta-gray-200 hover:bg-zinc-50 dark:hover:bg-ta-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors';
    $panel = 'absolute left-0 mt-1 bg-white dark:bg-ta-gray-900 rounded-xl shadow-xl border border-zinc-200 dark:border-ta-gray-800 z-50 overflow-hidden';
    $loadingTargets = 'toggle,openAll,closeAll,prevWeek,nextWeek,currentWeek,goToDate,selectedLines,selectedWorkCenters,partSearch,clearFilters';

    // Cada nivel se ve distinto: las áreas son lo más grande. Clases escritas completas para que Tailwind las encuentre.
    $style = [
        0 => ['bg' => 'bg-zinc-100 dark:bg-ta-gray-800', 'pad' => 'pl-3', 'name' => 'text-base font-extrabold uppercase tracking-wide', 'pct' => 'text-2xl'],
        1 => ['bg' => 'bg-white dark:bg-ta-gray-900', 'pad' => 'pl-8', 'name' => 'text-sm font-bold', 'pct' => 'text-xl'],
        2 => ['bg' => 'bg-white dark:bg-ta-gray-900', 'pad' => 'pl-14', 'name' => 'text-sm font-semibold', 'pct' => 'text-lg'],
    ];
@endphp

<div wire:poll.30s.visible class="flex flex-col gap-3 min-h-0 flex-1">

    {{-- Barra superior: título, filtros y semana --}}
    <div class="relative z-20 flex-shrink-0 bg-white dark:bg-ta-gray-900 rounded-xl border border-zinc-200 dark:border-ta-gray-800 shadow-sm px-3 py-2 flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h1 class="text-sm font-bold uppercase tracking-wide text-zinc-800 dark:text-ta-gray-100">Avance de la planta</h1>
            <p class="text-xs text-zinc-500 dark:text-ta-gray-400">Toca una estación para ver sus números de parte</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-600 dark:text-ta-gray-300 xl:justify-end">
            <span wire:loading.delay wire:target="{{ $loadingTargets }}" class="text-zinc-500 dark:text-ta-gray-400">Cargando…</span>

            {{-- Mismos filtros que /: líneas (por área), estaciones (por línea) y número de parte --}}
            @include('livewire.guest.partials.lp-filters', ['filter' => ['ptLinesFilter', 'Buscar línea o estación...'], 'noClear' => true])
            @include('livewire.guest.partials.lp-filters', ['filter' => ['ptWcFilter', 'Buscar estación...'], 'noClear' => true])
            @include('livewire.guest.partials.lp-filters', ['filter' => null])

            <span class="hidden xl:block h-5 w-px bg-zinc-200 dark:bg-ta-gray-700" aria-hidden="true"></span>

            <button wire:click="openAll" type="button" class="{{ $control }} px-2.5 font-medium">Abrir todo</button>
            <button wire:click="closeAll" type="button" class="{{ $control }} px-2.5 font-medium">Cerrar todo</button>

            <span class="hidden xl:block h-5 w-px bg-zinc-200 dark:bg-ta-gray-700" aria-hidden="true"></span>

            {{-- Semana: ‹ 19 – 25 Oct ›; el calendario elige un día y lleva a su semana --}}
            @unless($isCurrentWeek)
                <button wire:click="currentWeek" type="button" class="{{ $control }} px-2.5 font-medium">Semana actual</button>
            @endunless
            <button wire:click="prevWeek" type="button" title="Semana anterior" aria-label="Semana anterior" class="{{ $control }} w-8 flex items-center justify-center text-zinc-500 dark:text-ta-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>
            <span class="min-w-[7rem] text-center text-sm font-semibold text-zinc-800 dark:text-ta-gray-100 whitespace-nowrap">{{ $weekLabel }}</span>
            <input type="date" value="{{ $date !== '' ? $date : now()->toDateString() }}" wire:change="goToDate($event.target.value)" title="Elegir fecha" aria-label="Elegir fecha" class="{{ $control }} px-2">
            <button wire:click="nextWeek" type="button" title="Semana siguiente" aria-label="Semana siguiente" class="{{ $control }} w-8 flex items-center justify-center text-zinc-500 dark:text-ta-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
        </div>
    </div>

    {{-- Árbol en tabla, igual que /: días divididos por turno arriba; nombre y % de la semana fijos a la izquierda --}}
    <div class="isolate min-h-0 bg-white dark:bg-ta-gray-900 rounded-xl border border-zinc-200 dark:border-ta-gray-800 shadow-sm flex flex-col">
        <div class="min-h-0 overflow-auto rounded-t-xl transition-opacity" wire:loading.delay.class="opacity-50" wire:target="{{ $loadingTargets }}">
            <table class="w-full min-w-[2000px] table-fixed border-collapse text-sm">
                <colgroup>
                    <col class="w-72">
                    <col class="w-32">
                    <col class="w-28">
                    <col class="w-28">
                    @foreach($columns as $col)
                        <col>
                    @endforeach
                </colgroup>
                <thead class="sticky top-0 z-20">
                    <tr>
                        <th rowspan="2" class="sticky left-0 z-30 bg-zinc-50 dark:bg-ta-gray-800 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-ta-gray-300 border-b border-r border-zinc-200 dark:border-ta-gray-700/70">Área · Línea · Estación</th>
                        <th rowspan="2" class="sticky left-72 z-30 bg-zinc-50 dark:bg-ta-gray-800 px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-ta-gray-300 border-b border-r border-zinc-200 dark:border-ta-gray-700/70" title="Lo producido contra lo que tocaba hasta ahora">Semana</th>
                        {{-- Acumulado de la semana sin número de orden, por estado --}}
                        <th colspan="2" class="bg-zinc-50 dark:bg-ta-gray-800 px-2 pt-1.5 pb-1 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-ta-gray-300 border-l border-r border-zinc-200 dark:border-ta-gray-700/70">Sin N° de orden</th>
                        @foreach($days as $day)
                            <th colspan="{{ count($shifts) }}" class="px-2 pt-1.5 pb-1 text-center border-l border-zinc-200 dark:border-ta-gray-700/70 {{ $day['highlight'] ? 'lp-hl-day' : 'bg-zinc-50 dark:bg-ta-gray-800' }}">
                                <span class="text-xs font-bold {{ $day['highlight'] ? 'text-zinc-900 dark:text-ta-gray-50' : 'text-zinc-700 dark:text-ta-gray-200' }}">{{ $day['name'] }}</span>
                                <span class="text-[11px] {{ $day['highlight'] ? 'font-semibold text-zinc-700 dark:text-ta-gray-200' : 'font-normal text-zinc-500 dark:text-ta-gray-400' }}">{{ $day['label'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-ta-gray-800 px-1 py-1.5 text-center text-[11px] font-semibold text-red-700 dark:text-red-400 border border-zinc-200 dark:border-ta-gray-700/70" title="Producción sin verificar: no cuenta como real contra el plan">No verificado</th>
                        <th class="bg-zinc-50 dark:bg-ta-gray-800 px-1 py-1.5 text-center text-[11px] font-semibold text-[#c2410c] dark:text-orange-400 border border-zinc-200 dark:border-ta-gray-700/70" title="Acumuladores cerrados, sin orden">No planeado</th>
                        @foreach($columns as $col)
                            {{-- Turno: ámbar = diurno, índigo = nocturno --}}
                            <th class="pp-shift-th {{ $col['abbr'] === 'D' ? 'pp-shift-D' : 'pp-shift-N' }}" title="{{ $col['title'] }}">{{ $col['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $r)
                        @php
                            $lv = $r['level'];
                            $s = $style[$lv];
                            $st = $r['stats'];
                            // Armados aquí: un @if entre los atributos de la etiqueta rompe el HTML (Livewire le mete marcadores)
                            $attrs = $lv < 2
                                ? 'wire:click="toggle(\'' . $r['key'] . '\')" wire:keydown.enter="toggle(\'' . $r['key'] . '\')" role="button" tabindex="0" aria-expanded="' . ($r['open'] ? 'true' : 'false') . '"'
                                : '';
                        @endphp
                        <tr wire:key="r-{{ $r['key'] }}" {!! $attrs !!} class="group border-b border-zinc-200 dark:border-ta-gray-800 {{ $lv < 2 ? 'cursor-pointer' : '' }}">
                            {{-- Nombre --}}
                            <td class="sticky left-0 z-10 border-r border-zinc-200 dark:border-ta-gray-800 py-2 pr-2 group-hover:bg-blue-50 dark:group-hover:bg-ta-gray-800 {{ $s['bg'] }} {{ $s['pad'] }}">
                                <div class="flex items-center gap-2">
                                    @if($lv < 2)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-zinc-400 transition-transform {{ $r['open'] ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                    @endif
                                    @if($r['color'])<span class="w-1 h-4 rounded-full shrink-0" style="background-color: {{ $r['color'] }}" aria-hidden="true"></span>@endif

                                    @if($lv === 2)
                                        {{-- Estación: abre sus números de parte en otra ventana --}}
                                        <a href="{{ $r['href'] }}" target="_blank" rel="noopener" title="Abrir {{ $r['name'] }} en otra ventana{{ $r['noOrder'] > 0 ? ' · ' . number_format($r['noOrder']) . ' piezas sin número de orden' : '' }}"
                                            class="group/link inline-flex min-w-0 items-center gap-1 rounded text-zinc-800 dark:text-ta-gray-100 hover:text-[#2563eb] dark:hover:text-[#60a5fa] hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {{ $s['name'] }}">
                                            <span class="truncate">{{ $r['name'] }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 opacity-40 group-hover/link:opacity-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                        </a>
                                    @else
                                        <span class="truncate text-zinc-800 dark:text-ta-gray-100 {{ $s['name'] }}" title="{{ $r['name'] }}">{{ $r['name'] }}</span>
                                    @endif

                                    {{-- Un punto: verde = produciendo ahora, rojo = detenido (en áreas y líneas, verde si alguna estación produce) --}}
                                    @if($lv < 2)
                                        @if($r['live'])<span class="lp-dot lp-dot-green !relative !top-0 !right-0 ml-auto shrink-0" title="{{ $r['live'] }} de {{ $r['total'] }} estaciones produciendo"></span>@endif
                                    @elseif($r['state'] === 'live')
                                        <span class="lp-dot lp-dot-green !relative !top-0 !right-0 ml-auto shrink-0" title="Produciendo ahora"></span>
                                    @elseif($r['state'] === 'stop')
                                        <span class="lp-dot-stop ml-auto" title="Detenido"></span>
                                    @endif
                                </div>
                            </td>

                            {{-- % de la semana, con su barra --}}
                            <td class="sticky left-72 z-10 border-r border-zinc-200 dark:border-ta-gray-800 px-2 group-hover:bg-blue-50 dark:group-hover:bg-ta-gray-800 {{ $s['bg'] }}" title="{{ $st['title'] }}">
                                <div class="text-center font-extrabold tabular-nums leading-none {{ $s['pct'] }} {{ $st['tone'] }}">{{ $st['pct'] !== null ? $st['pct'] . '%' : '—' }}</div>
                                <div class="mt-1.5 h-1.5 rounded-full bg-zinc-200 dark:bg-ta-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full {{ $st['bar'] }}" style="width: {{ $st['fill'] }}%"></div>
                                </div>
                                {{-- Acumulado de la semana: real / plan --}}
                                <div class="mt-1 text-center text-[11px] font-semibold tabular-nums whitespace-nowrap text-zinc-600 dark:text-ta-gray-300">{{ $st['text'] }}</div>
                            </td>

                            {{-- Acumulado sin número de orden de la semana: No verificado (rojo) y No planeado (naranja) --}}
                            @foreach([['No verificado', 'text-red-700 dark:text-red-400'], ['No planeado', 'text-[#c2410c] dark:text-orange-400']] as [$status, $color])
                                @php $qty = $r['no'][$status] ?? 0; @endphp
                                <td class="px-1 text-center border-r border-zinc-200 dark:border-ta-gray-800 {{ $qty > 0 ? '' : 'pp-x' }}" @if($qty > 0) title="{{ $status }}: {{ number_format($qty) }} pzs" @endif>
                                    @if($qty > 0)<span class="text-sm font-bold tabular-nums {{ $color }}">{{ number_format($qty) }}</span>@endif
                                </td>
                            @endforeach

                            {{-- Turnos: HTML armado en el componente (barra plan / real y % de cada turno) --}}
                            {!! $r['cells'] !!}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 4 }}" class="px-4 py-16 text-center text-zinc-500 dark:text-ta-gray-400">
                                @if($searching)
                                    Ningún número de parte coincide con «{{ trim($partSearch) }}» en esta semana.
                                    <button type="button" wire:click="$set('partSearch', '')" class="ml-1 font-semibold text-[#2563eb] dark:text-[#60a5fa] hover:underline">Limpiar búsqueda</button>
                                @else
                                    No hay producción ni plan en esta semana.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie: leyenda (igual que /) --}}
        <div class="px-4 py-2 border-t border-zinc-200 dark:border-ta-gray-800 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-zinc-600 dark:text-ta-gray-300 flex-shrink-0">
            <span class="flex items-center gap-1.5"><span class="w-4 h-2 rounded-sm border border-zinc-400 bg-[rgba(161,161,170,0.15)] dark:border-ta-gray-500 dark:bg-[rgba(102,112,133,0.20)]"></span>Plan</span>
            <span class="flex items-center gap-1.5"><span class="w-4 h-2 rounded-sm border border-[#059669] bg-[rgba(16,185,129,0.15)] dark:bg-[rgba(16,185,129,0.22)]"></span>Real</span>
            <span class="text-zinc-300 dark:text-ta-gray-700" aria-hidden="true">|</span>
            <span class="flex items-center gap-3">
                <span class="font-semibold lp-t-bad">≤ {{ LineProgress::WARN }}%</span>
                <span class="font-semibold lp-t-warn">{{ LineProgress::WARN + 1 }}–{{ LineProgress::GOOD - 1 }}%</span>
                <span class="font-semibold lp-t-good">≥ {{ LineProgress::GOOD }}%</span>
            </span>
            <span class="text-zinc-300 dark:text-ta-gray-700" aria-hidden="true">|</span>
            <span class="flex items-center gap-1.5"><span class="lp-dot lp-dot-green !relative !top-0 !right-0"></span>Produciendo</span>
            <span class="flex items-center gap-1.5"><span class="lp-dot-stop"></span>Detenido</span>
            <span class="ml-auto flex items-center gap-1.5 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-[#059669] animate-pulse"></span>
                Última actualización <span class="font-semibold tabular-nums text-zinc-800 dark:text-ta-gray-100">{{ $lastUpdated }}</span>
            </span>
        </div>
    </div>

    @script
    <script>
        const catalog = window.lineProgressCatalog || { lines: {}, workCenters: [] };
        // ↑ Debe ser la primera línea: Alpine solo ejecuta este script como bloque si empieza con const/let

        const byName = (a, b) => (a.group + ' ' + a.name).localeCompare(b.group + ' ' + b.name, 'es', { numeric: true, sensitivity: 'base' });

        // Todas las líneas, agrupadas por área; se puede buscar por el nombre de una de sus estaciones
        const lineOptions = Object.values(catalog.lines).map(l => {
            const stations = catalog.workCenters.filter(w => w.line_id === l.id).map(w => w.name);
            return { id: l.id, name: l.name, group: l.area_name, sub: '', stations, search: [l.name, l.area_name, ...stations].join(' ').toLowerCase() };
        }).sort(byName);

        // Todas las estaciones, agrupadas por línea
        const wcOptions = catalog.workCenters.map(w => ({
            id: w.id, name: w.name, group: w.line_name, sub: '', search: (w.name + ' ' + w.line_name).toLowerCase(),
        })).sort(byName);

        const inUrl = (param) => [...new URLSearchParams(window.location.search).keys()]
            .some(k => k === param || k.startsWith(param + '['));

        // Select múltiple persistente (URL + navegador). Llaves propias: no se mezclan con las de /
        const multiSelect = (cfg) => ({
            open: false,
            search: '',
            selected: $wire.entangle(cfg.model).live,
            clearText: cfg.clearText,
            listMaxHeight: 288,

            toggleOpen() {
                this.open = !this.open;
                if (this.open) {
                    const bottom = this.$refs.trigger.getBoundingClientRect().bottom;
                    this.listMaxHeight = Math.max(160, Math.min(448, window.innerHeight - bottom - 110));
                }
            },

            init() {
                if (!inUrl(cfg.param)) {
                    try {
                        const stored = JSON.parse(localStorage.getItem(cfg.storageKey) || '[]');
                        const valid = stored.filter(id => cfg.options.some(o => o.id === id));
                        if (valid.length) this.selected = valid;
                    } catch (e) { /* almacenamiento no disponible: se muestra todo */ }
                }

                this.$watch('selected', value => {
                    try { localStorage.setItem(cfg.storageKey, JSON.stringify(value || [])); } catch (e) {}
                });
            },

            get filtered() {
                const q = this.search.trim().toLowerCase();
                return q ? cfg.options.filter(o => o.search.includes(q)) : cfg.options;
            },
            get groups() {
                const groups = [];
                for (const o of this.filtered) {
                    let g = groups.find(x => x.name === o.group);
                    if (!g) groups.push(g = { name: o.group, options: [] });
                    g.options.push(o);
                }
                return groups;
            },
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
            sub(o) {
                const q = this.search.trim().toLowerCase();
                const hits = q && o.stations ? o.stations.filter(st => st.toLowerCase().includes(q)) : [];
                return hits.length ? hits.join(', ') : o.sub;
            },
            get label() {
                const picked = cfg.options.filter(o => this.selected.includes(o.id));
                if (!picked.length) return cfg.allText;
                if (picked.length === 1) return picked[0].name;
                const group = picked[0].group;
                if (picked.every(o => o.group === group) && cfg.options.filter(o => o.group === group).length === picked.length) return group;
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

        Alpine.data('ptLinesFilter', () => multiSelect({
            model: 'selectedLines', param: 'lines', storageKey: 'plantProgress.lines',
            allText: 'Todas las líneas', units: 'líneas', clearText: 'Mostrar todas las líneas', options: lineOptions,
        }));

        Alpine.data('ptWcFilter', () => multiSelect({
            model: 'selectedWorkCenters', param: 'wcs', storageKey: 'plantProgress.wcs',
            allText: 'Todas las estaciones', units: 'estaciones', clearText: 'Mostrar todas las estaciones', options: wcOptions,
        }));
    </script>
    @endscript
</div>
