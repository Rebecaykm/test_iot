<div>
    <div x-data="charts">
        <div class="grid grid-cols-1 gap-4">
            @foreach ($productionRecords as $productionRecord)
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="p-6 flex flex-col h-full">
                    <div class="chart-wrapper mb-5 flex-1">
                        <canvas id="{{ $productionRecord->production_id }}" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>
            @endforeach

        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

        @script
            <script>
                Alpine.data('charts', () => {
                    return {
                        init() {
                            var productionRecords = @json($productionRecords);

                            productionRecords.forEach(function(productionRecord) {
                                console.log(productionRecord);

                                var cycleTime = 1;
                                var plan = productionRecord.planned_quantity;
                                var real = productionRecord.produced_quantity;

                                var currentDate = new Date();
                                var startHours = currentDate.getHours();
                                var startMinutes = currentDate.getMinutes();
                                var startSeconds = currentDate.getSeconds();
                                var startFormattedTime = startHours + ':' + (startMinutes < 10 ? '0' + startMinutes : startMinutes) + ':' + (startSeconds < 10 ? '0' + startSeconds : startSeconds);

                                var ctx = document.getElementById(productionRecord.production_id).getContext('2d');

                                // Crear el gráfico
                                var myBarChart = new Chart(ctx, {
                                    plugins: [ChartDataLabels],
                                    type: 'bar',
                                    data: {
                                        labels: [productionRecord.part_number],
                                        datasets: [{
                                                label: 'Plan',
                                                data: [0],
                                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                                borderColor: 'rgb(54, 162, 235)',
                                                borderWidth: 2
                                            },
                                            {
                                                label: 'Real',
                                                data: [real],
                                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                                borderColor: 'rgb(75, 192, 192)',
                                                borderWidth: 2
                                            }
                                        ]
                                    },
                                    options: {
                                        indexAxis: 'y',
                                        scales: {
                                            x: {
                                                beginAtZero: true,
                                                ticks: {
                                                    callback: function(value, index, values) {
                                                        if (value === 0) {
                                                            return startFormattedTime;
                                                        }

                                                        if (value < 1) {
                                                            return '';
                                                        }

                                                        if (!Number.isInteger(value)) {
                                                            return '';
                                                        }

                                                        if (value <= plan) {
                                                            var secondsPassed = value * cycleTime;
                                                            var timePassed = new Date(currentDate.getTime() + (secondsPassed * 1000));
                                                            var hours = timePassed.getHours();
                                                            var minutes = timePassed.getMinutes();
                                                            var seconds = timePassed.getSeconds();

                                                            return hours + ':' + (minutes < 10 ? '0' + minutes : minutes) + ':' + (seconds < 10 ? '0' + seconds : seconds);
                                                        }
                                                    }
                                                }
                                            },
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    maxRotation: 90,
                                                    minRotation: 90,
                                                }
                                            }
                                        },
                                        plugins: [{
                                            datalabels: {
                                                anchor: 'end',
                                                align: 'end',
                                                font: {
                                                    weight: 'bold',
                                                    size: 18
                                                },
                                                color: 'black',
                                                position: 'top',
                                                offset: 10,
                                            },
                                        }],
                                    }
                                });

                                // Real
                                setInterval(function() {
                                    myBarChart.data.datasets[1].data = [real]; // Actualizamos el valor de 'Real' directamente
                                    myBarChart.update(); // Actualizamos la gráfica
                                }, 1000);


                                // Plan
                                var planProgress = 0;

                                setInterval(function() {
                                    if (planProgress < plan) {
                                        planProgress++;
                                        myBarChart.data.datasets[0].data = [planProgress];
                                        myBarChart.update();
                                    }
                                }, cycleTime * 1000);
                            });

                        }
                    }
                });
            </script>
        @endscript
    </div>
</div>
