@extends('adminlte::page')

@section('title', 'Editar Rol')

@section('content_header')
    <h1>{{ __('Editar Rol') }}</h1>
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
            <form action="{{ route('roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="name" class="form-label fw-bold text-secondary">{{ __('Nombre del Rol') }} *</label>
                        <input type="text" name="name" id="name"
                            class="form-control border-light-subtle @error('name') is-invalid @enderror"
                            value="{{ old('name', $role->name) }}" required placeholder="Ej: Editor">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-secondary">{{ __('Permisos') }}</label>
                        <div class="permissions-container border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                            @if($permissions->count() > 0)
                                <div class="row">
                                    @foreach($permissions->groupBy(function($item) {
                                        return explode(' ', $item->name)[0];
                                    }) as $group => $groupPermissions)
                                        <div class="col-md-4 mb-4">
                                            <div class="card border-0 bg-light">
                                                <div class="card-header bg-transparent py-2">
                                                    <h6 class="mb-0 fw-bold text-secondary text-uppercase">
                                                        <i class="fas fa-folder me-2"></i>{{ $group }}
                                                    </h6>
                                                </div>
                                                <div class="card-body py-2">
                                                    @foreach($groupPermissions as $permission)
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="permissions[]"
                                                                value="{{ $permission->id }}"
                                                                id="permission_{{ $permission->id }}"
                                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                {{ ucwords(str_replace($group.' ', '', $permission->name)) }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    No hay permisos disponibles.
                                </div>
                            @endif
                        </div>
                        @error('permissions')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times me-2"></i> {{ __('Cancelar') }}
                    </a>
                    <button type="reset" class="btn btn-outline-primary rounded-3">
                        <i class="fas fa-undo me-2"></i> {{ __('Restablecer') }}
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save me-2"></i> {{ __('Actualizar Rol') }}
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

        /* Contenedor de permisos */
        .permissions-container {
            border-color: #e0e0e0 !important;
            border-radius: 8px !important;
        }

        .permissions-container .card {
            border-radius: 8px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .permissions-container .form-check-label {
            font-size: 0.9rem !important;
            cursor: pointer;
        }

        .permissions-container .form-check-input {
            margin-top: 0.3rem;
        }

        /* Estilos para checkboxes */
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
@stop

@section('js')
    <script>
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

        // Función para seleccionar/deseleccionar todos los permisos de un grupo
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar checkboxes de "Seleccionar todos" para cada grupo
            document.querySelectorAll('.permissions-container .card-header').forEach(header => {
                const groupPermissions = header.closest('.col-md-4').querySelectorAll('.form-check-input');
                if (groupPermissions.length > 0) {
                    const selectAllCheckbox = document.createElement('div');
                    selectAllCheckbox.className = 'form-check form-check-inline mb-2';
                    selectAllCheckbox.innerHTML = `
                        <input type="checkbox" class="form-check-input select-all-group">
                        <label class="form-check-label small">Seleccionar todos</label>
                    `;
                    header.appendChild(selectAllCheckbox);
                }
            });

            // Event listener para checkboxes de "Seleccionar todos"
            document.querySelectorAll('.select-all-group').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const groupCard = this.closest('.card');
                    const checkboxes = groupCard.querySelectorAll('.form-check-input:not(.select-all-group)');
                    checkboxes.forEach(cb => {
                        cb.checked = this.checked;
                    });
                });

                // Verificar si todos los permisos del grupo están seleccionados
                const groupCard = checkbox.closest('.card');
                const groupCheckboxes = groupCard.querySelectorAll('.form-check-input:not(.select-all-group)');
                const allChecked = Array.from(groupCheckboxes).every(cb => cb.checked);
                checkbox.checked = allChecked;
            });
        });
    </script>
@stop
