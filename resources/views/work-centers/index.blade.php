@extends('adminlte::page')

@section('title', 'Estaciones de Trabajo')

@section('content_header')
<div class="card-header d-flex justify-content-between">
    <h3 class="card-title text-uppercase">{{ __('Estaciones de Trabajo') }}</h3>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header text-uppercase">
                    <h3 class="card-title">Estaciones de Trabajo</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead class="text-uppercase">
                            <tr>
                                <th>{{ __('Línea') }}</th>
                                <th>{{ __('Número') }}</th>
                                <th>{{ __('Nombre') }}</th>
                                <th>{{ __('Fecha de Creación') }}</th>
                                <th>{{ __('Fecha de Actualización') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($workCenters as $workCenter)
                            <tr>
                                <td>{{ $workCenter->line_id->name ?? 'No Asignado' }}</td>
                                <td>{{ $workCenter->number }}</td>
                                <td>{{ $workCenter->name }}</td>
                                <td>{{ $workCenter->created_at->format('d-m-Y H:i') }}</td>
                                <td>{{ $workCenter->updated_at->format('d-m-Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix d-flex justify-content-end">
                    {{ $workCenters->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
