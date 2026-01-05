@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <h1>{{ __('Roles') }}</h1>
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
        <!-- Header con buscador -->
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Buscador -->
                <div class="search-box">
                    <form method="GET" action="{{ route('roles.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control border-end-0" placeholder="Buscar..."
                                aria-label="Buscar" value="{{ request('search') }}">
                            <button type="submit" class="input-group-text bg-white border-start-0">
                                <i class="fas fa-search text-secondary"></i>
                            </button>
                            @if (!empty(request('search')))
                                <a href="{{ route('roles.index') }}" class="input-group-text bg-white border-start-0">
                                    <i class="fas fa-times text-danger"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Botón Agregar -->
                @can('create roles')
                    <a href="{{ route('roles.create') }}" class="btn btn-primary rounded-3">
                        <i class="fas fa-plus me-2"></i>
                        <span>Agregar Rol</span>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Cuerpo con tabla -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Nombre') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Permisos') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Creado') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr class="border-light-subtle">
                                <!-- Nombre -->
                                <td class="py-3 fw-500">{{ $role->name }}</td>

                                <!-- Permisos -->
                                <td class="py-3">
                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                        @foreach($role->permissions->take(3) as $permission)
                                            <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                                {{ $permission->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if($role->permissions->count() > 3)
                                        <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                            +{{ $role->permissions->count() - 3 }} más
                                        </span>
                                    @endif
                                    <div class="text-muted small mt-1">
                                        {{ $role->permissions->count() }} permisos
                                    </div>
                                </td>

                                <!-- Fecha de Creación -->
                                <td class="py-3 small">
                                    <div class="fw-500">{{ $role->created_at->format('Y-m-d') }}</div>
                                    <div class="text-muted">{{ $role->created_at->format('H:i') }}</div>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit roles')
                                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary rounded-3">
                                                <i class="fas fa-edit me-1"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete roles')
                                            <form action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:inline;" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">
                                                    <i class="fas fa-trash me-1"></i>
                                                    <span>Eliminar</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-user-shield fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">
                                            @if(request('search'))
                                                No se encontraron roles que coincidan con "{{ request('search') }}"
                                            @else
                                                No hay roles registrados
                                            @endif
                                        </span>
                                        @if(request('search'))
                                            <a href="{{ route('roles.index') }}" class="btn btn-sm btn-link mt-2">
                                                Limpiar búsqueda
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pie de página con paginación -->
        @if ($roles->hasPages() || $roles->total() > 0)
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Información de resultados -->
                    <div class="text-muted small">
                        Mostrando {{ $roles->firstItem() ?? 0 }} a {{ $roles->lastItem() ?? 0 }} de
                        {{ $roles->total() }} resultados
                    </div>

                    <!-- Controles de paginación -->
                    @if ($roles->hasPages())
                        {{ $roles->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @endif
    </div>
@stop

@section('css')
    <!-- Fuente Google Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Aplicar fuente a elementos específicos sin afectar AdminLTE */
        .card,
        .btn,
        .form-control,
        .table,
        .content-header h1 {
            font-family: 'Roboto', sans-serif !important;
        }

        /* Estilos adicionales para la tabla */
        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        /* Mejoras en jerarquía tipográfica */
        .table thead th {
            font-weight: 700 !important;
            font-size: 0.85rem;
        }

        .table tbody td {
            font-size: 0.875rem;
        }

        /* Buscador sin contorno azul */
        .search-box .input-group {
            width: 380px;
        }

        .search-box .form-control {
            border-radius: 20px 0 0 20px !important;
            border-right: none;
            padding: 0.5rem 1.5rem;
            height: 42px;
            font-size: 0.95rem;
        }

        .search-box .input-group-text {
            border-radius: 0 20px 20px 0 !important;
            border-left: none;
            background-color: white;
            padding: 0 1.25rem;
            font-size: 1rem;
        }

        /* Botón de limpiar búsqueda */
        .search-box .input-group-text .fa-times {
            transition: all 0.2s ease;
        }

        .search-box .input-group-text:hover .fa-times {
            transform: scale(1.1);
        }

        /* Quitar contorno azul al enfocar */
        .search-box .form-control:focus {
            border-color: #dee2e6 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* Badges simétricos */
        .badge-status {
            display: inline-block;
            padding: 0.4em 0.6em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid transparent;
        }

        .badge-status.bg-primary {
            background-color: rgba(13, 110, 253, 0.1) !important;
            color: #0d6efd !important;
            border-color: rgba(13, 110, 253, 0.2) !important;
        }

        .badge-status.bg-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
            border-color: rgba(108, 117, 125, 0.2) !important;
        }

        .badge-status.bg-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
            color: #198754 !important;
            border-color: rgba(25, 135, 84, 0.2) !important;
        }

        .badge-status.bg-danger {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
            border-color: rgba(220, 53, 69, 0.2) !important;
        }

        .badge-status.bg-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
            color: #856404 !important;
            border-color: rgba(255, 193, 7, 0.2) !important;
        }

        .badge-status.bg-info {
            background-color: rgba(13, 202, 240, 0.1) !important;
            color: #0dcaf0 !important;
            border-color: rgba(13, 202, 240, 0.2) !important;
        }

        /* Estilos para la paginación */
        .pagination {
            margin-bottom: 0;
        }

        .page-item .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            color: #6c757d;
            font-size: 0.9rem;
            min-width: 32px;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            padding: 6px 12px;
        }

        .page-item.active .page-link {
            background-color: #1a73e8;
            color: white;
        }

        .page-item:not(.active) .page-link:hover {
            background-color: #f8f9fa;
            color: #1a73e8;
        }

        .page-item.disabled .page-link {
            opacity: 0.5;
        }

        /* Estilos para el contador de resultados */
        .text-muted.small {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Ajustes de espaciado para paginación */
        .card-footer .pagination {
            margin-bottom: 0;
        }

        /* Estilos para los botones de acción */
        .btn {
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        /* Alertas */
        .alert {
            border-radius: 8px;
        }

        .btn-close {
            background-size: 0.75rem;
            padding: 0.5rem;
        }

        .fw-500 {
            font-weight: 500;
        }

        /* Asegurar que los botones de acción mantengan su tamaño */
        .btn-outline-primary,
        .btn-outline-danger {
            white-space: nowrap;
        }

        /* Flex wrap para permisos */
        .gap-1 {
            gap: 0.25rem;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Confirmación antes de eliminar
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar event listener a todos los formularios de eliminación
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Usar SweetAlert2 si está disponible, sino usar confirm nativo
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "¡No podrás revertir esta acción!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.submit();
                            }
                        });
                    } else {
                        // Fallback a confirm nativo
                        if (confirm(
                                '¿Estás seguro de que deseas eliminar este rol? Esta acción no se puede deshacer.'
                            )) {
                            this.submit();
                        }
                    }
                });
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
