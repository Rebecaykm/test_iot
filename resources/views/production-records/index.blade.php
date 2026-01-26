@extends('adminlte::page')

@section('title', 'Registros de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1>{{ __('Registros de Producción') }}</h1>

        <div class="d-flex gap-2">
            <a href="{{ route('line-stoppage-records.create', ['source' => 'production-records.index']) }}"
                class="btn btn-outline-warning rounded-3 shadow-sm mr-1">
                <i class="fas fa-pause me-2"></i>
                <span class="d-none d-md-inline">Paro de Línea</span>
            </a>

            <a href="{{ route('scrap-records.create', ['source' => 'production-records.index']) }}"
                class="btn btn-outline-danger rounded-3 shadow-sm mr-1">
                <i class="fas fa-exclamation-circle me-2"></i>
                <span class="d-none d-md-inline">Scrap</span>
            </a>

            <a href="{{ route('production.export-pdf-form') }}" class="btn btn-outline-primary rounded-3 shadow-sm">
                <i class="fas fa-file mr-1"></i>
                <span class="d-none d-md-inline">Reporte PDF</span>
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
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-end">
                    <div class="search-box">
                        <form method="GET" action="{{ route('production-records.index') }}" id="searchForm">
                            <div class="d-flex flex-column flex-md-row gap-2">
                                <div class="input-group" style="width: 200px;">
                                    <input type="date" name="date" class="form-control" value="{{ request('date') }}"
                                        max="{{ date('Y-m-d') }}">
                                </div>

                                <div class="input-group" style="width: 300px;">
                                    <input type="text" name="search" class="form-control border-end-0"
                                        placeholder="Buscar..." value="{{ request('search') }}">
                                    <button type="submit" class="input-group-text bg-white border-start-0">
                                        <i class="fas fa-search text-secondary"></i>
                                    </button>
                                    @if (request()->filled('search') || request()->filled('date'))
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
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle">Planeada</th>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle">Producida</th>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle">Progreso</th>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle">Estado</th>
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
                                    } elseif ($percentage < 100) {
                                        $color = 'warning';
                                    } elseif ($percentage == 100) {
                                        $color = 'success';
                                    } else {
                                        $color = 'warning';
                                    }

                                    $progressWidth = min(100, $percentage);
                                @endphp

                                <tr class="border-light-subtle">
                                    <td class="py-3">
                                        <div>{{ $record->work_name }}</div>
                                        <div class="text-muted small">{{ $record->work_number }}</div>
                                    </td>
                                    <td class="py-3">
                                        <div>{{ $record->part_number }}</div>
                                        <div class="text-muted small">{{ $record->part_name }}</div>
                                    </td>
                                    <td class="py-3">{{ $record->shop_order_number }}</td>
                                    <td class="py-3">
                                        <div>{{ \Carbon\Carbon::parse($record->planned_date)->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge-status badge-primary">{{ $record->shift_name }}</span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="badge-status badge-secondary">{{ number_format($plan) }}</span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span
                                            class="badge-status badge-{{ $color }}">{{ number_format($prod) }}</span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="progress w-75" style="height: 8px;">
                                                <div class="progress-bar bg-{{ $color }}"
                                                    style="width: {{ $progressWidth }}%;"></div>
                                            </div>
                                            <small class="text-muted mt-1">{{ number_format($percentage, 1) }}%</small>
                                        </div>
                                    </td>

                                    <td class="py-3 text-center">
                                        @if ($record->synced_to_infor)
                                            <span class="badge-status badge-success" title="Enviado">
                                                <i class="fas fa-check mr-1"></i> Enviado
                                            </span>
                                        @else
                                            @switch($record->status_name)
                                                @case('Completado')
                                                    <span class="badge-status badge-success">
                                                        <i class="fas fa-check-circle mr-1"></i> {{ $record->status_name }}
                                                    </span>
                                                @break

                                                @case('En progreso')
                                                    <span class="badge-status badge-primary">
                                                        <i class="fas fa-sync-alt mr-1"></i> {{ $record->status_name }}
                                                    </span>
                                                @break

                                                @case('Detenido')
                                                    <span class="badge-status badge-danger">
                                                        <i class="fas fa-stop-circle mr-1"></i> {{ $record->status_name }}
                                                    </span>
                                                @break

                                                @default
                                                    <span class="badge-status badge-secondary">
                                                        <i class="fas fa-info-circle mr-1"></i> {{ $record->status_name }}
                                                    </span>
                                            @endswitch
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No se encontraron registros
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($productionRecords->hasPages())
                        <div class="card-footer bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Mostrando {{ $productionRecords->firstItem() }} a
                                    {{ $productionRecords->lastItem() }} de {{ $productionRecords->total() }}</small>
                                {{ $productionRecords->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @stop

        @section('css')
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

                .rounded-3 {
                    border-radius: 12px !important;
                }

                .table thead th {
                    font-weight: 700 !important;
                    font-size: 0.85rem;
                }

                .search-box .input-group {
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                    border-radius: 8px;
                }

                .search-box .form-control {
                    border: 1px solid #e0e0e0;
                    height: 42px;
                }

                .badge-status {
                    display: inline-block;
                    min-width: 95px;
                    padding: 0.5em 0.8em;
                    border-radius: 12px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    border: 1px solid;
                    text-align: center;
                }

                .badge-status.badge-primary {
                    background: rgba(13, 110, 253, 0.1);
                    color: #0d6efd;
                    border-color: rgba(13, 110, 253, 0.2);
                }

                .badge-status.badge-success {
                    background: rgba(25, 135, 84, 0.1);
                    color: #198754;
                    border-color: rgba(25, 135, 84, 0.2);
                }

                .badge-status.badge-danger {
                    background: rgba(220, 53, 69, 0.1);
                    color: #dc3545;
                    border-color: rgba(220, 53, 69, 0.2);
                }

                .badge-status.badge-secondary {
                    background: rgba(108, 117, 125, 0.1);
                    color: #6c757d;
                    border-color: rgba(108, 117, 125, 0.2);
                }

                .badge-status.badge-warning {
                    background: rgba(255, 193, 7, 0.1);
                    color: #856404;
                    border-color: rgba(255, 193, 7, 0.2);
                }

                .progress {
                    border-radius: 10px;
                    background-color: #f0f0f0;
                }

                .fw-500 {
                    font-weight: 500;
                }

                @media (max-width: 768px) {
                    .search-box .input-group {
                        width: 100% !important;
                    }

                    .d-flex.justify-content-end {
                        justify-content: center !important;
                    }
                }
            </style>
        @stop

        @section('js')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Auto-cerrar alertas
                    setTimeout(() => {
                        const alerts = document.querySelectorAll('.alert');
                        alerts.forEach(alert => {
                            if (window.bootstrap && bootstrap.Alert) {
                                new bootstrap.Alert(alert).close();
                            }
                        });
                    }, 5000);
                });
            </script>
        @stop
