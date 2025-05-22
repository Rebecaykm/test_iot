@extends('adminlte::page')

@section('title', 'Clientes')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Clientes') }}</h1>
        <div class="col-md-4">
            <form action="{{ route('clients.index') }}" method="GET">
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
                        <h3 class="card-title m-0">Lista de Clientes</h3>
                        @can('create clients')
                            <div class="ml-auto">
                                <a href="{{ route('clients.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i> Nuevo Cliente
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
                                    <th class="py-3 text-secondary fw-normal">{{ __('Proyectos') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($clients as $client)
                                    <tr class="border-top">
                                        <td class="ps-4 py-3 fw-medium">{{ $client->code }}</td>
                                        <td class="py-3">{{ $client->name }}</td>
                                        <td class="py-3">{{ $client->projects->count() }}</td>
                                        <td class="py-3 text-center">
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                @can('edit clients')
                                                    <a href="{{ route('clients.edit', $client->id) }}"
                                                       class="btn btn-sm btn-primary"
                                                       title="Editar">
                                                        <i class="fas fa-edit mr-2"></i>
                                                        <span class="d-none d-sm-inline ml-1">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan
                                                @can('delete clients')
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger delete-client"
                                                            data-id="{{ $client->id }}"
                                                            data-name="{{ $client->name }}">
                                                        <i class="fas fa-trash"></i>
                                                        <span class="d-none d-sm-inline ml-1">{{ __('Eliminar') }}</span>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            @if(request()->has('search'))
                                                No se encontraron resultados para "{{ request('search') }}"
                                            @else
                                                No hay clientes registrados
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top">
                            <div class="text-muted">
                                MOSTRANDO {{ $clients->firstItem() ?? 0 }} -
                                {{ $clients->lastItem() ?? 0 }} DE {{ $clients->total() }}
                            </div>
                            <div>
                                {{ $clients->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro que deseas eliminar al cliente <strong id="clientName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .table-responsive {
            min-height: 300px;
        }

        .btn-group .btn {
            margin-right: 5px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        @media (max-width: 768px) {
            .btn-group .btn span {
                display: none;
            }

            .btn-group .btn i {
                margin-right: 0 !important;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
            }

            .card-header h3 {
                margin-bottom: 1rem;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('.delete-client').click(function() {
                const clientId = $(this).data('id');
                const clientName = $(this).data('name');

                $('#clientName').text(clientName);
                $('#deleteForm').attr('action', `/clients/${clientId}`);
                $('#deleteModal').modal('show');
            });
        });
    </script>
@stop
