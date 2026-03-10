@extends('adminlte::page')

@section('title', 'Editar Número de Parte')

@section('content_header')
    <h1>{{ __('Editar Número de Parte') }}</h1>
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
            <form action="{{ route('part-numbers.update', $partNumber->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <!-- Información de solo lectura -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">{{ __('Estación') }}</label>
                        <div class="form-control-readonly">
                            {{ $partNumber->workCenter->name ?? 'No asignada' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">{{ __('Número') }}</label>
                        <div class="form-control-readonly">
                            {{ $partNumber->number }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">{{ __('Nombre') }}</label>
                        <div class="form-control-readonly">
                            {{ $partNumber->name }}
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">{{ __('Clase') }}</label>
                        <div class="form-control-readonly">
                            {{ $partNumber->itemClass->abbreviation ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">{{ __('Tasa de Producción') }}</label>
                        <div class="form-control-readonly text-center">
                            @if ($partNumber->production_rate != 0)
                                {{ number_format(60 / $partNumber->production_rate, 2) }}
                            @else
                                0.0
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">{{ __('Estado') }}</label>
                        <div class="form-control-readonly text-center">
                            @if($partNumber->is_obsolete)
                                <span class="text-danger fw-500">
                                    <i class="fas fa-times-circle mr-1"></i> Obsoleto
                                </span>
                            @else
                                <span class="text-success fw-500">
                                    <i class="fas fa-check-circle mr-1"></i> Activo
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Campos editables -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="production_order" class="form-label fw-bold text-secondary">{{ __('Orden de Producción') }}</label>
                        <input type="number" name="production_order" id="production_order" min="0" step="1"
                               class="form-control border-light-subtle @error('production_order') is-invalid @enderror"
                               value="{{ old('production_order', $partNumber->production_order) }}"
                               placeholder="Dejar vacío si no tiene orden específica">
                        @error('production_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Orden en que se debe producir este número de parte en la estación</small>
                    </div>

                    <div class="col-md-4">
                        <label for="efficiency" class="form-label fw-bold text-secondary">{{ __('Eficiencia (%)') }}</label>
                        <input type="number" name="efficiency" id="efficiency" step="0.01" min="0" max="100"
                               class="form-control border-light-subtle @error('efficiency') is-invalid @enderror"
                               value="{{ old('efficiency', $partNumber->efficiency) }}">
                        @error('efficiency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('part-numbers.index') }}" class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times mr-2"></i> {{ __('Cancelar') }}
                    </a>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save mr-2"></i> {{ __('Guardar Cambios') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orden de Producción en la Estación -->
    @if($partNumbersInSameWorkCenter->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-bold text-secondary">
                <i class="fas fa-list-ol mr-2"></i>
                {{ __('Orden de Producción en ') }}{{ $partNumber->workCenter->name ?? 'Esta Estación' }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Orden') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Número de Parte') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Estado') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $allPartNumbers = $partNumbersInSameWorkCenter->concat(collect([$partNumber]))
                                ->sortBy(function($item) {
                                    return $item->production_order ?? 9999;
                                });
                        @endphp
                        @foreach($allPartNumbers as $pn)
                            <tr class="{{ $pn->id === $partNumber->id ? 'table-warning' : '' }}">
                                <td class="py-3">
                                    @if($pn->production_order)
                                        <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                            {{ $pn->production_order }}
                                        </span>
                                    @else
                                        <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                            Sin orden
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="fw-500">{{ $pn->number }}</div>
                                    <div class="text-muted small">{{ $pn->name }}</div>
                                    @if($pn->id === $partNumber->id)
                                        <span class="badge-status bg-warning bg-opacity-10 text-dark">
                                            <i class="fas fa-star mr-1"></i> Actual
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if($pn->is_obsolete)
                                        <span class="badge-status bg-danger bg-opacity-10 text-danger">
                                            <i class="fas fa-times-circle mr-1"></i> Obsoleto
                                        </span>
                                    @else
                                        <span class="badge-status bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-check-circle mr-1"></i> Activo
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Scraps Default -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-secondary">
                    <i class="fas fa-trash-alt mr-2"></i>
                    {{ __('Scraps Default') }}
                </h5>
                {{-- @can('create scrap default') --}}
                    <a href="{{ route('part-number-default-scraps.create', $partNumber) }}"
                    class="btn btn-primary rounded-3">
                        <i class="fas fa-plus mr-2"></i>
                        <span>Agregar Scrap Default</span>
                    </a>
                {{-- @endcan --}}
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Código') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Tipo de Scrap') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Cantidad') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Descripción') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($defaultScraps as $defaultScrap)
                            <tr class="border-light-subtle">
                                <td class="py-3">
                                    <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                        {{ $defaultScrap->scrap->code }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="fw-500">{{ $defaultScrap->scrap->name }}</div>
                                </td>
                                <td class="py-3">
                                    <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                        {{ $defaultScrap->quantity }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="text-muted">{{ $defaultScrap->description ?? '—' }}</div>
                                </td>
                                {{-- @can('delete scrap default') --}}
                                    <td class="py-3 text-center">
                                        <form action="{{ route('part-number-default-scraps.destroy', $defaultScrap) }}"
                                            method="POST" style="display:inline;" class="delete-default-scrap-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">
                                                <i class="fas fa-trash mr-1"></i>
                                                <span>Eliminar</span>
                                            </button>
                                        </form>
                                    </td>
                                {{-- @endcan --}}
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-trash fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">No hay scraps default configurados</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Imágenes Asociadas -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-secondary">
                    <i class="fas fa-images mr-2"></i>
                    {{ __('Imágenes Asociadas') }}
                </h5>
                @can('create visual aids')
                    <a href="{{ route('visual-aids.create', ['part_number' => $partNumber->id]) }}"
                       class="btn btn-primary rounded-3">
                        <i class="fas fa-plus mr-2"></i>
                        <span>Agregar Imagen</span>
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Imagen') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Descripción') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Estado') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visualAids as $visualAid)
                            <tr class="border-light-subtle">
                                <td class="py-3">
                                    <img src="{{ asset('storage/' . $visualAid->path) }}"
                                         alt="{{ $visualAid->alt_text }}"
                                         class="img-thumbnail"
                                         style="width: 60px; height: 60px; object-fit: cover;">
                                </td>
                                <td class="py-3">
                                    <div class="fw-500">{{ $visualAid->alt_text ?? 'N/A' }}</div>
                                </td>
                                <td class="py-3">
                                    @if($visualAid->is_active)
                                        <span class="badge-status bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-check-circle mr-1"></i> Activa
                                        </span>
                                    @else
                                        <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                            <i class="fas fa-times-circle mr-1"></i> Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 text-center">
                                    @can('delete visual aids')
                                        <form action="{{ route('visual-aids.destroy', [$visualAid->id, $partNumber->id]) }}"
                                              method="POST" style="display:inline;" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">
                                                <i class="fas fa-trash mr-1"></i>
                                                <span>Eliminar</span>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-image fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">No hay imágenes asociadas</span>
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

        /* Badges simétricos (para tabla) */
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

        .badge-status.bg-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
        }

        .badge-status.bg-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
            color: #198754 !important;
        }

        .badge-status.bg-danger {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
        }

        .badge-status.bg-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
            color: #856404 !important;
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

        /* Imágenes */
        .img-thumbnail {
            border-radius: 8px !important;
            border: 1px solid #e0e0e0 !important;
            padding: 0 !important;
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

        /* Table warning row */
        .table-warning {
            background-color: rgba(255, 243, 205, 0.3) !important;
        }

        /* Text colors for status */
        .text-danger {
            color: #dc3545 !important;
        }

        .text-success {
            color: #198754 !important;
        }
    </style>
@stop

@section('js')
    <script>
        // Confirmar eliminación de scrap default
        document.querySelectorAll('.delete-default-scrap-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¿Eliminar scrap default?',
                        text: "Esta acción no se puede revertir.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) this.submit();
                    });
                } else {
                    if (confirm('¿Eliminar este scrap default?')) this.submit();
                }
            });
        });

        // Confirmación antes de eliminar imágenes
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
                                '¿Estás seguro de que deseas eliminar esta imagen? Esta acción no se puede deshacer.'
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
