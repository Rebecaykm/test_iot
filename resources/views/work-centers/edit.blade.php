@extends('adminlte::page')

@section('title', 'Editar Estación')

@section('content_header')
    <h1>{{ __('Editar Estación') }}</h1>
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

    <!-- Formulario Principal -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-body">
            <form action="{{ route('work-centers.update', $workCenter->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Primera fila: Número y Nombre -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary">{{ __('Número') }}</label>
                        <div class="form-control-readonly">
                            {{ $workCenter->number }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary">{{ __('Nombre') }}</label>
                        <div class="form-control-readonly">
                            {{ $workCenter->name }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Segunda fila: Línea y IP -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary">{{ __('Línea') }}</label>
                        <div class="form-control-readonly">
                            {{ $workCenter->line?->name ?? 'Sin línea asignada' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="ip" class="form-label fw-bold text-secondary">{{ __('Dirección IP') }}</label>
                        <input type="text" name="ip" id="ip"
                            class="form-control border-light-subtle @error('ip') is-invalid @enderror"
                            value="{{ old('ip', $workCenter->ip) }}" placeholder="Ej: 192.168.1.100">
                        @error('ip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Dirección IP de la estación para comunicación</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('work-centers.index') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times me-2"></i> {{ __('Cancelar') }}
                    </a>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save me-2"></i> {{ __('Actualizar Estación') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listado de Tags -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-secondary">
                    <i class="fas fa-tags me-2"></i>
                    {{ __('Tags Asociados') }}
                </h5>
                @can('create tags')
                    <a href="{{ route('tags.create', ['work_center' => $workCenter->id]) }}" class="btn btn-primary rounded-3">
                        <i class="fas fa-plus me-2"></i>
                        <span>Agregar Tag</span>
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Dirección') }}
                            </th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Longitud') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Tipo') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Descripción') }}
                            </th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                {{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tags as $tag)
                            <tr class="border-light-subtle">
                                <!-- Dirección -->
                                <td class="py-3 fw-500">{{ $tag->address ?? '-' }}</td>

                                <!-- Longitud -->
                                <td class="py-3">{{ $tag->long ?? '-' }}</td>

                                <!-- Tipo de Tag -->
                                <td class="py-3">
                                    <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                        {{ $tag->tagType->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- Descripción -->
                                <td class="py-3">
                                    <span class="text-muted small">{{ $tag->description ?? '-' }}</span>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit tags')
                                            <a href="{{ route('tags.edit', $tag) }}"
                                                class="btn btn-sm btn-outline-primary rounded-3">
                                                <i class="fas fa-edit me-1"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete tags')
                                            <form action="{{ route('tags.destroy', $tag) }}" method="POST"
                                                style="display:inline;" class="delete-form">
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
                                <td colspan="5" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">No hay tags asociados</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

        /* Campos de solo lectura */
        .form-control-readonly {
            border-radius: 8px !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.95rem !important;
            border: 1px solid #e0e0e0 !important;
            background-color: #f8f9fa !important;
            color: #495057 !important;
            min-height: 38px;
            display: flex;
            align-items: center;
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

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
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

        /* Table hover */
        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
    </style>
@stop

@section('js')
    <script>
        // Confirmación antes de eliminar tags
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
                                '¿Estás seguro de que deseas eliminar este tag? Esta acción no se puede deshacer.'
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
