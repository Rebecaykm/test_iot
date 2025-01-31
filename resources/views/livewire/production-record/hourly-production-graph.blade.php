<div>

    <div x-data="charts()" x-init="initializeCharts()" class="py-12 w-full max-w-7xl">
        <div id="charts-container" class="grid grid-cols-1 sm:grid-cols-2 gap-8">
            @foreach ($chartData as $chart)
                <div class="bg-white shadow-lg rounded-lg overflow-hidden" wire:ignore>
                    <div class="p-6">
                        <div class="chart-wrapper mb-5">
                            <h3 class="text-center text-2xl font-semibold text-gray-800 uppercase">
                                {{ $chart['work_name'] }} - {{ $chart['part_number'] }}
                            </h3>
                            <div class="w-full h-96">
                                <canvas id="{{ $chart['chart_id'] }}" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            {{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

        <script>
            function charts() {
                return {
                    charts: @json($chartData),
                    realTime: @json($realTime),
                    initializeCharts() {
                        this.charts.forEach(chart => {
                            const ctx = document.getElementById(chart.chart_id);

                            if (ctx) {
                                const chartInstance = new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: chart.labels,
                                        datasets: chart.datasets,
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'top',
                                            },
                                            title: {
                                                display: true,
                                                text: `Production per Hour: ${chart.work_name} - ${chart.part_number}`
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                stacked: true
                                            }
                                        }
                                    }
                                });

                                // Actualización en tiempo real
                                if (this.realTime) {
                                    setInterval(() => {
                                        $wire.dispatchSelf('refreshGraph');

                                        chartInstance.data.labels = $wire.entangle('labels').live.initialValue;
                                        chartInstance.data.datasets[0].data = $wire.entangle('data').live
                                            .initialValue;

                                        chartInstance.update();
                                    }, 1000); // Cada segundo
                                }
                            } else {
                                console.error('Canvas not found for chart:', chart.chart_id);
                            }
                        });
                    }
                }
            }
        </script>
    </div>


</div>
