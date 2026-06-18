<div class="w-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Encabezado --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-600">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold text-blue-800 dark:text-blue-200 flex items-center">
                    <span class="w-2 h-2 bg-blue-500 rounded-full inline-block mr-2"></span>
                    {{ $workCenter }}
                </h2>

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Indicador en vivo / histórico --}}
                    @if($isLive)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            En vivo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            Histórico
                        </span>
                    @endif

                    {{-- Calendario (date picker) --}}
                    <div x-data="datePicker(@js($selectedDate))" @click.away="open = false" class="relative">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:border-blue-400 dark:hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
                        class="pl-3 pr-9 py-1.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors cursor-pointer">
                        @foreach($shiftOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Chart --}}
        <div x-data="shiftTimelineChart" wire:ignore class="px-4 py-5 sm:px-6">
            <div class="relative" :style="`height: ${chartHeight}px`">
                <canvas id="{{ $chartId }}"></canvas>
            </div>
            <p x-show="isEmpty" x-cloak
               class="text-center py-8 text-gray-500 dark:text-gray-400">
                No hay producción registrada en este turno.
            </p>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

    @script
    <script>
        const pad = (n) => String(n).padStart(2, '0');

        // ---- Calendario (date picker) ----
        Alpine.data('datePicker', (initial) => ({
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

        Alpine.data('shiftTimelineChart', () => {
            let chart = null;
            let refreshInterval = null;
            // Solo se auto-refresca cuando se ve el turno actual en vivo
            let live = @json($isLive);

            // Estado de los datos actuales
            let state = {
                quantities: @json($quantities),
                timeLabels: @json($timeLabels),
                shiftStartIso: @json($shiftStartIso),
            };

            // Convierte un offset (horas desde el inicio de la línea) a "dd-mm HH:mm"
            const clockLabel = (hoursOffset) => {
                if (!state.shiftStartIso) return hoursOffset;
                const base = new Date(state.shiftStartIso);
                const d = new Date(base.getTime() + hoursOffset * 3600000);
                return `${pad(d.getDate())}-${pad(d.getMonth() + 1)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
            };

            // Alto del lienzo: poco espacio entre barras (barra 26px + ~6px de separación)
            const heightFor = (count) => Math.max(300, count * 32 + 60);

            return {
                isEmpty: @json(count($labels) === 0),
                chartHeight: heightFor(@json(count($labels))),

                init() {
                    this.createChart();

                    $wire.on('update-timeline', (data) => {
                        this.updateChart(data[0]);
                    });

                    // Auto-refresco solo si se está viendo el turno actual en vivo
                    refreshInterval = setInterval(() => {
                        if (live) $wire.dispatchSelf('refresh-timeline');
                    }, 30000);
                },

                createChart() {
                    const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');

                    const colors = {
                        text:          isDark ? '#e5e7eb' : '#374151',
                        textMuted:     isDark ? '#9ca3af' : '#6b7280',
                        grid:          isDark ? 'rgba(75,85,99,0.25)' : 'rgba(0,0,0,0.06)',
                        tooltipBg:     isDark ? 'rgba(17,24,39,0.95)' : 'rgba(255,255,255,0.97)',
                        tooltipBorder: isDark ? '#374151' : '#e5e7eb',
                        barFill:       isDark ? 'rgba(52,211,153,0.18)' : 'rgba(16,185,129,0.15)',
                        barBorder:     isDark ? '#34d399' : '#059669',
                        label:         isDark ? '#ffffff' : '#111827',
                    };

                    // Dibuja una etiqueta de fecha/hora junto al puntero (x, y)
                    const drawTimeTag = (ctx, x, y, area, text, color) => {
                        ctx.font = 'bold 11px sans-serif';
                        const padding = 6;
                        const boxW = ctx.measureText(text).width + padding * 2;
                        const boxH = 18;
                        // Por defecto a la derecha del puntero; si no cabe, a la izquierda
                        let boxX = x + 12;
                        if (boxX + boxW > area.right) boxX = x - 12 - boxW;
                        boxX = Math.max(area.left, Math.min(boxX, area.right - boxW));
                        // Centrada verticalmente en el puntero, acotada al área
                        let boxY = y - boxH / 2;
                        boxY = Math.max(area.top, Math.min(boxY, area.bottom - boxH));
                        ctx.fillStyle = color;
                        ctx.fillRect(boxX, boxY, boxW, boxH);
                        ctx.fillStyle = '#ffffff';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(text, boxX + boxW / 2, boxY + boxH / 2);
                    };

                    // Plugin: línea "Ahora" (ámbar) fija + línea roja que sigue el mouse
                    const crosshairPlugin = {
                        id: 'crosshair',
                        afterEvent(chart, args) {
                            const e = args.event;
                            const area = chart.chartArea;
                            const inside = e.x >= area.left && e.x <= area.right
                                && e.y >= area.top && e.y <= area.bottom;
                            if (e.type === 'mousemove' && inside) {
                                chart._crosshairX = e.x;
                                chart._crosshairY = e.y;
                            } else {
                                chart._crosshairX = null;
                                chart._crosshairY = null;
                            }
                            args.changed = true; // forzar redibujo para mover la línea
                        },
                        afterDraw(chart) {
                            const { ctx, chartArea: area, scales } = chart;

                            // Línea "Ahora" (hora actual)
                            if (state.shiftStartIso) {
                                const nowOffset = (Date.now() - new Date(state.shiftStartIso).getTime()) / 3600000;
                                if (nowOffset >= 0 && nowOffset <= scales.x.max) {
                                    const nx = scales.x.getPixelForValue(nowOffset);
                                    ctx.save();
                                    ctx.beginPath();
                                    ctx.setLineDash([5, 5]);
                                    ctx.lineWidth = 1.5;
                                    ctx.strokeStyle = '#f59e0b';
                                    ctx.moveTo(nx, area.top);
                                    ctx.lineTo(nx, area.bottom);
                                    ctx.stroke();
                                    ctx.setLineDash([]);
                                    ctx.restore();
                                }
                            }

                            // Línea roja que sigue el mouse + fecha/hora junto al puntero
                            const x = chart._crosshairX;
                            if (x == null) return;
                            const y = chart._crosshairY ?? area.top;
                            ctx.save();
                            ctx.beginPath();
                            ctx.setLineDash([6, 6]);
                            ctx.lineWidth = 1.5;
                            ctx.strokeStyle = '#ef4444';
                            ctx.moveTo(x, area.top);
                            ctx.lineTo(x, area.bottom);
                            ctx.stroke();
                            ctx.setLineDash([]);
                            drawTimeTag(ctx, x, y, area, clockLabel(scales.x.getValueForPixel(x)), '#ef4444');
                            ctx.restore();
                        }
                    };

                    chart = new Chart(ctx, {
                        type: 'bar',
                        plugins: [ChartDataLabels, crosshairPlugin],
                        data: {
                            labels: @json($labels),
                            datasets: [{
                                label: 'Producción',
                                data: @json($ranges),   // [[inicio, fin], ...] en horas de la línea
                                backgroundColor: colors.barFill,
                                borderColor: colors.barBorder,
                                borderWidth: 1,
                                borderRadius: 3,
                                borderSkipped: false,
                                barThickness: 26,
                                maxBarThickness: 30,
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
                                    min: 0,
                                    max: {{ $durationHours }},
                                    ticks: {
                                        stepSize: 1,
                                        color: colors.textMuted,
                                        font: { size: 10 },
                                        maxRotation: 90,
                                        minRotation: 90,
                                        autoSkip: false,
                                        callback: (value) => clockLabel(value),
                                    },
                                    grid: { color: colors.grid },
                                    border: { display: false },
                                    title: { display: true, text: 'Fecha y hora', color: colors.textMuted }
                                },
                                y: {
                                    type: 'category',
                                    ticks: { color: colors.text, font: { size: 12 } },
                                    grid: { display: false },
                                    border: { display: false }
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                datalabels: { display: false },
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
                                            const horario = state.timeLabels[ctx.dataIndex] || '';
                                            const q = state.quantities[ctx.dataIndex] || 0;
                                            return [
                                                ` Horario: ${horario}`,
                                                ` Cantidad: ${Number(q).toLocaleString('es-MX')}`,
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
                    state.quantities = data.quantities;
                    state.timeLabels = data.timeLabels;
                    state.shiftStartIso = data.shiftStartIso;

                    this.isEmpty = !data.labels || data.labels.length === 0;
                    this.chartHeight = heightFor(data.labels ? data.labels.length : 0);

                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.ranges;
                    chart.options.scales.x.max = data.durationHours;
                    chart.options.scales.x.ticks.stepSize = 1;
                    // Esperar a que el contenedor cambie de alto antes de redibujar
                    this.$nextTick(() => { chart.resize(); chart.update('none'); });
                },

                destroy() {
                    if (refreshInterval) clearInterval(refreshInterval);
                    chart?.destroy();
                    chart = null;
                }
            };
        });
    </script>
    @endscript
</div>
