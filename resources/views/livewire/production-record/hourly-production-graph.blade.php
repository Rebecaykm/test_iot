<div>
    <div class="py-12 w-full max-w-7xl">
        <div id="charts-container" class="grid grid-cols-1 sm:grid-cols-2 gap-8">
            @foreach ($chartData as $chart)
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartData = @json($chartData);

            chartData.forEach(chart => {
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
                } else {
                    console.error('Canvas not found for chart:', chart.chart_id);
                }
            });
        });
    </script>

</div>
