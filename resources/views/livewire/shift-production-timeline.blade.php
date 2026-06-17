<div class="w-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Encabezado --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-600">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <h2 class="text-lg font-bold text-blue-800 dark:text-blue-200 flex items-center">
                    <span class="w-2 h-2 bg-blue-500 rounded-full inline-block mr-2"></span>
                    {{ $workCenter }}
                </h2>
                @if($shiftName)
                    <div class="flex flex-wrap gap-2 text-sm font-medium">
                        <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">
                            {{ $plannedDate }}
                        </span>
                        <span class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded-full">
                            {{ $shiftName }}
                        </span>
                    </div>
                @endif
            </div>
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

            // Convierte un offset (horas desde el inicio del turno) a hora de reloj "HH:mm"
            const clockLabel = (hoursOffset) => {
                if (!state.shiftStartIso) return hoursOffset;
                const base = new Date(state.shiftStartIso);
                const d = new Date(base.getTime() + hoursOffset * 3600000);
                return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });
            };

            // Alto del lienzo: 56px por barra (mantiene barras gruesas + separación)
            const heightFor = (count) => Math.max(360, count * 56 + 80);

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

                    chart = new Chart(ctx, {
                        type: 'bar',
                        plugins: [ChartDataLabels],
                        data: {
                            labels: @json($labels),
                            datasets: [{
                                label: 'Producción',
                                data: @json($ranges),   // [[inicio, fin], ...] en horas del turno
                                backgroundColor: colors.barFill,
                                borderColor: colors.barBorder,
                                borderWidth: 1.5,
                                borderRadius: 2,
                                borderSkipped: false,
                                barThickness: 34,
                                maxBarThickness: 40,
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
                                        font: { size: 11 },
                                        callback: (value) => clockLabel(value),
                                    },
                                    grid: { color: colors.grid },
                                    border: { display: false },
                                    title: { display: true, text: 'Hora del turno', color: colors.textMuted }
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
