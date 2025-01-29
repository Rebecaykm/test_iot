<div>
<div class="space-y-6">
        @foreach ($chartData as $chart)
            <div class="bg-white p-4 rounded-lg shadow">
                <h2 class="text-lg font-bold text-center">
                    {{ $chart['work_name'] }} - {{ $chart['part_number'] }}
                </h2>
                <div>
                    <canvas id="{{ $chartId }}"></canvas>
                </div>
            </div>
        @endforeach

    @push('scripts')
        <script>
            console.log('Hola')
            document.addEventListener('livewire:load', () => {
                const chartData = @json($chartData);
                console.log('Hola')
                Object.entries(chartData).forEach(([workName, charts]) => {
                    charts.forEach(chart => {
                        const ctx = document.getElementById(@json($chartId));
                        console.log(ctx);
                        if (ctx) {
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: chart.labels,
                                    datasets: [
                                        {
                                            label: 'Planned Quantity',
                                            data: chart.plannedData,
                                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                                            borderColor: 'rgb(255, 159, 64)',
                                            borderWidth: 2
                                        },
                                        {
                                            label: 'Produced Quantity',
                                            data: chart.productionData,
                                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                            borderColor: 'rgb(75, 192, 192)',
                                            borderWidth: 2
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: { beginAtZero: true }
                                    }
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</div>

</div>
