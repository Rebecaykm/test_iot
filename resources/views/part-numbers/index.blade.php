@extends('adminlte::page')

@section('title', 'Números de Parte')

@section('content_header')
<div class="card-header d-flex justify-content-between">
    <h3 class="card-title text-uppercase">{{ __('Números de Partes') }}</h3>
</div>
@stop

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header text-uppercase">
                    <h3 class="card-title">Números de Partes</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead class="text-uppercase">
                            <tr>
                                <th>{{ __('Estación') }}</th>
                                <th>{{ __('Número') }}</th>
                                <th>{{ __('Nombre') }}</th>
                                <th class="text-center">{{ __('Tiempo Ciclo') }}</th>
                                <th class="text-center">{{ __('Eficiencia') }}</th>
                                <th class="text-center">{{ __('Estado') }}</th>
                                <th>{{ __('Fecha de Creación') }}</th>
                                <th>{{ __('Fecha de Actualización') }}</th>
                            </tr>
                        </thead>
                        <tbody class="">
                            @foreach ($partNumbers as $partNumber)
                            <tr>
                                <td>{{ $partNumber->workCenter->name ?? 'No Asignado' }}</td>
                                <td>{{ $partNumber->number }}</td>
                                <td>{{ $partNumber->name }}</td>
                                <td class="text-center">
                                    @if($partNumber->production_rate != 0)
                                    <span class="badge bg-primary px-4 py-2 fs-6">{{ number_format(60 / $partNumber->production_rate, 2) }}</span>
                                    @else
                                    <span class="badge bg-primary px-4 py-2 fs-6">0.00</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary px-4 py-2 fs-6">{{ $partNumber->efficiency }}</span>
                                </td>
                                <td class="text-center">
                                    @if($partNumber->is_obsolete)
                                    <span class="badge bg-danger px-4 py-2 fs-6">Obsoleto</span>
                                    @else
                                    <span class="badge bg-success px-4 py-2 fs-6">Activo</span>
                                    @endif
                                </td>


                                <td>{{ $partNumber->created_at->format('d-m-Y H:i') }}</td>
                                <td>{{ $partNumber->updated_at->format('d-m-Y H:i') }}</td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix d-flex justify-content-end">
                    {{ $partNumbers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@stop
