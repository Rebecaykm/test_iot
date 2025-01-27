@extends('adminlte::page')

@section('title', 'Hourly Production Record')

@section('content_header')
    <h1>Hourly Production Record</h1>
@stop

@section('content')
    <div id="charts-container">
        @foreach ($chartData as $workName => $data)
            <div class="card">
                <div class="card-body">
                    <div class="chart-wrapper mb-5">
                        <h3 class="text-center text-uppercase">{{ $workName }}</h3>
                        <canvas id="chart-{{ Str::slug($workName, '-') }}"></canvas>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@stop

@section('css')
    {{-- Opcional: Agrega estilos si necesitas personalizar --}}
    <style>
        .chart-wrapper {
            width: 100%;
            height: 400px; /* Ajusta la altura que prefieras para la gráfica */
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Obtén los datos de $chartData en formato JSON
            const chartData = @json($chartData);

            // Itera sobre los datos y crea una gráfica para cada Work Name
            Object.keys(chartData).forEach(workName => {
                const chartConfig = chartData[workName];

                // Obtener el canvas correspondiente
                const ctx = document.getElementById(`chart-${workName.replace(/\s+/g, '-')}`);

                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar', // Tipo de gráfica (puedes cambiarlo a 'line', 'pie', etc.)
                        data: {
                            labels: chartConfig.labels, // Etiquetas en el eje X
                            datasets: chartConfig.datasets // Conjunto de datos
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false, // Permite que la gráfica ocupe el tamaño completo de su contenedor
                            plugins: {
                                legend: {
                                    position: 'top', // Posición de la leyenda
                                },
                                title: {
                                    display: true,
                                    text: `Production Data for ${workName}`
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true // Comienza en 0 en el eje Y
                                }
                            }
                        }
                    });
                }
            });
        });
    </script>
@stop
