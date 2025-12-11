@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <h1>{{ __('Usuarios') }}</h1>
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
                    <form method="GET" action="{{ route('users.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control border-end-0" placeholder="Buscar..."
                                aria-label="Buscar" value="{{ request('search') }}">
                            <button type="submit" class="input-group-text bg-white border-start-0">
                                <i class="fas fa-search text-secondary"></i>
                            </button>
                            @if (!empty(request('search')))
                                <a href="{{ route('users.index') }}" class="input-group-text bg-white border-start-0">
                                    <i class="fas fa-times text-danger"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Botón Agregar -->
                @can('create users')
                    <a href="{{ route('users.create') }}" class="btn btn-primary rounded-3">
                        <i class="fas fa-plus mr-2"></i>
                        <span>Agregar Usuario</span>
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
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Usuario') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Rol') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Líneas') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Creado') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                {{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-light-subtle">
                                <!-- Nombre -->
                                <td class="py-3 fw-500">{{ $user->name }}</td>

                                <!-- Usuario -->
                                <td class="py-3">
                                    <span class="text-muted small">{{ $user->nickname }}</span>
                                    <br>
                                    <span class="text-muted smaller">{{ $user->email }}</span>
                                </td>

                                <!-- Rol -->
                                <td class="py-3">
                                    @if ($user->roles->first())
                                        <span class="badge-status bg-success bg-opacity-10 text-success">
                                            {{ $user->roles->first()->name }}
                                        </span>
                                    @else
                                        <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                            {{ __('Sin Rol') }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Líneas -->
                                <td class="py-3">
                                    @if ($user->lines->count() > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($user->lines as $line)
                                                <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                                    {{ $line->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                            {{ __('Sin Líneas') }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Fecha de Creación -->
                                <td class="py-3 small">
                                    <div class="fw-500">{{ optional($user->created_at)->format('Y-m-d') }}</div>
                                    <div class="text-muted">{{ optional($user->created_at)->format('H:i') }}</div>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit users')
                                            <a href="{{ route('users.edit', $user->id) }}"
                                                class="btn btn-sm btn-outline-primary rounded-3">
                                                <i class="fas fa-edit mr-1"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete users')
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                style="display:inline;" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">
                                                    <i class="fas fa-trash mr-1"></i>
                                                    <span>Eliminar</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">
                                            @if (request('search'))
                                                No se encontraron usuarios que coincidan con "{{ request('search') }}"
                                            @else
                                                No hay usuarios registrados
                                            @endif
                                        </span>
                                        @if (request('search'))
                                            <a href="{{ route('users.index') }}" class="btn btn-sm btn-link mt-2">
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
        @if ($users->hasPages() || $users->total() > 0)
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Información de resultados -->
                    <div class="text-muted small">
                        Mostrando {{ $users->firstItem() ?? 0 }} a {{ $users->lastItem() ?? 0 }} de
                        {{ $users->total() }} resultados
                    </div>

                    <!-- Controles de paginación -->
                    @if ($users->hasPages())
                        {{ $users->links('pagination::bootstrap-4') }}
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
            min-width: 70px;
            padding: 0.5em 0.75em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-status.bg-primary {
            background-color: rgba(13, 110, 253, 0.1) !important;
            color: #0d6efd !important;
            border: 1px solid rgba(13, 110, 253, 0.2) !important;
        }

        .badge-status.bg-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
            border: 1px solid rgba(108, 117, 125, 0.2) !important;
        }

        .badge-status.bg-info {
            background-color: rgba(13, 202, 240, 0.1) !important;
            color: #0dcaf0 !important;
            border: 1px solid rgba(13, 202, 240, 0.2) !important;
        }

        .badge-status.bg-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
            color: #198754 !important;
            border: 1px solid rgba(25, 135, 84, 0.2) !important;
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

        /* Texto más pequeño */
        .smaller {
            font-size: 0.8rem;
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
                                '¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.'
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
