@extends('adminlte::page')

@section('title', 'Editar Registro de Scrap')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark"><i class="fas fa-edit mr-2"></i>Editar Registro de Scrap</h1>
        <a href="{{ route('scrap-records.index') }}" class="btn btn-secondary">
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
                            <i class="fas fa-info-circle mr-2"></i>Editar Información del Registro de Scrap
                        </h3>
                    </div>

                    <form action="{{ route('scrap-records.update', $scrapRecord->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <h5 class="alert-heading">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Error de Validación
                                    </h5>
                                    <ul class="mb-0 pl-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="part_number_id" class="font-weight-bold">Número de Parte <span class="text-danger">*</span></label>
                                        <select class="form-control @error('part_number_id') is-invalid @enderror"
                                                id="part_number_id"
                                                name="part_number_id"
                                                required>
                                            <option value="">Seleccione un número de parte...</option>
                                            @foreach($partNumbers as $partNumber)
                                                <option value="{{ $partNumber->id }}" {{ old('part_number_id', $scrapRecord->part_number_id) == $partNumber->id ? 'selected' : '' }}>
                                                    {{ $partNumber->number }} - {{ $partNumber->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('part_number_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="scrap_id" class="font-weight-bold">Scrap <span class="text-danger">*</span></label>
                                        <select class="form-control @error('scrap_id') is-invalid @enderror"
                                                id="scrap_id"
                                                name="scrap_id"
                                                required>
                                            <option value="">Seleccione un tipo de scrap...</option>
                                            @foreach($scraps as $scrap)
                                                <option value="{{ $scrap->id }}" {{ old('scrap_id', $scrapRecord->scrap_id) == $scrap->id ? 'selected' : '' }}>
                                                    {{ $scrap->code }} - {{ $scrap->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('scrap_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="quantity" class="font-weight-bold">Cantidad <span class="text-danger">*</span></label>
                                        <input type="number"
                                               min="0"
                                               step="0.01"
                                               class="form-control @error('quantity') is-invalid @enderror"
                                               id="quantity"
                                               name="quantity"
                                               value="{{ old('quantity', $scrapRecord->quantity) }}"
                                               placeholder="Ej: 10, 25.5, 100..."
                                               required>
                                        @error('quantity')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Ingrese la cantidad de scrap generado. Use decimales si es necesario.
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Información del Registro</label>
                                        <div class="border rounded p-3 bg-light">
                                            <div class="small text-muted">
                                                <div><strong>Creado:</strong> {{ $scrapRecord->created_at->format('d/m/Y H:i') }}</div>
                                                <div><strong>Última actualización:</strong> {{ $scrapRecord->updated_at->format('d/m/Y H:i') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                            <div>
                                <a href="{{ route('scrap-records.index') }}" class="btn btn-default mr-2">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </a>
                            </div>
                            <div>
                                <button type="reset" class="btn btn-default mr-2">
                                    <i class="fas fa-undo mr-1"></i> Restablecer
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Actualizar Registro
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .125);
            background-color: #f8f9fa;
        }

        .card-title {
            color: #495057;
            font-weight: 600;
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .alert-danger {
            border-left: 4px solid #dc3545;
        }

        .alert-heading {
            font-weight: 600;
            font-size: 1.1em;
        }

        .form-group label {
            margin-bottom: 0.5rem;
        }

        .btn-default {
            background-color: #f8f9fa;
            border-color: #ddd;
            color: #444;
        }

        .btn-default:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .invalid-feedback {
            display: block;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .form-text {
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus en el primer campo al cargar la página
            document.getElementById('part_number_id').focus();

            // Validación en tiempo real para cantidad
            const quantityInput = document.getElementById('quantity');
            quantityInput.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });

            // Confirmación antes de restablecer el formulario
            const resetButton = document.querySelector('button[type="reset"]');
            resetButton.addEventListener('click', function(e) {
                if (!confirm('¿Está seguro de que desea restablecer todos los campos a sus valores originales?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@stop
