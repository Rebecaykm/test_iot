<x-guest-layout>
    <div class="pt-4 bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg prose dark:prose-invert">

                <div class="flex justify-end items-center uppercase">
                    <h4 class="text-lg font-semibold">
                        <span id="clock" class="bg-gray-600 text-white py-2 px-3 rounded-full">
                        </span>
                    </h4>
                </div>

                <div>
                    <canvas wire:ignore id="myChart"></canvas>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
        var productionRate = 1;
        var plannedData = 30;
        var real = 0;

        var currentDate = new Date();

        var startHours = currentDate.getHours();
        var startMinutes = currentDate.getMinutes();
        var startSeconds = currentDate.getSeconds();

        var startFormattedTime = startHours + ':' + (startMinutes < 10 ? '0' + startMinutes : startMinutes) + ':' + (startSeconds < 10 ? '0' + startSeconds : startSeconds);

        const ctx = document.getElementById('myChart');

        var myChart = new Chart(ctx, {
            plugins: [ChartDataLabels],
            type: 'bar',
            data: {
                labels: ['BDTS'],
                datasets: [{
                    label: 'Plan',
                    data: [0],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 2
                }, {
                    label: 'Real',
                    data: [real],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 2
                }]
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

                                if (value <= plannedData) {
                                    var secondsPassed = value * productionRate;
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
                plugins: {
                    title: {
                        display: true,
                        text: 'Chart.js Horizontal Bar Chart'
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        color: 'black',
                        position: 'top',
                        offset: 10,
                    },
                }
            }
        });

        // Real
        setInterval(function() {
            real++;
            myChart.data.datasets[1].data = [real]; // Actualizamos directamente el valor de "Real"
            myChart.update(); // Actualizamos la gráfica
        }, 2000);

        // Plan
        var planProgress = 0;

        setInterval(function() {
            if (planProgress < plannedData) {
                planProgress++;
                var planProgressValue = (planProgress / plannedData) * 100; // Porcentaje de avance de "Plan"

                // Actualizamos la barra "Plan"
                myChart.data.datasets[0].data = [planProgress];
                myChart.update(); // Actualizamos la gráfica
            }
        }, productionRate * 1000);

        // Reloj en tiempo real
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");
            const seconds = String(now.getSeconds()).padStart(2, "0");

            const timeString = `${hours}:${minutes}:${seconds}`;
            document.getElementById("clock").innerText = timeString;
        }

        setInterval(updateClock, 1000);
    </script>
</x-guest-layout>
