@extends('adminlte::page')

@section('title', 'Estaciones de Trabajo')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Estaciones de Trabajo') }}</h1>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h3 class="card-title m-0">Lista de Estaciones</h3>
                        <div>
                            <!-- Aquí puedes añadir botones de acción si lo necesitas -->
                            <!-- Por ejemplo: <a href="route('workcenters.create')" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Nueva Estación</a> -->
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">{{ __('Línea') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Número') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Nombre') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Creación') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Actualización') }}</th>
{{--                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>--}}
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($workCenters as $workCenter)
                                    <tr class="border-top">
                                        <td class="ps-4 py-3">
                                            @if ($workCenter->line_id && isset($workCenter->line_id->name))
                                                <span class="badge rounded-pill bg-info text-white px-3 py-2">{{ $workCenter->line_id->name }}</span>
                                            @else
                                                <span class="badge rounded-pill bg-secondary px-3 py-2">No Asignado</span>
                                            @endif
                                        </td>
                                        <td class="py-3 fw-medium">{{ $workCenter->number }}</td>
                                        <td class="py-3">{{ $workCenter->name }}</td>
                                        <td class="py-3 text-muted">{{ $workCenter->created_at->format('d-m-Y H:i') }}</td>
                                        <td class="py-3 text-muted">{{ $workCenter->updated_at->format('d-m-Y H:i') }}</td>
{{--                                        <td class="py-3 text-center">--}}
{{--                                            <div class="btn-group">--}}
{{--                                                <a href="#" class="btn btn-sm btn-outline-primary rounded me-1">--}}
{{--                                                    <i class="fas fa-eye"></i>--}}
{{--                                                </a>--}}
{{--                                                <a href="#" class="btn btn-sm btn-outline-secondary rounded me-1">--}}
{{--                                                    <i class="fas fa-edit"></i>--}}
{{--                                                </a>--}}
{{--                                                <button type="button" class="btn btn-sm btn-outline-danger rounded">--}}
{{--                                                    <i class="fas fa-trash"></i>--}}
{{--                                                </button>--}}
{{--                                            </div>--}}
{{--                                        </td>--}}
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 d-flex justify-content-end">
                        {{ $workCenters->links() }}
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

        /* Mejorar los botones de acción */
        .btn-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            padding: 0;
        }

        /* Asegurar que las celdas tengan suficiente padding */
        .table td, .table th {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        /* Excepción para la primera celda */
        .ps-4 {
            padding-left: 1.5rem !important;
        }
    </style>
@stop
