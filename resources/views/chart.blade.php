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
        var plan = 30; // Cantidad total de piezas a procesar
        var real = 0; // Piezas procesadas en tiempo real

        // Cálculo del tiempo total necesario (en minutos)
        var totalTime = (cycleTime * plan) / 60;

        var currentDate = new Date();

        // Hora de inicio (fija)
        var startHours = currentDate.getHours();
        var startMinutes = currentDate.getMinutes();
        var startSeconds = currentDate.getSeconds();

        // Formato de hora de inicio
        var startFormattedTime = startHours + ':' + (startMinutes < 10 ? '0' + startMinutes : startMinutes) + ':' + (startSeconds < 10 ? '0' + startSeconds : startSeconds);

        const ctx = document.getElementById('myChart');

        // Guardamos el objeto Chart para poder acceder a él más tarde
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['BDTS'],
                datasets: [{
                    label: 'Plan',
                    data: [0], // Inicializamos la barra "Plan" en cero
                    backgroundColor: 'rgba(54, 162, 235, 0.2)', // Color de la barra de "Plan"
                    borderColor: 'rgb(54, 162, 235)', // Borde de la barra de "Plan"
                    borderWidth: 2
                }, {
                    label: 'Real',
                    data: [real],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)', // Azul semitransparente para "Real"
                    borderColor: 'rgb(75, 192, 192)', // Borde azul para la barra de "Real"
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
                                    return startFormattedTime;  // La hora de inicio está fija
                                }
                                // Calculamos los segundos transcurridos
                                var secondsPassed = value * cycleTime;  // Segundos transcurridos
                                var timePassed = new Date(currentDate.getTime() + (secondsPassed * 1000)); // Sumamos los segundos al tiempo de inicio
                                var hours = timePassed.getHours();
                                var minutes = timePassed.getMinutes();
                                var seconds = timePassed.getSeconds();
                                return hours + ':' + (minutes < 10 ? '0' + minutes : minutes) + ':' + (seconds < 10 ? '0' + seconds : seconds);
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

        // Intervalo para actualizar el valor de "Plan" según el tiempo transcurrido
        var planProgress = 0; // Inicializamos el progreso de "Plan"

        setInterval(function() {
            if (planProgress < plan) {
                planProgress++;
                var planProgressValue = (planProgress / plan) * 100; // Porcentaje de avance de "Plan"

                // Actualizamos la barra "Plan"
                myChart.data.datasets[0].data = [planProgress];
                myChart.update(); // Actualizamos la gráfica
            }
        }, cycleTime * 1000); // El ciclo de crecimiento de la barra "Plan"
    </script>
</x-guest-layout>
