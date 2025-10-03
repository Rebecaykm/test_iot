@extends('adminlte::page')

@section('title', 'Editar Estación')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Editar Estación</h1>
        <a href="{{ route('work-centers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            Información de la Estación
                        </h3>
                    </div>

                    <form action="{{ route('work-centers.update', $workCenter->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="number">Número</label>
                                        <input type="text" class="form-control"
                                               id="number" name="number"
                                               value="{{ old('number', $workCenter->number) }}"
                                               readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Nombre</label>
                                        <input type="text" class="form-control"
                                               id="name" name="name"
                                               value="{{ old('name', $workCenter->name) }}"
                                               readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Línea</label>
                                        <input type="text" class="form-control"
                                               id="linea" name="línea"
                                               value="{{ old('name', $workCenter->line?->name) ?? '' }}"
                                               readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ip" class="font-weight-bold">IP <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('ip') is-invalid @enderror"
                                               id="ip" name="ip" value="{{ old('ip', $workCenter->ip) }}"
                                               placeholder="Ej: 127.0.0.1" >
                                        @error('ip')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="card-footer bg-white d-flex justify-content-end py-3">
                            <button type="reset" class="btn btn-default mr-2">
                                <i class="fas fa-undo mr-1"></i> Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Actualizar Estación
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>


        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title m-0">Listado de Tags</h3>
                        @can('view tags')
                            <div class="ml-auto">
                                <a href="{{ route('tags.create', ['work_center' => $workCenter->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-2"></i> Agregar Tag
                                </a>
                            </div>
                        @endcan
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Dirección</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Longitud</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Tipo de Tag</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Descripción</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($tags as $tag)
                                    <tr class="border-top">
                                        <td class="py-3">{{ $tag->address ?? '-' }}</td>
                                        <td class="py-3">{{ $tag->long ?? '-' }}</td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill bg-primary text-white px-3 py-2 mb-1">
                                                {{ $tag->tagType->name }}
                                            </span>
                                        </td>
                                        <td class="py-3">{{ $tag->description ?? '-' }}</td>
                                        <td class="py-3">
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                <!-- Botón Editar -->
                                                @can('edit tags')
                                                    <a href="{{ route('tags.edit', $tag->id) }}"
                                                       class="btn btn-sm btn-primary d-flex align-items-center"
                                                       title="Editar">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan
                                                <!-- Botón Eliminar -->
                                                @can('delete tags')
                                                    <form action="{{ route('tags.destroy', $tag->id) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este tag?')">
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
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No hay tags asociadas por mostrar.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
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
