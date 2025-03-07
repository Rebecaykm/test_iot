<x-guest-layout>
    <div class="pt-4 bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg prose dark:prose-invert">

                <canvas id="myChart" width="400" height="400"></canvas>

                <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.0/dist/chart.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

            </div>
        </div>
    </div>
    <script>
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar', // Tipo de gráfico (puede ser 'bar', 'line', 'pie', etc.)
            plugins: [ChartDataLabels], // Habilitamos el plugin
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo'], // Etiquetas de los datos
                datasets: [{
                    label: 'Ventas del 2025', // Nombre de la serie
                    data: [12, 19, 3, 5, 2], // Datos de las barras
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                    ], // Colores de fondo de las barras
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                    ], // Colores de borde de las barras
                    borderWidth: 1 // Grosor del borde
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        anchor: 'end', // Esto coloca la etiqueta al final de la barra
                        // clamp:true,
                        align: 'end', // Esto coloca la etiqueta al inicio de la barra, en la parte superior
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        color: 'black', // Color de la fuente
                        position: 'top',
                        offset: 10, // Esto agrega un desplazamiento de 10 píxeles hacia arriba
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true // Comenzar el eje Y desde cero
                    }
                }
            }
        });
    </script>

</x-guest-layout>
