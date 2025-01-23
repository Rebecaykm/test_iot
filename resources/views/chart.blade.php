@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>Gráfico de Barras - Ejemplo Básico</h3>
        </div>
        <div class="card-body">
            <canvas id="myChart"></canvas>
        </div>
    </div>
@stop

@section('css')
    {{-- Aquí puedes agregar tus estilos adicionales si es necesario --}}
    <style>
        .card {
            margin-top: 20px;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Crear los datos del gráfico
            const ctx = document.getElementById('myChart').getContext('2d');
            const myChart = new Chart(ctx, {
                type: 'bar', // Tipo de gráfico (barra)
                data: {
                    labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo'], // Etiquetas (ejes X)
                    datasets: [{
                        label: 'Ventas',
                        data: [12, 19, 3, 5, 2], // Datos (ejes Y)
                        backgroundColor: 'rgba(0, 123, 255, 0.2)', // Color de las barras
                        borderColor: 'rgba(0, 123, 255, 1)', // Color del borde de las barras
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, // Hace que el gráfico sea responsivo
                    scales: {
                        y: {
                            beginAtZero: true // Inicia el eje Y desde 0
                        }
                    }
                }
            });
        });
    </script>
@stop
