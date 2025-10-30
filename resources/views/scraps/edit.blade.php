@extends('adminlte::page')

@section('title', 'Editar Scrap')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark"><i class="fas fa-edit mr-2"></i>Editar Scrap</h1>
        <a href="{{ route('scraps.index') }}" class="btn btn-secondary">
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
                            <i class="fas fa-info-circle mr-2"></i>Editar Información del Scrap
                        </h3>
                    </div>

                    <form action="{{ route('scraps.update', $scrap->id) }}" method="POST">
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
                                        <label for="code" class="font-weight-bold">Código <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control @error('code') is-invalid @enderror"
                                               id="code"
                                               name="code"
                                               value="{{ old('code', $scrap->code) }}"
                                               placeholder="Ej: SCR-001, PLAST-002..."
                                               required>
                                        @error('code')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control @error('name') is-invalid @enderror"
                                               id="name"
                                               name="name"
                                               value="{{ old('name', $scrap->name) }}"
                                               placeholder="Ej: Scrap de Plástico ABS, Retazos de Tela..."
                                               required>
                                        @error('name')
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
                                        <label for="type_scrap_id" class="font-weight-bold">Tipo de Scrap <span class="text-danger">*</span></label>
                                        <select class="form-control @error('type_scrap_id') is-invalid @enderror"
                                                id="type_scrap_id"
                                                name="type_scrap_id"
                                                required>
                                            <option value="">Seleccione un tipo de scrap...</option>
                                            @foreach($typeScraps as $type)
                                                <option value="{{ $type->id }}" {{ old('type_scrap_id', $scrap->type_scrap_id) == $type->id ? 'selected' : '' }}>
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('type_scrap_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Información del Scrap</label>
                                        <div class="border rounded p-3 bg-light">
                                            <div class="small text-muted">
                                                <div><strong>Creado:</strong> {{ $scrap->created_at->format('d/m/Y H:i') }}</div>
                                                <div><strong>Última actualización:</strong> {{ $scrap->updated_at->format('d/m/Y H:i') }}</div>
                                                @if($scrap->typeScrap)
                                                    <div><strong>Tipo actual:</strong> {{ $scrap->typeScrap->name }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description" class="font-weight-bold">Descripción</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                                  id="description"
                                                  name="description"
                                                  rows="3"
                                                  placeholder="Descripción detallada del scrap...">{{ old('description', $scrap->description) }}</textarea>
                                        @error('description')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                            <div>
                                <a href="{{ route('scraps.index') }}" class="btn btn-default mr-2">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </a>
                            </div>
                            <div>
                                <button type="reset" class="btn btn-default mr-2">
                                    <i class="fas fa-undo mr-1"></i> Restablecer
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Actualizar Scrap
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

        .bg-light {
            background-color: #f8f9fa !important;
        }

        .text-muted div {
            margin-bottom: 0.25rem;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus en el primer campo al cargar la página
            document.getElementById('code').focus();

            // Confirmación antes de restablecer el formulario
            const resetButton = document.querySelector('button[type="reset"]');
            resetButton.addEventListener('click', function(e) {
                if (!confirm('¿Está seguro de que desea restablecer todos los campos a sus valores originales?')) {
                    e.preventDefault();
                }
            });

            // Validación en tiempo real para campos de texto
            const codeInput = document.getElementById('code');
            const nameInput = document.getElementById('name');

            codeInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    this.classList.remove('is-invalid');
                }
            });

            nameInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    </script>
@stop
