<div class="w-full h-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl border-2 border-blue-500/50 dark:border-blue-400/50 shadow-sm overflow-hidden flex flex-col">

        {{-- Encabezado: estación a la izquierda; filtros y menú en la esquina derecha --}}
        <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-600 flex-shrink-0">
            <div class="flex items-center justify-between gap-2">
                <h1 class="text-xl font-bold text-gray-800 dark:text-white flex items-center">
                    <span class="w-2.5 h-2.5 bg-blue-500 rounded-full inline-block mr-2.5"></span>
                    {{ $workCenter }}
                </h1>
                <div class="flex items-center gap-2">
                    {{-- Calendario (date picker) --}}
                    <div x-data="pressDatePicker(@js($selectedDate))" @click.away="open = false" class="relative">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:border-blue-400 dark:hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <span x-text="label" class="text-gray-700 dark:text-gray-200 font-medium capitalize"></span>
                        </button>

                        <div x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-1.5 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 p-3"
                            style="display: none;">
                            {{-- Navegación de mes --}}
                            <div class="flex items-center justify-between mb-2">
                                <button type="button" @click="prevMonth()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                </button>
                                <span x-text="monthLabel" class="text-sm font-semibold text-gray-700 dark:text-gray-200 capitalize"></span>
                                <button type="button" @click="nextMonth()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                </button>
                            </div>
                            {{-- Días de la semana --}}
                            <div class="grid grid-cols-7 gap-1 mb-1 text-center text-xs font-medium text-gray-400 dark:text-gray-500">
                                <template x-for="d in ['Do','Lu','Ma','Mi','Ju','Vi','Sa']" :key="d"><span x-text="d"></span></template>
                            </div>
                            {{-- Días del mes --}}
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(d, i) in days" :key="i">
                                    <button type="button" x-text="d" @click="pick(d)" :disabled="!d || isFuture(d)"
                                        :class="{
                                            'invisible': !d,
                                            'bg-blue-600 text-white font-semibold': isSelected(d),
                                            'text-gray-300 dark:text-gray-600 cursor-not-allowed': d && isFuture(d),
                                            'ring-1 ring-blue-400 text-gray-700 dark:text-gray-200': isToday(d) && !isSelected(d),
                                            'hover:bg-blue-50 dark:hover:bg-blue-900/30 text-gray-700 dark:text-gray-200': d && !isFuture(d) && !isSelected(d) && !isToday(d)
                                        }"
                                        class="h-8 w-8 rounded-lg text-sm flex items-center justify-center transition-colors"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Select de turnos --}}
                    <select wire:model.live="selectedShiftId"
                        class="pl-2.5 pr-8 py-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors cursor-pointer">
                        @foreach($shiftOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>

                    {{-- Menú de tres puntos junto a los filtros --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                            class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                            style="display: none;">

                            <a href="{{ url('/') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                </svg>
                                <span>Inicio</span>
                            </a>

                            @php
                                $workCenterModel = \App\Models\WorkCenter::where('name', $workCenter)->first();
                                $workCenterId = $workCenterModel ? $workCenterModel->id : null;
                            @endphp

                            @if($workCenterId)
                                <a href="{{ route('guest.production-records', $workCenterId) }}"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Detalles de Producción</span>
                                </a>
                            @endif

                            <a href="{{ route('press-production', $workCenter) }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                                </svg>
                                <span>Gráfica de Producción</span>
                            </a>

                            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/home') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Panel Administrativo</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Iniciar Sesión</span>
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart --}}
        <div x-data="pressTimelineChart" wire:ignore class="px-4 py-4 sm:px-6 flex-1">
            <div class="relative" :style="`height: ${chartHeight}px`">
                <canvas id="{{ $chartId }}"></canvas>
            </div>
            <p x-show="isEmpty" x-cloak
               class="text-center py-8 text-gray-500 dark:text-gray-400">
                No hay producción registrada en este turno.
            </p>
        </div>

        {{-- Pie: última actualización / histórico --}}
        <div class="px-6 py-2.5 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">
            <span>Golpes por MDI</span>

            <span>
                @if($isLive)
                    Última actualización {{ $lastUpdated }}
                @else
                    Histórico
                @endif
            </span>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @script
    <script>
        const pad = (n) => String(n).padStart(2, '0');

        // ---- Calendario (date picker) ----
        Alpine.data('pressDatePicker', (initial) => ({
            open: false,
            selected: initial || '',
            viewMonth: 0,
            viewYear: 0,
            days: [],
            months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            init() {
                const d = this.selected ? new Date(this.selected + 'T00:00:00') : new Date();
                this.viewMonth = d.getMonth();
                this.viewYear = d.getFullYear();
                this.build();
            },
            get label() {
                if (!this.selected) return 'Fecha';
                return new Date(this.selected + 'T00:00:00')
                    .toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
            },
            get monthLabel() { return this.months[this.viewMonth] + ' ' + this.viewYear; },
            build() {
                const startDay = new Date(this.viewYear, this.viewMonth, 1).getDay();
                const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                const cells = [];
                for (let i = 0; i < startDay; i++) cells.push(null);
                for (let d = 1; d <= daysInMonth; d++) cells.push(d);
                this.days = cells;
            },
            prevMonth() { this.viewMonth === 0 ? (this.viewMonth = 11, this.viewYear--) : this.viewMonth--; this.build(); },
            nextMonth() { this.viewMonth === 11 ? (this.viewMonth = 0, this.viewYear++) : this.viewMonth++; this.build(); },
            iso(d) { return `${this.viewYear}-${pad(this.viewMonth + 1)}-${pad(d)}`; },
            todayIso() { const n = new Date(); return `${n.getFullYear()}-${pad(n.getMonth() + 1)}-${pad(n.getDate())}`; },
            isFuture(d) { return d && this.iso(d) > this.todayIso(); },
            isSelected(d) { return !!d && this.iso(d) === this.selected; },
            isToday(d) { return !!d && this.iso(d) === this.todayIso(); },
            pick(d) {
                if (!d || this.isFuture(d)) return;
                this.selected = this.iso(d);
                this.open = false;
                $wire.set('selectedDate', this.selected);
            },
        }));

        Alpine.data('pressTimelineChart', () => {
            let chart = null;
            let refreshInterval = null;
            let resizeHandler = null;
            // Solo se auto-refresca cuando se ve el turno actual en vivo
            let live = @json($isLive);

            // Estado de los datos actuales
            let state = {
                rowStatuses: @json($rowStatuses),
                planProgress: @json($planProgress),
                planTotals: @json($planTotals),
                planTimeLabels: @json($planTimeLabels),
                quantities: @json($quantities),
                timeLabels: @json($timeLabels),
                barStatuses: @json($barStatuses),
                realTotals: @json($realTotals),
                shiftStartIso: @json($shiftStartIso),
                labelCount: @json(count($labels)),
            };

            const isDark = () => document.documentElement.classList.contains('dark');

            // Colores por cumplimiento vs plan total del MDI
            // (>= 90% verde, 10-90% ámbar, < 10% rojo, sin plan neutro)
            // rowFill: tinte para pintar solo la etiqueta del MDI (eje Y) a modo de recuadro
            const statusColors = () => isDark()
                ? {
                    good: { fill: 'rgba(52,211,153,0.20)', border: '#34d399', text: '#34d399', rowFill: 'rgba(52,211,153,0.18)' },
                    mid:  { fill: 'rgba(251,191,36,0.20)', border: '#fbbf24', text: '#fbbf24', rowFill: 'rgba(251,191,36,0.18)' },
                    bad:  { fill: 'rgba(248,113,113,0.20)', border: '#f87171', text: '#f87171', rowFill: 'rgba(248,113,113,0.18)' },
                    none: { fill: 'rgba(156,163,175,0.20)', border: '#9ca3af', text: '#e5e7eb', rowFill: 'rgba(156,163,175,0.14)' },
                }
                : {
                    good: { fill: 'rgba(16,185,129,0.15)', border: '#059669', text: '#059669', rowFill: 'rgba(16,185,129,0.14)' },
                    mid:  { fill: 'rgba(245,158,11,0.15)', border: '#d97706', text: '#d97706', rowFill: 'rgba(245,158,11,0.14)' },
                    bad:  { fill: 'rgba(239,68,68,0.15)', border: '#dc2626', text: '#dc2626', rowFill: 'rgba(239,68,68,0.14)' },
                    none: { fill: 'rgba(107,114,128,0.15)', border: '#6b7280', text: '#374151', rowFill: 'rgba(107,114,128,0.10)' },
                };

            const statusOf = (s) => statusColors()[s] || statusColors().none;

            // Solo la hora (sin fecha), para las etiquetas del eje X
            const axisTimeLabel = (hoursOffset) => {
                if (!state.shiftStartIso) return hoursOffset;
                const base = new Date(state.shiftStartIso);
                const d = new Date(base.getTime() + hoursOffset * 3600000);
                return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
            };

            return {
                isEmpty: @json(count($labels) === 0),
                chartHeight: 480,

                // Alto de la gráfica ajustado a la pantalla: llena el alto visible de la
                // ventana; si hay muchos MDIs, crece (mínimo por fila) y la página hace scroll.
                fitHeight() {
                    const wrap = this.$el.querySelector('.relative');
                    const available = window.innerHeight - (wrap ? wrap.getBoundingClientRect().top : 200) - 70;
                    const minPerRow = Math.max(1, state.labelCount) * 52 + 70;
                    this.chartHeight = Math.max(320, minPerRow, available);
                },

                init() {
                    this.fitHeight();
                    this.createChart();

                    $wire.on('update-timeline', (data) => {
                        this.updateChart(data[0]);
                    });

                    resizeHandler = () => {
                        this.fitHeight();
                        this.$nextTick(() => chart?.resize());
                    };
                    window.addEventListener('resize', resizeHandler);

                    // Auto-refresco solo si se está viendo el turno actual en vivo
                    refreshInterval = setInterval(() => {
                        if (live) $wire.dispatchSelf('refresh-timeline');
                    }, 30000);
                },

                createChart() {
                    const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
                    const dark = isDark();

                    const colors = {
                        text:          dark ? '#e5e7eb' : '#374151',
                        textMuted:     dark ? '#9ca3af' : '#6b7280',
                        grid:          dark ? 'rgba(75,85,99,0.30)' : 'rgba(0,0,0,0.08)',
                        tooltipBg:     dark ? 'rgba(17,24,39,0.95)' : 'rgba(255,255,255,0.97)',
                        tooltipBorder: dark ? '#374151' : '#e5e7eb',
                        planFill:      dark ? 'rgba(59,130,246,0.20)' : 'rgba(37,99,235,0.15)',
                        planBorder:    dark ? '#60a5fa' : '#2563eb',
                    };

                    // Línea "Ahora": azul rey, distinta del azul del plan y del verde/rojo del real
                    const nowLineColor = '#4169e1';

                    // Plugin: línea vertical punteada marcando la hora actual
                    const nowLinePlugin = {
                        id: 'nowLine',
                        afterDraw(chart) {
                            const { ctx, chartArea: area, scales } = chart;
                            if (!state.shiftStartIso) return;

                            const nowOffset = (Date.now() - new Date(state.shiftStartIso).getTime()) / 3600000;
                            if (nowOffset < 0 || nowOffset > scales.x.max) return;

                            const nx = scales.x.getPixelForValue(nowOffset);
                            ctx.save();
                            ctx.beginPath();
                            ctx.setLineDash([5, 5]);
                            ctx.lineWidth = 1.5;
                            ctx.strokeStyle = nowLineColor;
                            ctx.moveTo(nx, area.top);
                            ctx.lineTo(nx, area.bottom);
                            ctx.stroke();
                            ctx.setLineDash([]);
                            ctx.restore();
                        }
                    };

                    // Plugin: pinta el fondo de la etiqueta del MDI (eje Y), como si tuviera
                    // un recuadro de su color de estado. No toca el área de las barras.
                    const rowLabelBoxPlugin = {
                        id: 'rowLabelBox',
                        beforeDraw(chart) {
                            const { ctx, scales: { y } } = chart;
                            const count = chart.data.labels.length;
                            if (!count) return;

                            const slotHeight = (y.bottom - y.top) / count;
                            ctx.save();
                            for (let i = 0; i < count; i++) {
                                const centerY = y.getPixelForValue(i);
                                const top = centerY - slotHeight / 2;
                                const status = statusOf(state.rowStatuses[i]);

                                ctx.fillStyle = status.rowFill;
                                ctx.fillRect(y.left, top, y.right - y.left, slotHeight);
                            }
                            ctx.restore();
                        }
                    };

                    // Plugin: número centrado en cada barra (plan: lo que debería llevar;
                    // real: golpes del segmento). Siempre se dibuja completo, aunque no
                    // quepa dentro de la barra, con un halo para que resalte sobre cualquier fondo.
                    const centerLabelsPlugin = {
                        id: 'centerLabels',
                        afterDatasetsDraw(chart) {
                            const { ctx } = chart;
                            ctx.save();
                            ctx.font = 'bold 13px sans-serif';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.lineJoin = 'round';

                            chart.data.datasets.forEach((ds, di) => {
                                const meta = chart.getDatasetMeta(di);
                                if (meta.hidden) return;

                                meta.data.forEach((el, i) => {
                                    const value = di === 0 ? state.planProgress[i] : state.quantities[i];
                                    if (value == null) return;

                                    const text = Number(value).toLocaleString('es-MX');
                                    const cx = (el.x + el.base) / 2;
                                    const color = di === 0
                                        ? colors.planBorder
                                        : statusOf(state.barStatuses[i]).text;

                                    // Halo de contraste detrás del número para que se lea completo
                                    // aunque se salga de la barra o quede sobre el recuadro de fondo.
                                    ctx.lineWidth = 3.5;
                                    ctx.strokeStyle = isDark() ? 'rgba(17,24,39,0.9)' : 'rgba(255,255,255,0.95)';
                                    ctx.strokeText(text, cx, el.y);

                                    ctx.fillStyle = color;
                                    ctx.fillText(text, cx, el.y);
                                });
                            });

                            ctx.restore();
                        }
                    };

                    chart = new Chart(ctx, {
                        type: 'bar',
                        plugins: [rowLabelBoxPlugin, centerLabelsPlugin, nowLinePlugin],
                        data: {
                            labels: @json($labels),
                            datasets: [{
                                label: 'Plan',
                                data: @json($planRanges),   // {x:[inicio,fin], y:mdi} en horas de la línea
                                backgroundColor: colors.planFill,
                                borderColor: colors.planBorder,
                                borderWidth: 2,
                                borderRadius: 3,
                                borderSkipped: false,
                                // Poca separación entre plan y real del mismo MDI, y entre MDIs
                                categoryPercentage: 0.92,
                                barPercentage: 0.96,
                            }, {
                                label: 'Real',
                                data: @json($ranges),   // {x:[inicio,fin], y:mdi} en horas de la línea
                                backgroundColor: (ctx) => statusOf(state.barStatuses[ctx.dataIndex]).fill,
                                borderColor: (ctx) => statusOf(state.barStatuses[ctx.dataIndex]).border,
                                borderWidth: 2,
                                borderRadius: 3,
                                borderSkipped: false,
                                categoryPercentage: 0.92,
                                barPercentage: 0.96,
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: { duration: 400 },
                            interaction: { mode: 'nearest', intersect: true },
                            scales: {
                                x: {
                                    position: 'top',
                                    min: 0,
                                    max: {{ $durationHours }},
                                    ticks: {
                                        stepSize: 1,
                                        color: colors.textMuted,
                                        font: { size: 11 },
                                        maxRotation: 0,
                                        minRotation: 0,
                                        autoSkip: false,
                                        callback: (value) => axisTimeLabel(value),
                                    },
                                    grid: { color: colors.grid, borderDash: [4, 4] },
                                    border: { display: false },
                                    title: { display: true, text: 'Hora', color: colors.textMuted }
                                },
                                y: {
                                    type: 'category',
                                    ticks: {
                                        font: { size: 13, weight: 'bold' },
                                        // MDI con punto y color según su avance (verde/rojo)
                                        color: (ctx) => statusOf(state.rowStatuses[ctx.index]).text,
                                        callback: function (value, index) {
                                            return '● ' + this.getLabelForValue(value);
                                        },
                                    },
                                    // Cuadrícula horizontal más marcada: separa mejor la fila de cada MDI
                                    grid: { display: true, color: dark ? 'rgba(148,163,184,0.35)' : 'rgba(55,65,81,0.22)', lineWidth: 1.5, offset: true },
                                    border: { display: false }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        color: colors.text,
                                        font: { size: 12, weight: 'bold' },
                                        usePointStyle: true,
                                        pointStyle: 'rectRounded',
                                        padding: 16,
                                    }
                                },
                                tooltip: {
                                    backgroundColor: colors.tooltipBg,
                                    titleColor: colors.text,
                                    bodyColor: colors.textMuted,
                                    borderColor: colors.tooltipBorder,
                                    borderWidth: 4,
                                    padding: 12,
                                    cornerRadius: 8,
                                    callbacks: {
                                        title: (items) => items.length ? items[0].label : '',
                                        label: (ctx) => {
                                            const i = ctx.dataIndex;
                                            if (ctx.datasetIndex === 0) {
                                                // Plan: si la ventana ya venció, planProgress es el total;
                                                // si va produciendo, es lo que debería llevar
                                                return [
                                                    ` Horario: ${state.planTimeLabels[i] || ''}`,
                                                    ` Cantidad: ${Number(state.planProgress[i] || 0).toLocaleString('es-MX')}`,
                                                ];
                                            }
                                            return [
                                                ` Horario: ${state.timeLabels[i] || ''}`,
                                                ` Cantidad: ${Number(state.quantities[i] || 0).toLocaleString('es-MX')}`,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    });
                },

                updateChart(data) {
                    if (!chart) return;

                    live = data.isLive;
                    state.rowStatuses = data.rowStatuses;
                    state.planProgress = data.planProgress;
                    state.planTotals = data.planTotals;
                    state.planTimeLabels = data.planTimeLabels;
                    state.quantities = data.quantities;
                    state.timeLabels = data.timeLabels;
                    state.barStatuses = data.barStatuses;
                    state.realTotals = data.realTotals;
                    state.shiftStartIso = data.shiftStartIso;
                    state.labelCount = data.labels ? data.labels.length : 0;

                    this.isEmpty = state.labelCount === 0;
                    this.fitHeight();

                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.planRanges;
                    chart.data.datasets[1].data = data.ranges;
                    chart.options.scales.x.max = data.durationHours;
                    chart.options.scales.x.ticks.stepSize = 1;
                    // Esperar a que el contenedor cambie de alto antes de redibujar
                    this.$nextTick(() => { chart.resize(); chart.update('none'); });
                },

                destroy() {
                    if (refreshInterval) clearInterval(refreshInterval);
                    if (resizeHandler) window.removeEventListener('resize', resizeHandler);
                    chart?.destroy();
                    chart = null;
                }
            };
        });
    </script>
    @endscript
</div>
