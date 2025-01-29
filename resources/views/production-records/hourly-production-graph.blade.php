@extends('adminlte::page')

@section('title', 'Hourly Production Record')

@section('content_header')
<!-- <h1>Hourly Production Record</h1> -->
@stop

@section('content')
<div id="charts-container">
    @foreach ($chartData as $chart)
    <div class="card">
        <div class="card-body">
            <div class="chart-wrapper mb-5">
                <h3 class="text-center text-uppercase">{{ $chart['work_name'] }} - {{ $chart['part_number'] }}</h3>
                <canvas id="{{ $chart['chart_id'] }}"></canvas>
            </div>
        </div>
    </div>
    @endforeach
</div>
@stop

@section('css')
<style>
    .chart-wrapper {
        width: 100%;
        height: 400px;
    }

    canvas {
        width: 100% !important;
        height: 100% !important;
    }
</style>
@stop

@section('js')
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartData = @json($chartData);
            console.log(chartData);
            chartData.forEach(chart => {
                const ctx = document.getElementById(chart.chart_id); // Aquí utilizas chart.chart_id

                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar', // Tipo de gráfica
                        data: {
                            labels: chart.labels, // Etiquetas en el eje X
                            datasets: chart.datasets // Datos
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: `Production Data for ${chart.work_name} - ${chart.part_number}`
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    stacked: true
                                }
                            }
                        }
                    });
                }
            });
        });
    </script>
@stop
