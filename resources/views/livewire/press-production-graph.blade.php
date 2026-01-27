<div class="w-full">
    <div x-data="productionChart" class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <div class="flex justify-end items-center mb-4">
            <div class="flex gap-3 text-[10px] font-bold">
                <span class="flex items-center"><span class="w-3 h-3 bg-green-500/20 border border-green-600 mr-1 rounded-sm"></span> REAL</span>
                <span class="flex items-center"><span class="w-3 h-3 bg-red-500/20 border border-red-600 mr-1 rounded-sm"></span> FALTANTE</span>
                <span class="flex items-center"><span class="w-3 h-3 bg-blue-500/20 border border-blue-600 mr-1 rounded-sm"></span> EXCEDENTE</span>
            </div>
        </div>

        <div class="relative h-[450px]">
            <canvas id="{{ $chartId }}" wire:ignore></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productionChart', () => ({
                chart: null,
                init() {
                    const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
                    let pData = @json($plannedData);
                    let rData = @json($producedData);
                    let dData = @json($differenceData);

                    this.chart = new Chart(ctx, {
                        plugins: [ChartDataLabels], // Registro del plugin
                        data: {
                            labels: @json($labels),
                            datasets: [{
                                    type: 'line',
                                    label: 'Plan',
                                    data: pData,
                                    borderColor: '#1e40af',
                                    borderWidth: 3,
                                    pointRadius: 6,
                                    fill: false,
                                    tension: 0.2,
                                    datalabels: {
                                        align: 'top',
                                        anchor: 'end',
                                        offset: 8
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Real',
                                    data: rData.map((v, i) => v > pData[i] ? pData[i] : v),
                                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                                    borderColor: '#16a34a',
                                    borderWidth: 2,
                                    stack: 's1',
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                    }
                                },
                                {
                                    type: 'bar',
                                    label: 'Diferencia',
                                    data: dData.map(v => Math.abs(v)),
                                    backgroundColor: (c) => dData[c.dataIndex] < 0 ? 'rgba(239, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.2)',
                                    borderColor: (c) => dData[c.dataIndex] < 0 ? '#dc2626' : '#2563eb',
                                    borderWidth: 2,
                                    stack: 's1',
                                    datalabels: {
                                        anchor: 'center',
                                        align: 'center',
                                    }
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: { stacked: true },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    ticks: { font: { weight: 'bold', size: 14 } }
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                // Configuración Global de las etiquetas
                                datalabels: {
                                    color: '#111827', // Gris muy oscuro (casi negro)
                                    font: {
                                        size: 16,     // Letra grande
                                        weight: 'bold'
                                    },
                                    formatter: function(value) {
                                        return value !== 0 ? value : ''; // No mostrar si es 0
                                    }
                                }
                            }
                        }
                    });

                    window.addEventListener('update-chart', (e) => {
                        const d = e.detail[0];
                        pData = d.planned;
                        rData = d.produced.map(v => v === null ? 0 : v);
                        dData = d.difference;

                        this.chart.data.labels = d.labels;
                        this.chart.data.datasets[0].data = pData;
                        this.chart.data.datasets[1].data = rData.map((v, i) => v > pData[i] ? pData[i] : v);
                        this.chart.data.datasets[2].data = dData.map(v => Math.abs(v));

                        this.chart.update();
                    });
                }
            }));
        });
    </script>
</div>
