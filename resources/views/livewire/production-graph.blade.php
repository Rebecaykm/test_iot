<div>

    <div x-data="charts()" x-init="initializeCharts()">
        <div class="grid grid-cols-1 gap-4">
            @foreach ($chartData as $chart)
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="chart-wrapper mb-5">
                            <div class="w-full h-96">
                                <canvas id="{{ $chart['chart_id'] }}" class="w-full h-full"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            function charts() {
                return {
                    charts: @json($chartData),
                    initializeCharts() {
                        this.charts.forEach(chart => {
                            const ctx = document.getElementById(chart.chart_id);

                            if (ctx) {
                                new Chart(ctx, {
                                    type: 'bar',
                                    data: {
                                        labels: chart.labels,
                                        datasets: chart.datasets
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
                                                text: `Production per Hour: ${chart.work_name}`
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                // stacked: true
                                            }
                                        }
                                    }
                                });
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
