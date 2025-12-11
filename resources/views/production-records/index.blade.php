@extends('adminlte::page')

@section('title', 'Registros de Producción')

@section('content_header')
    <h1>{{ __('Registros de Producción') }}</h1>
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
        <!-- Header con buscador y filtros -->
        <div class="card-header bg-white border-0 py-3">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">

                <!-- Botones de acción -->
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('line-stoppage-records.create', ['source' => 'production-records.index']) }}"
                        class="btn btn-outline-warning rounded-3 d-flex align-items-center">
                        <i class="fas fa-pause-circle mr-2"></i>
                        <span>Paro de Línea</span>
                    </a>

                    <a href="{{ route('scrap-records.create', ['source' => 'production-records.index']) }}"
                        class="btn btn-outline-danger rounded-3 d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span>Scrap</span>
                    </a>

                    <a href="{{ route('production.export-pdf-form') }}"
                        class="btn btn-outline-primary rounded-3 d-flex align-items-center">
                        <i class="fas fa-file-pdf mr-2"></i>
                        <span>Reporte PDF</span>
                    </a>
                </div>

                <!-- Buscador y filtros -->
                <div class="search-box">
                    <form method="GET" action="{{ route('production-records.index') }}" id="searchForm">
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <div class="input-group" style="width: 200px;">
                                <input type="date" name="date" class="form-control" value="{{ request('date') }}"
                                    max="{{ date('Y-m-d') }}">
                                @if (request()->has('date'))
                                    <a href="{{ route('production-records.index') }}"
                                        class="input-group-text bg-white border-start-0 text-danger">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>

                            <div class="input-group" style="width: 300px;">
                                <input type="text" name="search" class="form-control border-end-0"
                                    placeholder="Buscar..." value="{{ request('search') }}">
                                <button type="submit" class="input-group-text bg-white border-start-0">
                                    <i class="fas fa-search text-secondary"></i>
                                </button>
                                @if (request()->has('search'))
                                    <a href="{{ route('production-records.index') }}"
                                        class="input-group-text bg-white border-start-0 text-danger">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cuerpo con tabla -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Estación</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">N° de Parte</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">N° de Orden</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Fecha</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Turno</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Planeada</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Producida</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Progreso</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Estado</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Sincronización
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($productionRecords as $record)
                            @php
                                $prod = $record->produced_quantity;
                                $plan = $record->planned_quantity;
                                $percentage = $plan > 0 ? ($prod / $plan) * 100 : 0;

                                if ($percentage < 80) {
                                    $color = 'danger';
                                    $progressColor = 'danger';
                                } elseif ($percentage < 100) {
                                    $color = 'warning';
                                    $progressColor = 'warning';
                                } elseif ($percentage == 100) {
                                    $color = 'success';
                                    $progressColor = 'success';
                                } else {
                                    $color = 'warning';
                                    $progressColor = 'warning';
                                }

                                $progressWidth = min(100, $percentage);
                            @endphp

                            <tr class="border-light-subtle">
                                <!-- Estación -->
                                <td class="py-3">
                                    <div class="fw-500">{{ $record->work_number }}</div>
                                    <div class="text-muted small">{{ $record->work_name }}</div>
                                </td>

                                <!-- Número de Parte -->
                                <td class="py-3">
                                    <div class="fw-500">{{ $record->part_number }}</div>
                                    <div class="text-muted small">{{ $record->part_name }}</div>
                                </td>

                                <!-- Número de Orden -->
                                <td class="py-3 fw-500">
                                    {{ $record->shop_order_number }}
                                </td>

                                <!-- Fecha -->
                                <td class="py-3 small">
                                    <div class="fw-500">
                                        {{ \Carbon\Carbon::parse($record->planned_date)->format('d/m/Y') }}
                                    </div>
                                </td>

                                <!-- Turno -->
                                <td class="py-3">
                                    <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                        {{ $record->shift_name }}
                                    </span>
                                </td>

                                <!-- Cantidad Planeada -->
                                <td class="py-3 text-center">
                                    <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                        {{ number_format($plan) }}
                                    </span>
                                </td>

                                <!-- Cantidad Producida -->
                                <td class="py-3 text-center">
                                    <span
                                        class="badge-status bg-{{ $color }} bg-opacity-10 text-{{ $color }}">
                                        {{ number_format($prod) }}
                                    </span>
                                </td>

                                <!-- Barra de Progreso -->
                                <td class="py-3 text-center">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="progress w-75" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $progressColor }}" role="progressbar"
                                                style="width: {{ $progressWidth }}%;" aria-valuenow="{{ $progressWidth }}"
                                                aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted mt-1">
                                            {{ number_format($percentage, 1) }}%
                                            @if ($percentage > 100)
                                                <span class="text-{{ $color }} ms-1">
                                                    (+{{ number_format($percentage - 100, 1) }}%)
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                </td>

                                <!-- Estado -->
                                <td class="py-3 text-center">
                                    @switch($record->status_name)
                                        @case('Completado')
                                            <span class="badge-status bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-check-circle mr-1"></i> {{ $record->status_name }}
                                            </span>
                                        @break

                                        @case('En progreso')
                                            <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                                <i class="fas fa-spinner mr-1"></i> {{ $record->status_name }}
                                            </span>
                                        @break

                                        @case('No planeado')
                                            <span class="badge-status bg-warning bg-opacity-10 text-warning">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $record->status_name }}
                                            </span>
                                        @break

                                        @case('Detenido')
                                            <span class="badge-status bg-warning bg-opacity-10 text-warning">
                                                <i class="fas fa-ban mr-1"></i> {{ $record->status_name }}
                                            </span>
                                        @break

                                        @case('Pendiente')
                                            <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                                <i class="fas fa-clock mr-1"></i> {{ $record->status_name }}
                                            </span>
                                        @break

                                        @default
                                            <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                                {{ $record->status_name }}
                                            </span>
                                    @endswitch
                                </td>

                                <!-- Sincronización -->
                                <td class="py-3 text-center">
                                    @if ($record->synced_to_infor)
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="text-success small fw-500">
                                                <i class="fas fa-check-circle mr-1"></i> Enviado
                                            </span>
                                            @if ($record->synced_at)
                                                <span class="text-muted" style="font-size: 0.7rem;">
                                                    {{ \Carbon\Carbon::parse($record->synced_at)->format('d/m/Y H:i') }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            <i class="fas fa-clock mr-1"></i> Pendiente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">
                                            @if (request()->has('search') || request()->has('date'))
                                                No se encontraron registros para los filtros seleccionados
                                            @else
                                                No hay registros de producción
                                            @endif
                                        </span>

                                        @if (request()->has('search') || request()->has('date'))
                                            <a href="{{ route('production-records.index') }}"
                                                class="btn btn-sm btn-link mt-2">
                                                Limpiar filtros
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if ($productionRecords->hasPages() || $productionRecords->total() > 0)
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">

                        <div class="text-muted small">
                            Mostrando {{ $productionRecords->firstItem() ?? 0 }} a
                            {{ $productionRecords->lastItem() ?? 0 }} de
                            {{ $productionRecords->total() }} registros
                        </div>

                        @if ($productionRecords->hasPages())
                            {{ $productionRecords->links('pagination::bootstrap-4') }}
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <!-- Fuente Google Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Aplicar fuente a elementos específicos */
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

        /* Buscador mejorado */
        .search-box form {
            flex-wrap: wrap;
        }

        .search-box .input-group {
            width: 200px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .search-box .form-control {
            border: 1px solid #e0e0e0;
            padding: 0.5rem 1rem;
            height: 42px;
            font-size: 0.95rem;
        }

        .search-box .form-control:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        /* Botones del buscador */
        .search-box .btn {
            height: 42px;
            padding: 0.5rem 1.25rem;
            white-space: nowrap;
        }

        /* Badges simétricos */
        .badge-status {
            display: inline-block;
            min-width: 70px;
            padding: 0.4em 0.75em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
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

        /* Barra de progreso personalizada */
        .progress {
            border-radius: 10px;
            background-color: #f0f0f0;
            overflow: hidden;
        }

        .progress-bar {
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        /* Responsive para el buscador */
        @media (max-width: 768px) {
            .search-box form {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }

            .search-box .input-group {
                width: 100% !important;
            }

            .search-box .btn {
                width: 100%;
                justify-content: center;
            }

            .card-header {
                flex-direction: column;
                gap: 1rem;
            }

            .d-flex.flex-wrap {
                justify-content: center;
            }
        }

        /* Para pantallas medianas */
        @media (min-width: 769px) and (max-width: 992px) {
            .search-box .input-group {
                width: 180px;
            }
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            // Configurar fecha máxima en el input de fecha
            const dateInput = document.querySelector('input[name="date"]');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('max', today);

                // Si no hay valor, establecer la fecha de hoy
                if (!dateInput.value) {
                    dateInput.value = today;
                }
            }

            // Mejorar la experiencia de búsqueda
            const searchInput = document.querySelector('input[name="search"]');

            if (searchInput) {
                // Limpiar búsqueda con Escape
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                        this.form.submit();
                    }
                });

                // Auto-focus en el campo de búsqueda
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            }

            // Configurar placeholder dinámico para la fecha
            if (dateInput) {
                dateInput.addEventListener('focus', function() {
                    this.type = 'date';
                });

                dateInput.addEventListener('blur', function() {
                    if (!this.value) {
                        this.type = 'text';
                        this.placeholder = 'Fecha';
                    }
                });
            }
        });
    </script>
@stop
