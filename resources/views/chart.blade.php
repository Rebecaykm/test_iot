<x-guest-layout>
    <div class="pt-4 bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg prose dark:prose-invert">

                <div>
                    <canvas id="myChart"></canvas>
                </div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
        var cycleTime = 1; // Tiempo por ciclo (en segundos)
        var plan = 120; // Cantidad total de piezas a procesar
        var real = 0; // Piezas procesadas en tiempo real

        // Cálculo del tiempo total necesario (en minutos)
        var totalTime = (cycleTime * plan) / 60;

        var currentDate = new Date(); // Hora actual

        // Hora de inicio
        var startHours = currentDate.getHours();
        var startMinutes = currentDate.getMinutes();
        var startSeconds = currentDate.getSeconds();

        // Sumar el tiempo total al objeto Date
        currentDate.setMinutes(currentDate.getMinutes() + totalTime);

        // Hora de fin
        var endHours = currentDate.getHours();
        var endMinutes = currentDate.getMinutes();
        var endSeconds = currentDate.getSeconds();

        // Formato de hora con minutos y segundos
        var startformattedTime = startHours + ':' + (startMinutes < 10 ? '0' + startMinutes : startMinutes) + ':' + (startSeconds < 10 ? '0' + startSeconds : startSeconds);
        var endFormattedTime = endHours + ':' + (endMinutes < 10 ? '0' + endMinutes : endMinutes) + ':' + (endSeconds < 10 ? '0' + endSeconds : endSeconds);

        const ctx = document.getElementById('myChart');

        // Guardamos el objeto Chart para poder acceder a él más tarde
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['BDTS'],
                datasets: [{
                    label: 'Plan',
                    data: [plan],
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
                // responsive: true,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                if (value === 0) {
                                    return startformattedTime;
                                }
                                if (value === plan) {
                                    return endFormattedTime;
                                }
                                return;
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
                    }
                }
            }
        });

        // Intervalo para actualizar el valor de "Real" cada segundo
        setInterval(function() {
            real++;
            myChart.data.datasets[1].data = [real]; // Actualizamos directamente el valor de "Real"
            myChart.update(); // Actualizamos la gráfica
        }, 1000);
    </script>
</x-guest-layout>
