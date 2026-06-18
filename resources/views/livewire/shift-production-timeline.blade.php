<div class="w-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Encabezado --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-600">
            <h2 class="text-lg font-bold text-blue-800 dark:text-blue-200 flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full inline-block mr-2"></span>
                {{ $workCenter }}
            </h2>
        </div>

        {{-- Chart --}}
        <div x-data="shiftTimelineChart" wire:ignore class="px-4 py-5 sm:px-6">
            <div class="relative" :style="`height: ${chartHeight}px`">
                <canvas id="{{ $chartId }}"></canvas>
            </div>
            <p x-show="isEmpty" x-cloak
               class="text-center py-8 text-gray-500 dark:text-gray-400">
                No hay producción registrada en el turno actual.
            </p>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

    @script
    <script>
        Alpine.data('shiftTimelineChart', () => {
            let chart = null;
            let refreshInterval = null;

            // Estado de los datos actuales
            let state = {
                quantities: @json($quantities),
                timeLabels: @json($timeLabels),
                shiftStartIso: @json($shiftStartIso),
            };

            const pad = (n) => String(n).padStart(2, '0');

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

                    refreshInterval = setInterval(() => {
                        $wire.dispatchSelf('refresh-timeline');
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
                                borderWidth: 1.5,
                                borderRadius: 8,
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
