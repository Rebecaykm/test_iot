@extends('adminlte::page')

@section('title', 'Registros de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">{{ __('Registros de Producción') }}</h1>

        <!-- Botón de descarga PDF simplificado -->
        <a href="{{ route('production.export-pdf-form') }}" class="btn btn-danger d-flex align-items-center gap-2 shadow-sm"
            style="border-radius: 20px; padding: 0.5rem 1.5rem;">
            <i class="fas fa-file-pdf"></i>
            <span>Descargar reporte</span>
        </a>
    </div>
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

        <!-- Buscador reubicado arriba de la tabla -->
        <div class="p-3 d-flex justify-content-center justify-content-md-end">
            <div class="search-box">
                <form method="GET" action="{{ route('production-records.index') }}" id="searchForm">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control border-end-0" placeholder="Buscar..."
                            value="{{ request('search') }}">

                        <input type="date" name="date" class="form-control border-start-0 border-end-0"
                            value="{{ request('date') }}" style="width:150px;">

                        <button type="submit" class="input-group-text bg-white border-start-0">
                            <i class="fas fa-search text-secondary"></i>
                        </button>

                        @if (request()->has('search') || request()->has('date'))
                            <a href="{{ route('production-records.index') }}"
                                class="input-group-text bg-white border-start-0 text-danger">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
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
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">Planeada</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">Producida</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Estado</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Sincronización
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($productionRecords as $record)
                            @php
                                $prod = $record->produced_quantity;
                                $plan = $record->planned_quantity;
                                $color = $prod < $plan ? 'danger' : ($prod == $plan ? 'success' : 'warning');
                            @endphp

                            <tr class="border-light-subtle">
                                <td class="py-3">
                                    <div class="fw-500">{{ $record->work_number }}</div>
                                    <div class="text-muted small">{{ $record->work_name }}</div>
                                </td>

                                <td class="py-3">
                                    <div class="fw-500">{{ $record->part_number }}</div>
                                    <div class="text-muted small">{{ $record->part_name }}</div>
                                </td>

                                <td class="py-3">
                                    <span class="text-dark">{{ $record->shop_order_number }}</span>
                                </td>

                                <td class="py-3 small">
                                    <div class="fw-500">
                                        {{ \Carbon\Carbon::parse($record->planned_date)->format('d/m/Y') }}
                                    </div>
                                </td>

                                <td class="py-3">
                                    <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                        {{ $record->shift_name }}
                                    </span>
                                </td>

                                <td class="py-3 text-end">
                                    <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                        {{ number_format($plan) }}
                                    </span>
                                </td>

                                <td class="py-3 text-end">
                                    <span
                                        class="badge-status bg-{{ $color }} bg-opacity-10 text-{{ $color }}">
                                        {{ number_format($prod) }}
                                    </span>
                                </td>

                                <td class="py-3">
                                    @if ($record->status_name == 'Completado')
                                        <span class="badge-status bg-success bg-opacity-10 text-success">
                                            {{ $record->status_name }}
                                        </span>
                                    @elseif ($record->status_name == 'En progreso')
                                        <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                            {{ $record->status_name }}
                                        </span>
                                    @elseif ($record->status_name == 'No planeado')
                                        <span class="badge-status bg-warning bg-opacity-10 text-warning">
                                            {{ $record->status_name }}
                                        </span>
                                    @else
                                        <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                            {{ $record->status_name }}
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 text-center">
                                    @if ($record->synced_to_infor)
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="text-success small fw-500">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Enviado a Infor
                                            </span>
                                            @if ($record->synced_at)
                                                <span class="text-muted" style="font-size: 0.7rem;">
                                                    {{ \Carbon\Carbon::parse($record->synced_at)->format('d/m/Y H:i') }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            <i class="fas fa-clock me-1"></i>
                                            Pendiente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">No se encontraron registros</span>

                                        @if (request()->has('search') || request()->has('date'))
                                            <a href="{{ route('production-records.index') }}"
                                                class="btn btn-sm btn-link mt-2">
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

        <!-- Paginación -->
        @if ($productionRecords->hasPages() || $productionRecords->total() > 0)
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando {{ $productionRecords->firstItem() ?? 0 }} a
                        {{ $productionRecords->lastItem() ?? 0 }} de
                        {{ $productionRecords->total() }} resultados
                    </div>
                    @if ($productionRecords->hasPages())
                        {{ $productionRecords->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @endif
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        .card,
        .btn,
        .form-control,
        .table,
        .content-header h1 {
            font-family: 'Roboto', sans-serif !important;
        }

        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        .table-hover tbody tr {
            transition: all 0.2s ease;
        }

        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        .table thead th {
            font-weight: 700 !important;
            font-size: 0.85rem;
        }

        .table tbody td {
            font-size: 0.875rem;
        }

        .search-box .input-group {
            width: 520px;
        }

        .search-box .form-control {
            border-radius: 20px 0 0 20px !important;
            padding: 0.5rem 1.5rem;
            height: 42px;
            font-size: 0.95rem;
        }

        .search-box .form-control:first-child {
            border-right: none;
        }

        .search-box .form-control:nth-child(2) {
            border-radius: 0 !important;
            border-left: 1px solid #dee2e6 !important;
            border-right: none;
        }

        .search-box .input-group-text {
            background-color: white;
            padding: 0 1.25rem;
            font-size: 1rem;
            border-left: none;
        }

        .search-box .input-group-text:last-child {
            border-radius: 0 20px 20px 0 !important;
        }

        .search-box .form-control:focus {
            border-color: #dee2e6 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .badge-status {
            display: inline-block;
            min-width: 60px;
            padding: 0.5em 0.75em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

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

        .text-muted.small {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .fw-500 {
            font-weight: 500;
        }

        .alert {
            border-radius: 8px;
        }

        .btn-close {
            background-size: 0.75rem;
            padding: 0.5rem;
        }

        /* Botón de PDF */
        .btn-danger {
            background-color: #dc3545;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .gap-2 {
            gap: 0.5rem;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cerrar alertas después de 5s
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (typeof bootstrap !== "undefined" && bootstrap.Alert) {
                        new bootstrap.Alert(alert).close();
                    }
                });
            }, 5000);

            // Fecha máxima del input date
            const dateInput = document.querySelector('input[name="date"]');
            if (dateInput) {
                const today = new Date().toISOString().split("T")[0];
                dateInput.setAttribute("max", today);
            }
        });
    </script>
@stop
