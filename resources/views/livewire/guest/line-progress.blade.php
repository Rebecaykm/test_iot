<div wire:poll.30s class="flex flex-col min-h-0">
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden flex flex-col min-h-0">

        {{-- Encabezado: líneas, leyenda y navegación de semana --}}
        <div class="px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700/70 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between flex-shrink-0">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">

                {{-- Select múltiple de líneas (persistente: URL + navegador) --}}
                <div x-data="lineProgressFilter" @click.away="open = false" class="relative w-full sm:w-72">
                    <button @click="open = !open" type="button"
                        class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700/70 rounded-lg text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                        <span class="flex items-center gap-2 min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
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
                            <input x-model="search" type="text" placeholder="Buscar línea..."
                                class="w-full px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-700/70 rounded-md bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="max-h-72 overflow-y-auto py-1">
                            <template x-for="line in filtered" :key="line.id">
                                <label class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                    <input type="checkbox" :checked="isSelected(line.id)" @change="toggle(line.id)"
                                        class="rounded border-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                    <span class="w-1 h-4 rounded-full flex-shrink-0" :style="`background-color: ${line.color || '#71717a'}`"></span>
                                    <span class="text-zinc-700 dark:text-zinc-200 leading-tight" x-text="line.full_name"></span>
                                </label>
                            </template>
                            <p x-show="filtered.length === 0" class="text-center text-sm text-zinc-500 dark:text-zinc-400 py-4">Sin resultados</p>
                        </div>
                        <div x-show="selected.length > 0" style="display: none;" class="p-2 border-t border-zinc-200 dark:border-zinc-800">
                            <button @click="clear()" type="button"
                                class="w-full px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors">
                                Mostrar todas las líneas
                            </button>
                        </div>
                    </div>
                </div>
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

        {{-- Tabla: los días quedan fijos arriba; área y línea son renglones separadores --}}
        <div class="min-h-0 overflow-auto">
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

                            @foreach($line['workCenters'] as $wc)
                                @foreach($wc['shifts'] as $i => $row)
                                    <tr wire:key="wc-{{ $line['id'] }}-{{ $wc['name'] }}-{{ $row['shift'] }}" class="{{ $loop->last ? 'border-b border-zinc-200 dark:border-zinc-800' : '' }}">
                                        {{-- Work center: lleva a su vista de detalle --}}
                                        @if($i === 0)
                                            <td rowspan="{{ count($wc['shifts']) }}"
                                                class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-1.5 align-middle border-r border-zinc-200 dark:border-zinc-800">
                                                <a href="{{ route('guest.work-center-progress', $wc['name']) }}"
                                                    class="block truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100 hover:text-[#2563eb] dark:hover:text-[#60a5fa] hover:underline"
                                                    title="Ver detalle de {{ $wc['name'] }}">
                                                    {{ $wc['name'] }}
                                                </a>
                                                @if($wc['inProgress'])
                                                    <span class="mt-1 flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-[#2563eb] dark:text-[#60a5fa]">
                                                        <span class="relative flex h-2 w-2">
                                                            <span class="absolute inline-flex h-full w-full rounded-full bg-[#2563eb] dark:bg-[#3b82f6] opacity-75 animate-ping"></span>
                                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-[#2563eb] dark:bg-[#3b82f6]"></span>
                                                        </span>
                                                        En Proceso
                                                    </span>
                                                @endif
                                            </td>
                                        @endif

                                        {{-- Turno --}}
                                        <td class="sticky left-44 z-10 bg-white dark:bg-zinc-900 px-2 py-1.5 text-center align-middle border-r border-zinc-200 dark:border-zinc-800 {{ $i > 0 ? 'border-t border-zinc-100 dark:border-zinc-800/60' : '' }}">
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                                {{ $row['shift'] === 'D'
                                                    ? 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:ring-amber-400/20 dark:text-amber-300'
                                                    : 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20 dark:bg-indigo-400/10 dark:ring-indigo-400/20 dark:text-indigo-300' }}">
                                                {{ $row['shift_name'] }}
                                            </span>
                                        </td>

                                        {{-- Celdas por día: medidor semicircular (plan / real) --}}
                                        @foreach($days as $day)
                                            @php $c = $row['cells'][$day['date']] ?? null; @endphp
                                            <td class="p-0 align-middle border-l border-zinc-200 dark:border-zinc-800 {{ $i > 0 ? 'border-t border-t-zinc-100 dark:border-t-zinc-800/60' : '' }}">
                                                @if(!$c || ($c['plan'] === 0 && $c['real'] === 0 && !$c['inProgress']))
                                                    <div class="py-2 text-center text-zinc-300 dark:text-zinc-700 text-[11px]">–</div>
                                                @else
                                                    <div class="relative px-2 pt-1.5 pb-1 {{ $c['inProgress'] ? 'ring-2 ring-inset ring-[#2563eb] dark:ring-[#3b82f6]' : '' }}"
                                                        title="Plan {{ number_format($c['plan']) }} · Real {{ number_format($c['real']) }}{{ $c['inProgress'] ? ' · En Proceso' : '' }}">
                                                        @if($c['inProgress'])
                                                            <span class="absolute top-1 right-1 flex h-2 w-2">
                                                                <span class="absolute inline-flex h-full w-full rounded-full bg-[#2563eb] dark:bg-[#3b82f6] opacity-75 animate-ping"></span>
                                                                <span class="relative inline-flex h-2 w-2 rounded-full bg-[#2563eb] dark:bg-[#3b82f6]"></span>
                                                            </span>
                                                        @endif

                                                        {{-- Medidor semicircular: 100 % = máx(plan, real) --}}
                                                        @php $a = $c['arc']; @endphp
                                                        <svg viewBox="0 0 100 56" class="block w-full max-w-[128px] mx-auto" role="img"
                                                            aria-label="Plan {{ $c['plan'] }}, real {{ $c['real'] }}">
                                                            {{-- Fondo del arco --}}
                                                            <path d="M 3 50 A 47 47 0 0 1 97 50 L 87 50 A 37 37 0 0 0 13 50 Z" vector-effect="non-scaling-stroke"
                                                                class="fill-zinc-50 stroke-zinc-200 dark:fill-zinc-800/40 dark:stroke-zinc-700/70" />
                                                            {{-- Mismo estilo que las barras: contorno fuerte + relleno claro --}}
                                                            {{-- Plan (azul): lo que hay que cubrir --}}
                                                            @if($a['plan'])
                                                                <path d="{{ $a['plan'] }}" vector-effect="non-scaling-stroke"
                                                                    class="fill-[rgba(37,99,235,0.15)] stroke-[#2563eb] dark:fill-[rgba(59,130,246,0.16)] dark:stroke-[#3b82f6]" />
                                                            @endif
                                                            {{-- Real (verde): va cubriendo el plan --}}
                                                            @if($a['real'])
                                                                <path d="{{ $a['real'] }}" vector-effect="non-scaling-stroke"
                                                                    class="fill-[rgba(16,185,129,0.15)] stroke-[#059669] dark:fill-[rgba(16,185,129,0.22)]" />
                                                            @endif
                                                            {{-- Centro: real contra plan --}}
                                                            <text x="50" y="49" text-anchor="middle"
                                                                class="fill-zinc-800 dark:fill-zinc-100 font-bold tabular-nums"
                                                                font-size="{{ $c['percent'] === null ? 10 : 15 }}">
                                                                {{ $c['percent'] === null ? 'Sin plan' : $c['percent'] . '%' }}
                                                            </text>
                                                        </svg>

                                                        {{-- Cantidades a los lados (como en la vista de work center) --}}
                                                        <div class="mt-0.5 flex items-center justify-between gap-1 text-[11px] font-semibold tabular-nums whitespace-nowrap">
                                                            <span class="flex items-center gap-1 text-zinc-600 dark:text-zinc-300"><span class="w-2 h-2 rounded-[2px] bg-[#2563eb] dark:bg-[#3b82f6]"></span>P: {{ number_format($c['plan']) }}</span>
                                                            <span class="flex items-center gap-1 text-zinc-800 dark:text-zinc-100"><span class="w-2 h-2 rounded-[2px] bg-[#059669]"></span>R: {{ number_format($c['real']) }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach

                                        {{-- Cant. Sin Orden: acumulado de la semana del renglón; clic = detalle por número de parte --}}
                                        <td class="p-1 align-middle text-center border-l border-zinc-200 dark:border-zinc-800 {{ $i > 0 ? 'border-t border-t-zinc-100 dark:border-t-zinc-800/60' : '' }}">
                                            @if($row['noOrderTotal'] > 0)
                                                <button type="button" wire:click="showNoOrder({{ $wc['id'] }}, '{{ $row['shift'] }}')"
                                                    title="Ver números de parte sin orden"
                                                    class="w-full rounded-md border border-[#ea580c] bg-[rgba(234,88,12,0.10)] dark:bg-[rgba(234,88,12,0.18)] px-2 py-2 text-sm font-bold tabular-nums text-zinc-800 dark:text-zinc-100 hover:bg-[rgba(234,88,12,0.20)] dark:hover:bg-[rgba(234,88,12,0.28)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ea580c] transition-colors">
                                                    {{ number_format($row['noOrderTotal']) }}
                                                </button>
                                            @else
                                                <div class="py-2 text-zinc-300 dark:text-zinc-700 text-[11px]">–</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="{{ count($days) + 3 }}" class="px-4 py-16 text-center text-zinc-500 dark:text-zinc-400">
                                No hay producción ni plan en esta semana para las líneas seleccionadas.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        {{-- Pie: leyenda y última actualización --}}
        <div class="px-4 py-2 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400 flex-shrink-0">
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
        Alpine.data('lineProgressFilter', () => ({
            open: false,
            search: '',
            lines: @js($allLines),
            selected: $wire.entangle('selectedLines').live,
            storageKey: 'lineProgress.lines',

            init() {
                // Sin ?lines= en la URL: recuperar la selección guardada en este navegador
                const fromUrl = new URLSearchParams(window.location.search).has('lines');
                if (!fromUrl) {
                    try {
                        const stored = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                        const valid = stored.filter(id => this.lines.some(l => l.id === id));
                        if (valid.length) this.selected = valid;
                    } catch (e) { /* almacenamiento no disponible: se muestran todas */ }
                }

                this.$watch('selected', value => {
                    try { localStorage.setItem(this.storageKey, JSON.stringify(value || [])); } catch (e) {}
                });
            },

            get filtered() {
                const q = this.search.trim().toLowerCase();
                return q ? this.lines.filter(l => l.full_name.toLowerCase().includes(q)) : this.lines;
            },
            get label() {
                if (!this.selected.length) return 'Todas las líneas';
                if (this.selected.length === 1) {
                    return this.lines.find(l => l.id === this.selected[0])?.name ?? '1 línea';
                }
                return `${this.selected.length} líneas`;
            },
            isSelected(id) { return this.selected.includes(id); },
            toggle(id) {
                this.selected = this.isSelected(id)
                    ? this.selected.filter(x => x !== id)
                    : [...this.selected, id];
            },
            clear() { this.selected = []; },
        }));
    </script>
    @endscript
</div>
