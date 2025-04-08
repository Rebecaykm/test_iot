@extends('adminlte::page')

@section('title', 'Números de Parte')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Números de Partes') }}</h1>
    </div>
@stop

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h3 class="card-title m-0">Lista de Números de Partes</h3>
                        <div>
                            <!-- Aquí puedes añadir botones de acción si lo necesitas -->
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">{{ __('Estación') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Número') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Nombre') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Tiempo Ciclo') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Eficiencia') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Estado') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Creación') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Actualización') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($partNumbers as $partNumber)
                                    <tr class="border-top">
                                        <td class="ps-4 py-3">{{ $partNumber->workCenter->name ?? 'No Asignado' }}</td>
                                        <td class="py-3 fw-medium">{{ $partNumber->number }}</td>
                                        <td class="py-3">{{ $partNumber->name }}</td>
                                        <td class="py-3 text-center">
                                            @if($partNumber->production_rate != 0)
                                                <span class="badge rounded-pill bg-primary px-3 py-2">{{ number_format(60 / $partNumber->production_rate, 2) }}</span>
                                            @else
                                                <span class="badge rounded-pill bg-primary px-3 py-2">0.00</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="badge rounded-pill bg-secondary px-3 py-2">{{ $partNumber->efficiency }}</span>
                                        </td>
                                        <td class="py-3 text-center">
                                            @if($partNumber->is_obsolete)
                                                <span class="badge rounded-pill bg-danger px-3 py-2">Obsoleto</span>
                                            @else
                                                <span class="badge rounded-pill bg-success px-3 py-2">Activo</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-muted">{{ $partNumber->created_at->format('d-m-Y H:i') }}</td>
                                        <td class="py-3 text-muted">{{ $partNumber->updated_at->format('d-m-Y H:i') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 d-flex justify-content-end">
                        {{ $partNumbers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@section('css')
    <style>
        /* Estilos para mejorar la apariencia de la paginación de Laravel con Bootstrap 4 (AdminLTE) */
        .pagination {
            margin-bottom: 0;
        }

        .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        .badge {
            font-weight: 500;
            font-size: 0.85rem;
        }
    </style>
@stop
