<div class="w-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Chart --}}
        <div x-data="productionChart" wire:ignore class="px-4 py-5 sm:px-6">
            <div class="relative h-[420px]">
                <canvas id="{{ $chartId }}"></canvas>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

    @script
    <script>
        Alpine.data('productionChart', () => {
            let chart = null;

            return {
                init() {
                    this.createChart();

                    // Escuchar el evento de Livewire correctamente
                    $wire.on('update-chart', (data) => {
                        this.updateChart(data[0]);
                    });

                    // Iniciar actualizaciones automáticas cada 30 segundos
                    refreshInterval = setInterval(() => {
                        $wire.dispatchSelf('refresh-graph');
                    }, 10000);
                },

                createChart() {
                    const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
                    const pData = @json($plannedData);
                    const rData = @json($producedData);
                    const dData = @json($differenceData);
                    const isDark = document.documentElement.classList.contains('dark');

                    const colors = {
                        text:       isDark ? '#e5e7eb' : '#374151',
                        textMuted:  isDark ? '#9ca3af' : '#6b7280',
                        grid:       isDark ? 'rgba(75,85,99,0.25)' : 'rgba(0,0,0,0.06)',
                        tooltipBg:  isDark ? 'rgba(17,24,39,0.95)' : 'rgba(255,255,255,0.97)',
                        tooltipBorder: isDark ? '#374151' : '#e5e7eb',

                        planLine:   isDark ? '#93c5fd' : '#2563eb',
                        planPoint:  isDark ? '#60a5fa' : '#1d4ed8',

                        realFill:   isDark ? 'rgba(52,211,153,0.35)' : 'rgba(16,185,129,0.15)',
                        realBorder: isDark ? '#34d399' : '#059669',

                        shortFill:   isDark ? 'rgba(248,113,113,0.35)' : 'rgba(239,68,68,0.15)',
                        shortBorder: isDark ? '#f87171' : '#dc2626',

                        surplusFill:   isDark ? 'rgba(96,165,250,0.35)' : 'rgba(59,130,246,0.15)',
                        surplusBorder: isDark ? '#60a5fa' : '#3b82f6',

                        label: isDark ? '#ffffff' : '#000000',
                    };

                    chart = new Chart(ctx, {
                        plugins: [ChartDataLabels],
                        data: {
                            labels: @json($labels),
                            datasets: [
                                {
                                    type: 'line',
                                    label: 'Plan',
                                    data: pData,
                                    borderColor: colors.planLine,
                                    pointBackgroundColor: colors.planPoint,
                                    pointBorderColor: isDark ? '#1e293b' : '#ffffff',
                                    pointBorderWidth: 2,
                                    borderWidth: 2.5,
                                    pointRadius: 5,
                                    pointHoverRadius: 6,
                                    fill: false,
                                    tension: 0.2,
                                    order: 0,
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        offset: 8,
                                        color: colors.label,
                                        font: { size: 12, weight: 'bold' },
                                        formatter: v => (v && v > 0) ? v.toLocaleString('es-MX') : ''
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Real',
                                    data: rData.map((v, i) => v !== null && v > pData[i] ? pData[i] : v),
                                    backgroundColor: colors.realFill,
                                    borderColor: colors.realBorder,
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    borderSkipped: 'bottom',
                                    stack: 's1',
                                    order: 1,
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                        color: colors.label,
                                        font: { size: 12, weight: 'bold' },
                                        formatter: v => (v && v > 0) ? v.toLocaleString('es-MX') : ''
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Faltante',
                                    data: dData.map(v => v < 0 ? Math.abs(v) : 0),
                                    backgroundColor: colors.shortFill,
                                    borderColor: colors.shortBorder,
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    borderSkipped: 'bottom',
                                    stack: 's1',
                                    order: 1,
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                        color: colors.label,
                                        font: { size: 12, weight: 'bold' },
                                        formatter: v => (v && v > 0) ? v.toLocaleString('es-MX') : ''
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Excedente',
                                    data: dData.map(v => v > 0 ? v : 0),
                                    backgroundColor: colors.surplusFill,
                                    borderColor: colors.surplusBorder,
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    borderSkipped: 'bottom',
                                    stack: 's1',
                                    order: 1,
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                        color: colors.label,
                                        font: { size: 12, weight: 'bold' },
                                        formatter: v => (v && v > 0) ? v.toLocaleString('es-MX') : ''
                                    }
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: { duration: 400 },
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    ticks: {
                                        color: colors.textMuted,
                                        font: { size: 11 },
                                        maxRotation: 30,
                                        minRotation: 0
                                    },
                                    grid: { color: colors.grid },
                                    border: { display: false }
                                },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    ticks: {
                                        color: colors.textMuted,
                                        font: { size: 11 },
                                        callback: v => v.toLocaleString('es-MX')
                                    },
                                    grid: { color: colors.grid },
                                    border: { display: false }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        color: colors.text,
                                        font: { size: 12 },
                                        padding: 14,
                                        usePointStyle: true,
                                        pointStyle: 'rectRounded',
                                        boxWidth: 12,
                                        boxHeight: 12,
                                        generateLabels(chart) {
                                            return chart.data.datasets.map((ds, i) => ({
                                                text: ds.label,
                                                fillStyle: ds.backgroundColor,
                                                strokeStyle: ds.borderColor,
                                                lineWidth: ds.borderWidth,
                                                hidden: !chart.isDatasetVisible(i),
                                                index: i,
                                                pointStyle: 'rectRounded'
                                            }));
                                        }
                                    },
                                    onClick(e, item, legend) {
                                        const chart = legend.chart;
                                        chart.isDatasetVisible(item.index)
                                            ? chart.hide(item.index)
                                            : chart.show(item.index);
                                    }
                                },
                                tooltip: {
                                    backgroundColor: colors.tooltipBg,
                                    titleColor: colors.text,
                                    bodyColor: colors.textMuted,
                                    borderColor: colors.tooltipBorder,
                                    borderWidth: 1,
                                    padding: 12,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label(ctx) {
                                            const v = ctx.raw;
                                            if (v === 0 || v === null) return null;
                                            return ` ${ctx.dataset.label}: ${Number(v).toLocaleString('es-MX')}`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                },

                updateChart(data) {
                    if (!chart) return;
                    const pData = data.planned;
                    const rData = data.produced.map(v => v === null ? 0 : v);
                    const dData = data.difference;

                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = pData;
                    chart.data.datasets[1].data = rData.map((v, i) => v > pData[i] ? pData[i] : v);
                    chart.data.datasets[2].data = dData.map(v => v < 0 ? Math.abs(v) : 0);
                    chart.data.datasets[3].data = dData.map(v => v > 0 ? v : 0);
                    chart.update('none');
                },

                destroy() {
                    chart?.destroy();
                    chart = null;
                }
            };
        });
    </script>
    @endscript
</div>
