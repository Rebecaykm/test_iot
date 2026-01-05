@extends('adminlte::page')

@section('title', 'Crear Línea')

@section('content_header')
    <h1>{{ __('Crear Línea') }}</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body">
            <form action="{{ route('lines.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label fw-bold text-secondary">{{ __('Nombre') }} *</label>
                        <input type="text" name="name" id="name"
                            class="form-control border-light-subtle @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required placeholder="Ej: Línea de Ensamblaje Principal">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="area_id" class="form-label fw-bold text-secondary">{{ __('Área') }}</label>
                        <select name="area_id" id="area_id"
                            class="form-control select2 border-light-subtle @error('area_id') is-invalid @enderror">
                            <option value="">{{ __('Seleccione un área') }}</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('area_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="color" class="form-label fw-bold text-secondary">{{ __('Color') }} *</label>
                        <select name="color" id="color"
                            class="form-control select2 border-light-subtle @error('color') is-invalid @enderror"
                            required>
                            <option value="">{{ __('Seleccione un color') }}</option>
                            @foreach([
                                '#c0392b' => 'Rojo Alizarina',
                                '#e74c3c' => 'Rojo Cinnabar',
                                '#9b59b6' => 'Púrpura Amatista',
                                '#8e44ad' => 'Violeta Studio',
                                '#2980b9' => 'Azul Belize Hole',
                                '#3498db' => 'Azul Curioso',
                                '#1abc9c' => 'Turquesa',
                                '#16a085' => 'Verde Mar',
                                '#27ae60' => 'Verde Nephritis',
                                '#2ecc71' => 'Verde Esmeralda',
                                '#f1c40f' => 'Amarillo Girasol',
                                '#f39c12' => 'Naranja',
                                '#e67e22' => 'Naranja Zanahoria',
                                '#d35400' => 'Calabaza',
                            ] as $hex => $name)
                                <option value="{{ $hex }}" {{ old('color') == $hex ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('color')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="description" class="form-label fw-bold text-secondary">{{ __('Descripción') }}</label>
                        <textarea name="description" id="description" rows="3"
                            class="form-control border-light-subtle @error('description') is-invalid @enderror"
                            placeholder="{{ __('Descripción detallada de la línea') }}">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-secondary">{{ __('Estaciones de Trabajo Disponibles') }}</label>
                        <div class="stations-container border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <div class="row">
                                @foreach($workCenters->chunk(ceil($workCenters->count() / 3)) as $column)
                                    <div class="col-md-4">
                                        @foreach($column as $workCenter)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="work_centers[]"
                                                    id="wc_{{ $workCenter->id }}"
                                                    value="{{ $workCenter->id }}"
                                                    {{ is_array(old('work_centers')) && in_array($workCenter->id, old('work_centers')) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="wc_{{ $workCenter->id }}">
                                                    {{ $workCenter->number }} - {{ $workCenter->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('work_centers')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('lines.index') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times me-2"></i> {{ __('Cancelar') }}
                    </a>
                    <button type="reset" class="btn btn-outline-primary rounded-3">
                        <i class="fas fa-undo me-2"></i> {{ __('Limpiar') }}
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save me-2"></i> {{ __('Guardar Línea') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <!-- Fuente Google Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Aplicar fuente a todo el sistema */
        body,
        .main-header,
        .main-sidebar,
        .content-wrapper,
        .card,
        .btn,
        .form-control,
        .form-select,
        .form-label,
        .table,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Roboto', sans-serif !important;
        }

        /* Estilos para formularios */
        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        .form-control {
            border-radius: 8px !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.95rem !important;
            border: 1px solid #e0e0e0 !important;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        .form-label {
            font-size: 0.9rem !important;
            margin-bottom: 0.5rem !important;
            font-weight: 600 !important;
        }

        /* Estilos para Select2 */
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 0 !important;
            font-family: 'Roboto', sans-serif !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            padding-right: 20px !important;
            font-size: 0.95rem !important;
            color: #495057 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }

        .select2-dropdown {
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        }

        .select2-results__option {
            font-family: 'Roboto', sans-serif !important;
            font-size: 0.95rem !important;
            padding: 8px 12px !important;
        }

        .select2-results__option--highlighted {
            background-color: #007bff !important;
        }

        /* Botones */
        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn i {
            font-size: 0.9rem !important;
            margin-right: 0.5rem !important;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        /* Card */
        .rounded-3 {
            border-radius: 12px !important;
        }

        /* Invalid feedback */
        .invalid-feedback {
            font-size: 0.85rem !important;
        }

        /* Textarea */
        textarea.form-control {
            resize: vertical;
            min-height: 90px;
        }

        /* Placeholder styling */
        ::placeholder {
            color: #6c757d !important;
            opacity: 0.7;
        }

        /* Alertas */
        .alert {
            border-radius: 8px;
        }

        .btn-close {
            background-size: 0.75rem;
            padding: 0.5rem;
        }

        /* Contenedor de estaciones */
        .stations-container {
            border-color: #e0e0e0 !important;
            border-radius: 8px !important;
        }

        .stations-container .form-check-label {
            font-size: 0.9rem !important;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#area_id, #color').select2({
                placeholder: 'Seleccione una opción',
                allowClear: false,
                width: '100%'
            });

            // Cerrar alertas automáticamente después de 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
@stop
