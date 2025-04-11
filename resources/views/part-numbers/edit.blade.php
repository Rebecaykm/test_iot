@extends('adminlte::page')

@section('title', 'Editar Número de Parte')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Editar Número de Parte</h1>
        <a href="{{ route('part-numbers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <!-- Formulario de edición -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Información del Número de Parte</h3>
                    </div>

                    <form action="{{ route('part-numbers.update', $partNumber->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row">
                                <!-- Campo Estación -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="work_center_id">Estación</label>
                                        <input type="text" class="form-control"
                                               id="work_center_id" name="work_center_id"
                                               value="{{ old('work_center_id', $partNumber->workCenter->name) }}"
                                               readonly>
                                    </div>
                                </div>

                                <!-- Campo Número -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="number">Número</label>
                                        <input type="text" class="form-control"
                                               id="number" name="number"
                                               value="{{ old('number', $partNumber->number) }}"
                                               readonly>
                                    </div>
                                </div>

                                <!-- Campo Nombre -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="name">Nombre</label>
                                        <input type="text" class="form-control"
                                               id="name" name="name"
                                               value="{{ old('name', $partNumber->name) }}"
                                               readonly>
                                    </div>
                                </div>

                                <!-- Campo Clase -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="item_class_id">Clase</label>
                                        <input type="text" class="form-control"
                                               id="item_class_id" name="item_class_id"
                                               value="{{ old('item_class_id', $partNumber->itemClass->abbreviation) }}"
                                               readonly>
                                    </div>
                                </div>

                                <!-- Campo Tasa de Producción -->
                                <div class="col-md-4">
                                    <div class="form-group"> <!-- h-100 para ocupar altura completa -->
                                        <label for="production_rate" class="w-100">Tasa de Producción</label>
                                        <!-- w-100 para ancho completo -->
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <!-- Flex para centrar vertical y horizontalmente -->
                                            <span
                                                class="badge rounded-pill bg-primary w-100 py-2">{{ number_format(60 / $partNumber->production_rate, 2) }}</span>
                                        </div>
                                    </div>
                                </div>


                                <!-- Campo Estado -->
                                <div class="col-md-4">
                                    <div class="form-group"> <!-- h-100 para ocupar altura completa -->
                                        <label for="status" class="w-100">Estado</label>
                                        <!-- w-100 para ancho completo -->
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <!-- Flex para centrar vertical y horizontalmente -->
                                            @if($partNumber->is_obsolete)
                                                <span class="badge rounded-pill bg-danger w-100 py-2">Obsoleto</span>
                                            @else
                                                <span class="badge rounded-pill bg-success w-100 py-2">Activo</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Campo Eficiencia -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="efficiency">Eficiencia (%)</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control"
                                               id="efficiency" name="efficiency"
                                               value="{{ old('efficiency', $partNumber->efficiency) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de guardar -->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sección de imágenes asociadas -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card card-secondary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title m-0">Imágenes asociadas</h3>
                        <div class="ml-auto">
                            <a href="{{ route('visual-aids.create', ['part_number' => $partNumber->id]) }}"
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Agregar Imagen
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Imagen</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Descripción</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Estado</th>
                                    <th class="ps-4 py-3 text-secondary fw-normal">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($visualAids as $visualAid)
                                    <tr class="border-top">
                                        <td>
                                            <img src="{{ asset('storage/' . $visualAid->path) }}"
                                                 alt="{{ $visualAid->alt_text }}"
                                                 width="50">
                                        </td>
                                        <td>{{ $visualAid->alt_text ?? 'N/A' }}</td>
                                        <td class="py-3">
                                            @if($visualAid->is_active)
                                                <span class="badge rounded-pill bg-success px-3 py-2">Activa</span>
                                            @else
                                                <span class="badge rounded-pill bg-secondary px-3 py-2">Inactiva</span>
                                            @endif
                                        </td>
                                        <td>
                                            <!-- Botón Eliminar -->
                                            <form
                                                action="{{ route('visual-aids.destroy', [$visualAid->id, $partNumber->id]) }}"
                                                method="POST"
                                                style="display: inline-block;"
                                                onsubmit="return confirm('¿Estás seguro de eliminar este número de parte?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-danger d-flex align-items-center delete-btn">
                                                    <i class="fas fa-trash mr-1"></i> {{ __('Eliminar') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No hay imágenes asociadas por mostrar.
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
        .status-badge-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .status-badge {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-badge .badge {
            width: 100%;
            max-width: 100%;
            font-size: 1rem;
            padding: 0.5rem 0;
        }
    </style>
@stop
