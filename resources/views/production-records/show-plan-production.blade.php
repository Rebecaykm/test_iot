@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <!-- <h1>Dashboard</h1> -->
@stop

@section('content')
    <div class="row">
        @foreach ($groupedByWorkCenter as $workCenterName => $dates)
            @foreach ($dates as $plannedDate => $shifts)
                @foreach ($shifts as $shiftName => $records)
                    <div class="col-md-6"> <!-- Usamos col-md-6 para 2 cards por fila -->
                        <div class="card card-outline card-primary mb-4 shadow-sm rounded"> <!-- Agregamos la clase shadow-sm para una sombra sutil -->
                        <div class="card-header text-white"> <!-- Color de fondo y texto -->
                            <div class="d-flex justify-content-between w-100 align-items-center"> <!-- Usamos flexbox para alinearlo en una fila -->
                                    <!-- Badge del workCenterName -->
                                    <h4 class="mr-3 mb-0">
                                        <span class="badge bg-primary text-dark">{{ $workCenterName }}</span>
                                    </h4>

                                    <!-- Fechas y turnos -->
                                    <div class="card-tools d-flex">
                                        <h4 class="mr-3 mb-0">
                                            <span class="badge bg-primary text-dark">{{ $plannedDate }}</span>
                                        </h4>
                                        <h4 class="mb-0">
                                            <span class="badge bg-secondary text-dark">{{ $shiftName }}</span>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-light">
                                        <tr class="text-uppercase">
                                            <th>{{ __(('Número de Parte')) }}</th>
                                            <th class="text-center">{{ __('Cantidad Planeada') }}</th>
                                            <th class="text-center">{{ __('Cantidad Producida') }}</th>
                                            <th class="text-center">{{ __('Diferencia') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($records as $record)
                                            <tr>
                                                <td>{{ $record['part_number'] }}</td>
                                                <td class="text-center">{{ $record['planned_quantity'] }}</td>
                                                <td class="text-center">{{ $record['produced_quantity'] }}</td>
                                                <td class="text-center">{{ $record['difference'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        @endforeach
    </div>
@stop

@section('css')
{{-- Puedes agregar aquí estilos adicionales si lo necesitas --}}
@stop

@section('js')
<script>
    console.log("Hi, I'm using the Laravel-AdminLTE package!");
</script>
@stop
