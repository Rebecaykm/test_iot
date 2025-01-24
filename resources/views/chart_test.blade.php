@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <canvas id="mk05Chart"></canvas>
        </div>
    </div>
@stop

@section('css')
    {{-- No necesitas agregar nada extra si usas el CDN --}}
    {{-- Si prefieres usar npm, ya está instalado --}}
@stop

@section('js')

    <script>
        // Aquí empiezas a preparar tus datos
        const data = {
            labels: [
                "23-01-2024 08:00",
                "23-01-2024 10:00",
                "23-01-2024 12:00",
                "23-01-2024 14:00",
                "23-01-2024 16:00",
                "23-01-2024 18:00",
                "23-01-2024 20:00",
                "23-01-2024 22:00",
                "23-01-2024 24:00",
                "24-01-2024 02:00",
                "24-01-2024 04:00",
                "24-01-2024 06:00",
                "24-01-2024 08:00",
            ],
            datasets: [{
                label: 'DGH97065Z',
                data: [
                    0,
                    50,
                    150,
                    200,
                    350,
                    400,
                    400,
                    400,
                    650,
                    800,
                    850,
                    1000,
                    1100
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.2)', // color de fondo
                borderColor: 'rgba(54, 162, 235, 1)', // color del borde
                borderWidth: 1
            }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'DGH97065Z'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        // Se crea la gráfica en el canvas con id 'mk05Chart'
        const mk05Chart = new Chart(
            document.getElementById('mk05Chart'),
            config
        );
    </script>
@stop
