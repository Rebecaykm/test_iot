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
                            <div class="ml-auto">
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
                                            {{ optional($area->created_at)->format('d-m-Y H:i') }}
                                        </td>
                                        <td class="py-3 text-muted">
                                            {{ optional($area->updated_at)->format('d-m-Y H:i') }}
                                        </td>
                                        <td class="py-3 text-center">
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                @can('edit areas')
                                                    <a href="{{ route('areas.edit', $area->id) }}"
                                                       class="btn btn-sm btn-primary d-flex align-items-center"
                                                       title="Editar">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan
                                                @can('delete areas')
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger d-flex align-items-center delete-area"
                                                            data-id="{{ $area->id }}"
                                                            data-name="{{ $area->name }}">
                                                        <i class="fas fa-trash mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Eliminar') }}</span>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No hay áreas por mostrar.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center w-100 p-3">
                        <div class="text-muted">
                            MOSTRANDO {{ $areas->firstItem() ?? 0 }} -
                            {{ $areas->lastItem() ?? 0 }} DE {{ $areas->total() }}
                        </div>
                        <div>
                            {{ $areas->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de confirmación de eliminación --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Eliminar Área</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        ¿Estás seguro que deseas eliminar el área <strong id="areaName"></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <style>
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

        @media (max-width: 576px) {
            .btn-group .btn span {
                display: none;
            }

            .btn-sm i {
                margin-right: 0 !important;
            }
        }

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

@section('js')
    <script>
        $(document).ready(function () {
            $('.delete-area').click(function () {
                const areaId = $(this).data('id');
                const areaName = $(this).data('name');
                $('#areaName').text(areaName);
                $('#deleteForm').attr('action', `/areas/${areaId}`);
                $('#deleteModal').modal('show');
            });
        });
    </script>
@stop
