@extends('adminlte::page')

@section('title', 'Áreas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Áreas') }}</h1>
        <div class="col-md-4">
            <form action="{{ route('areas.index') }}" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar..."
                           value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h3 class="card-title m-0">Lista de Áreas</h3>
                        @can('create areas')
                            <div class="ml-auto"> <!-- Clase ml-auto añadida aquí -->
                                <a href="{{ route('areas.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i> Agregar Área
                                </a>
                            </div>
                        @endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">{{ __('Código') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Nombre') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Descripción') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Creación') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Actualización') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($areas as $area)
                                    <tr class="border-top">
                                        <td class="py-3">{{ $area->code ?? '' }}</td>
                                        <td class="py-3">{{ $area->name ?? '' }}</td>
                                        <td class="py-3">{{ $area->description ?? '' }}</td>
                                        <td class="py-3 text-muted">
                                            {{ optional($area->created_at)->format('d-m-Y H:i') ?? '' }}
                                        </td>
                                        <td class="py-3 text-muted">
                                            {{ optional($area->updated_at)->format('d-m-Y H:i') ?? '' }}
                                        </td>
                                        <td class="py-3">
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                <!-- Botón Editar -->
                                                @can('edit areas')
                                                    <a href="{{ route('areas.edit', $area->id) }}"
                                                       class="btn btn-sm btn-primary d-flex align-items-center"
                                                       title="Editar">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan
                                                <!-- Botón Eliminar -->
                                                @can('delete areas')
                                                    <form action="{{ route('areas.destroy', $area->id) }}"
                                                          method="POST"
                                                          style="display: inline-block;"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar esta línea?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-danger d-flex align-items-center">
                                                            <i class="fas fa-trash mr-1"></i>
                                                            <span class="d-none d-sm-inline">{{ __('Eliminar') }}</span>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No hay areas por mostrar.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 d-flex justify-content-end">
                        {{ $areas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Estilos para la paginación */
        .pagination {
            margin-bottom: 0;
        }

        .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        /* Estilos para badges */
        .badge {
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Estilos para botones de acción */
        .btn-group {
            white-space: nowrap;
        }

        .btn-group .btn {
            margin-right: 0.3rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .btn-sm i {
            font-size: 0.8rem;
        }

        /* Responsive para móviles */
        @media (max-width: 576px) {
            .btn-group .btn span {
                display: none;
            }

            .btn-sm i {
                margin-right: 0 !important;
            }
        }

        /* Estilos para el buscador */
        .input-group {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .form-control {
            border-radius: 0.25rem 0 0 0.25rem;
        }

        .input-group-append .btn {
            border-radius: 0 0.25rem 0.25rem 0;
        }
    </style>
@stop
