<div>
    <div x-data="chart">
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
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

        @script
        <script>
            Alpine.data('chart', () => {
                return {
                    init() {
                        // var currentDate = new Date();
                        // var startHours = currentDate.getHours();
                        // var startMinutes = currentDate.getMinutes();
                        // var startSeconds = currentDate.getSeconds();
                        // var startFormattedTime = startHours + ':' + (startMinutes < 10 ? '0' + startMinutes : startMinutes) + ':' + (startSeconds < 10 ? '0' + startSeconds : startSeconds);

                        let plannedData = $wire.entangle("plannedData").live.initialValue;
                        let productionRate = $wire.entangle("productionRate").live.initialValue;
                        let productionStart = $wire.entangle("productionStart").live.initialValue;

                        let planProgress = new Array(plannedData.length).fill(0);

                        const ctx = document.getElementById(@json($chartId));

                        ds = [{
                            label: 'Plan',
                            data: planProgress,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 2
                        }, {
                            label: 'Real',
                            data: $wire.entangle("producedData").live.initialValue,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgb(75, 192, 192)',
                            borderWidth: 2
                        }];

                        var chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: $wire.entangle("labels").live.initialValue,
                                datasets: ds,
                            },
                            plugins: [ChartDataLabels],
                            options: {
                                responsive: true,
                                indexAxis: 'y',
                                plugins: {
                                    datalabels: {
                                        anchor: 'start',
                                        align: 'center',
                                        color: 'black',
                                        font: {
                                            weight: 'bold',
                                            size: 16
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        ticks: {
                                            display: true,
                                        },
                                        // ticks: {
                                        //     callback: function(value, index, values) {
                                        //         if (value === 0) {
                                        //             return startFormattedTime;
                                        //         }

                                        //         if (value < 1) {
                                        //             return '';
                                        //         }

                                        //         if (!Number.isInteger(value)) {
                                        //             return '';
                                        //         }

                                        //         let currentPlannedData = plannedData[index % plannedData.length];
                                        //         let currentProductionRate = productionRate[index % productionRate.length];

                                        //         if (value <= currentPlannedData) {
                                        //             var secondsPassed = value * currentProductionRate;

                                        //             var timePassed = new Date(currentDate.getTime() + (secondsPassed * 1000));

                                        //             var hours = timePassed.getHours();
                                        //             var minutes = timePassed.getMinutes();
                                        //             var seconds = timePassed.getSeconds();

                                        //             return hours + ':' + (minutes < 10 ? '0' + minutes : minutes) + ':' + (seconds < 10 ? '0' + seconds : seconds);
                                        //         }
                                        //     }
                                        // }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            maxRotation: 90,
                                            minRotation: 90,
                                        }
                                    }
                                },
                                layout: {
                                    padding: {
                                        left: 8,
                                        right: 8,
                                        top: 8,
                                        bottom: 8
                                    }
                                }
                            }
                        });

                        if (@json($realTime)) {
                            // Real
                            setInterval(() => {
                                $wire.dispatchSelf("refresh-graph");
                                chart.data.labels = $wire.entangle("labels").live.initialValue;
                                chart.data.datasets[1].data = $wire.entangle("producedData").live.initialValue;
                                chart.update();
                            }, 1000);


                            // Plan
                            setInterval(function() {
                                let currentDate = new Date(); // Una sola vez fuera del bucle.

                                for (let i = 0; i < plannedData.length; i++) {
                                    let currentProductionRate = Math.round(3600 / productionRate[i]);

                                    let currentProductionStart = new Date(productionStart[i]);
                                    let timeDifference = Math.round((currentDate - currentProductionStart) / 1000);

                                    let currentQuantity = Math.round(timeDifference / currentProductionRate);

                                    if (planProgress[i] < plannedData[i]) {
                                        planProgress[i] = Math.min(currentQuantity, plannedData[i]);
                                        chart.data.datasets[0].data[i] = planProgress[i];
                                        chart.update();
                                    }
                                }

                            }, 1000);
                        }

                    }
                }
            });
        </script>
        @endscript
    </div>
</div>
