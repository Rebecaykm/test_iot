@extends('adminlte::page')

@section('title', 'Crear Usuario')

@section('content_header')
    <h1>{{ __('Crear Usuario') }}</h1>
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
            <form action="{{ route('users.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-bold text-secondary">{{ __('Nombre') }} *</label>
                        <input type="text" name="name" id="name"
                            class="form-control border-light-subtle @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required placeholder="Ej: Miguel Álcala">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="nickname" class="form-label fw-bold text-secondary">{{ __('Nombre de Usuario') }} *</label>
                        <input type="text" name="nickname" id="nickname"
                            class="form-control border-light-subtle @error('nickname') is-invalid @enderror"
                            value="{{ old('nickname') }}" required placeholder="Ej: miguel_alcala">
                        <small class="text-muted">Solo letras, números, guiones y guiones bajos</small>
                        @error('nickname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-bold text-secondary">{{ __('Correo Electrónico') }} *</label>
                        <input type="email" name="email" id="email"
                            class="form-control border-light-subtle @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" required placeholder="Ej: usuario@dominio.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label fw-bold text-secondary">{{ __('Rol') }}</label>
                        <select name="role" id="role"
                            class="form-control select2 border-light-subtle @error('role') is-invalid @enderror">
                            <option value="">{{ __('Seleccione un rol') }}</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-bold text-secondary">{{ __('Contraseña') }} *</label>
                        <input type="password" name="password" id="password"
                            class="form-control border-light-subtle @error('password') is-invalid @enderror"
                            required placeholder="Mínimo 8 caracteres">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label fw-bold text-secondary">{{ __('Confirmar Contraseña') }} *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="form-control border-light-subtle"
                            required placeholder="Repite la contraseña">
                    </div>
                </div>

                <!-- Sección para asociar líneas -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-secondary">{{ __('Líneas Asociadas') }}</label>
                        <div class="stations-container border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            @if ($lines->count() > 0)
                                <div class="row">
                                    @foreach($lines as $line)
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="lines[]"
                                                    id="line_{{ $line->id }}"
                                                    value="{{ $line->id }}"
                                                    {{ in_array($line->id, old('lines', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="line_{{ $line->id }}">
                                                    {{ $line->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    No hay líneas disponibles para asociar.
                                </div>
                            @endif
                        </div>
                        @error('lines')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Sección para asociar estaciones -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-secondary">{{ __('Estaciones Asociadas') }}</label>
                        <div class="stations-container border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            @if ($workCenters->count() > 0)
                                <div class="row">
                                    @foreach($workCenters as $workCenter)
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="work_centers[]"
                                                    id="work_center_{{ $workCenter->id }}"
                                                    value="{{ $workCenter->id }}"
                                                    {{ in_array($workCenter->id, old('work_centers', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="work_center_{{ $workCenter->id }}">
                                                    <div class="fw-500">{{ $workCenter->number }}</div>
                                                    <div class="text-muted small">{{ $workCenter->name }}</div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    No hay estaciones disponibles para asociar.
                                </div>
                            @endif
                        </div>
                        @error('work_centers')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times me-2"></i> {{ __('Cancelar') }}
                    </a>
                    <button type="reset" class="btn btn-outline-primary rounded-3">
                        <i class="fas fa-undo me-2"></i> {{ __('Limpiar') }}
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save me-2"></i> {{ __('Guardar Usuario') }}
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

        /* Contenedor de estaciones y líneas */
        .stations-container {
            border-color: #e0e0e0 !important;
            border-radius: 8px !important;
        }

        .stations-container .form-check-label {
            font-size: 0.9rem !important;
            cursor: pointer;
        }

        .stations-container .form-check-input {
            margin-top: 0.3rem;
        }

        .fw-500 {
            font-weight: 500;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#role').select2({
                placeholder: 'Seleccione un rol',
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
