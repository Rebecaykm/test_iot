<div>
    <div x-data="charts">
        <div class="grid grid-cols-1 gap-4">
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="p-6 flex flex-col h-full">
                    <div class="chart-wrapper mb-5 flex-1">
                        <canvas wire:ignore id="{{ $chartId }}" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        @script
        <script>
            Alpine.data('charts', () => {
                return {
                    init() {
                        const ctx = document.getElementById(@json($chartId));

                        var chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: $wire.entangle("labels").live.initialValue,
                                datasets: $wire.entangle("datasets").live.initialValue,
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
                                        text: 'Producción por Hora: ' + $wire.entangle("workCenter").live.initialValue
                                    }
                                },
                                scales: {
                                    x: {
                                        stacked: true,
                                    },
                                    y: {
                                        stacked: false,
                                        ticks: {
                                            beginAtZero: true,
                                        },
                                    }
                                }
                            }
                        });

                        if (@json($realTime)) {
                            setInterval(() => {
                                $wire.dispatchSelf("refresh-graph");
                                chart.data.labels = $wire.entangle("labels").live.initialValue;
                                chart.data.datasets = $wire.entangle("datasets").live.initialValue;
                                chart.update();
                            }, 1000);
                        }
                    }
                }
            });
        </script>
        @endscript
    </div>
</div>
