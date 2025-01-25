@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <canvas id="consequuntur"></canvas>
        </div>
    </div>
@stop

@section('css')

@stop

@section('js')

    <script>
        const data = {
            labels: [
                "25-01-2024 09:00",
                "25-01-2024 10:00",
                "25-01-2024 11:00",
                "25-01-2024 12:00",
                "25-01-2024 13:00",
                "25-01-2024 14:00",
                "25-01-2024 15:00",

            ],
            datasets: [{
                    label: '145405',
                    data: [
                        75, 95, 115, 135, 155, 175, 195
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)', // color de fondo
                    borderColor: 'rgba(54, 162, 235, 1)', // color del borde
                    borderWidth: 1
                }
            ]
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
                        type: 'time',
                        time: {
                            unit: 'hour',
                            tooltipFormat: 'll HH:mm',
                            displayFormats: {
                                hour: 'HH:mm',
                            }
                        },
                        title: {
                            display: true,
                            text: 'Fecha y Hora'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Valor'
                        }
                    }
                }
            }
        };

        const consequuntur = new Chart(
            document.getElementById('consequuntur'),
            config
        );
    </script>
@stop
