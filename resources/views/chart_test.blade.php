<x-guest-layout>
    <div class="grid grid-cols-1 gap-4">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-6 flex flex-col h-full">
                <div class="chart-wrapper mb-5 flex-1">

                <canvas id="myChart" width="400" height="400"></canvas>

                <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.0/dist/chart.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

            </div>
        </div>
    </div>
    <script>
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo'],
                datasets: [{
                    label: 'Ventas del 2025',
                    data: [12, 19, 3, 5, 2],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        // clamp:true,
                        align: 'end',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        color: 'black',
                        position: 'top',
                        offset: 10,
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
