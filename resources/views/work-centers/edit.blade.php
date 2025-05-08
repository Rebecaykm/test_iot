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

                    <!-- Acción y método del formulario cambiados -->
                    <form action="{{ route('work-centers.update', $workCenter->id) }}" method="POST">
                        @csrf
                        @method('PUT') <!-- Método PUT para actualización -->

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
                                               value="{{ old('name', $workCenter->line->name) }}"
                                               readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ip" class="font-weight-bold">IP <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('ip') is-invalid @enderror"
                                               id="ip" name="ip" value="{{ old('ip', $workCenter->ip) }}"
                                               placeholder="Ej: 127.0.0.1" required>
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
                                <i class="fas fa-save mr-1"></i> Actualizar Usuario
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
                        <div class="ml-auto">
                            <a href="{{ route('tags.create', ['work_center' => $workCenter->id]) }}"
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-plus mr-2"></i> Agregar Tag
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Dirección</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Longitud</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Descripción</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($tags as $tag)
                                    <tr class="border-top">
                                        <td>{{ $tag->address ?? '-' }}</td>
                                        <td>{{ $tag->long ?? '-' }}</td>
                                        <td>{{ $tag->description ?? '-' }}</td>
                                         <td class="py-3 text-center">
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                <!-- Botón Editar -->
                                                @can('edit users')
                                                    <a href="{{ route('tags.edit', $tag->id) }}"
                                                       class="btn btn-sm btn-primary d-flex align-items-center"
                                                       title="Editar">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan
                                                <!-- Botón Eliminar -->
                                                @can('delete users')
                                                    <form action="{{ route('tags.destroy', $tag->id) }}"
                                                          method="POST"
                                                          style="display: inline-block;"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este user?')">
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
                                        <td colspan="4" class="text-center text-muted py-4">
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
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        select option {
            padding: 5px;
        }

        .stations-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
    </style>
@stop
