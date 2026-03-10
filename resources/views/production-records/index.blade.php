@extends('adminlte::page')

@section('title', 'Registros de Producción')

@section('content_header')
    <h1>{{ __('Registros de Producción') }}</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-lg overflow-hidden">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">

                <div>
                    <a href="{{ route('line-stoppage-records.create', ['source' => 'production-records.index']) }}"
                        class="btn btn-warning btn-sm mr-1">
                        <i class="fas fa-pause mr-1"></i> Paro de Línea
                    </a>

                    <a href="{{ route('scrap-records.create', ['source' => 'production-records.index']) }}"
                        class="btn btn-danger btn-sm mr-1">
                        <i class="fas fa-trash mr-1"></i> Scrap
                    </a>

                    <a href="{{ route('production.export-pdf-form') }}"
                        class="btn btn-primary btn-sm">
                        <i class="fas fa-file-pdf mr-1"></i> Reporte PDF
                    </a>
                </div>

                <form method="GET" action="{{ route('production-records.index') }}" id="searchForm">
                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        <input type="date" name="date" class="form-control form-control-sm" style="width: 180px;"
                            value="{{ request('date') }}" max="{{ date('Y-m-d') }}">

                        <div class="input-group input-group-sm" style="width: 280px;">
                            <input type="text" name="search" class="form-control"
                                placeholder="Buscar..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if (request()->filled('search') || request()->filled('date'))
                                    <a href="{{ route('production-records.index') }}"
                                        class="btn btn-outline-danger">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-0">Estación</th>
                            <th class="fw-bold text-secondary text-uppercase border-0">N° de Parte</th>
                            <th class="fw-bold text-secondary text-uppercase border-0">N° de Orden</th>
                            <th class="fw-bold text-secondary text-uppercase border-0">Fecha</th>
                            <th class="fw-bold text-secondary text-uppercase border-0">Turno</th>
                            <th class="fw-bold text-secondary text-uppercase border-0 text-center">Planeada</th>
                            <th class="fw-bold text-secondary text-uppercase border-0 text-center">Producida</th>
                            <th class="fw-bold text-secondary text-uppercase border-0 text-center">Progreso</th>
                            <th class="fw-bold text-secondary text-uppercase border-0 text-center">Estado</th>
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

                            <tr>
                                <td class="py-3">
                                    <div>{{ $record->work_name }}</div>
                                    <div class="text-muted small">{{ $record->work_number }}</div>
                                </td>
                                <td class="py-3">
                                    <div>{{ $record->part_number }}</div>
                                    <div class="text-muted small">{{ $record->part_name }}</div>
                                </td>
                                <td class="py-3">{{ $record->shop_order_number }}</td>
                                <td class="py-3">{{ \Carbon\Carbon::parse($record->planned_date)->format('d/m/Y') }}</td>
                                <td class="py-3">
                                    <span class="badge-status badge-primary">{{ $record->shift_name }}</span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="badge-status badge-secondary">{{ number_format($plan) }}</span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="badge-status badge-{{ $color }}">{{ number_format($prod) }}</span>
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
                                        <span class="badge-status badge-success">
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
                                <td colspan="9" class="text-center py-4 text-muted">No se encontraron registros</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($productionRecords->hasPages())
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Mostrando {{ $productionRecords->firstItem() }} a
                            {{ $productionRecords->lastItem() }} de {{ $productionRecords->total() }}
                        </small>
                        {{ $productionRecords->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <style>
        .badge-status {
            display: inline-block;
            min-width: 95px;
            padding: 0.4em 0.75em;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid transparent;
            text-align: center;
        }

        .badge-status.badge-primary  { background: rgba(13,110,253,.1);  color: #0d6efd; border-color: rgba(13,110,253,.2); }
        .badge-status.badge-success  { background: rgba(25,135,84,.1);   color: #198754; border-color: rgba(25,135,84,.2); }
        .badge-status.badge-danger   { background: rgba(220,53,69,.1);   color: #dc3545; border-color: rgba(220,53,69,.2); }
        .badge-status.badge-warning  { background: rgba(255,193,7,.1);   color: #856404; border-color: rgba(255,193,7,.2); }
        .badge-status.badge-secondary{ background: rgba(108,117,125,.1); color: #6c757d; border-color: rgba(108,117,125,.2); }

        .progress { border-radius: 10px; background-color: #f0f0f0; }

        .table thead th { font-size: 0.82rem; }
    </style>
@stop

@section('js')
    <script>
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(el => $(el).alert('close'));
        }, 5000);
    </script>
@stop
