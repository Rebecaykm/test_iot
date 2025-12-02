@extends('adminlte::page')

@section('title', 'Resumen de Producción')

@section('content_header')
    <h1>{{ __('Resumen de Producción') }}</h1>
@stop

@section('content')
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <!-- Filtros -->
        <div class="card-header bg-white border-0 py-3">
            <form method="GET" action="{{ route('production-records.summary') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="work_center" class="form-label small fw-semibold text-muted mb-1">Estación</label>
                        <select name="work_center" id="work_center" class="form-select select2">
                            <option value="">Todas las estaciones</option>
                            @foreach ($workCenters as $wc)
                                <option value="{{ $wc->name }}" @selected($selectedCenter === $wc->name)>
                                    {{ $wc->number }} - {{ $wc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="startDate" class="form-label small fw-semibold text-muted mb-1">Desde</label>
                        <input type="date" name="startDate" id="startDate" class="form-control" value="{{ $startDate }}">
                    </div>

                    <div class="col-md-2">
                        <label for="endDate" class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                        <input type="date" name="endDate" id="endDate" class="form-control" value="{{ $endDate }}">
                    </div>

                    <div class="col-md-3">
                        <label for="search" class="form-label small fw-semibold text-muted mb-1">N° Parte</label>
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="Buscar..." value="{{ $search }}">
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-3">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Estación</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">N° de Parte</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Fecha Planeada</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Turno</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">Cantidad Plan</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">Cantidad Real</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">Scrap</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Inicio</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Término</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $rec)
                            @php
                                $prod = $rec->produced_quantity;
                                $plan = $rec->planned_quantity;
                                $color = $prod < $plan ? 'danger' : ($prod == $plan ? 'success' : 'warning');
                            @endphp
                            <tr class="border-light-subtle">
                                <td class="py-3">
                                    <div class="fw-500">{{ $rec->work_number }}</div>
                                    <div class="text-muted small">{{ $rec->work_name }}</div>
                                </td>

                                <td class="py-3">
                                    <div class="fw-500">{{ $rec->part_number }}</div>
                                    <div class="text-muted small">{{ $rec->part_name }}</div>
                                </td>

                                <td class="py-3 small">
                                    <div class="fw-500">{{ \Carbon\Carbon::parse($rec->planned_date)->format('d/m/Y') }}</div>
                                </td>

                                <td class="py-3">
                                    <span class="badge-status bg-primary bg-opacity-10 text-primary">{{ $rec->shift_name }}</span>
                                </td>

                                <td class="py-3 text-end">
                                    <span class="badge-status bg-secondary bg-opacity-10 text-secondary">{{ number_format($plan) }}</span>
                                </td>

                                <td class="py-3 text-end">
                                    <span class="badge-status bg-{{ $color }} bg-opacity-10 text-{{ $color }}">{{ number_format($prod) }}</span>
                                </td>

                                <td class="py-3 text-end">
                                    <span class="fw-500">{{ number_format($rec->scrap_quantity) }}</span>
                                </td>

                                <td class="py-3 text-muted small">
                                    {{ $rec->production_start ? \Carbon\Carbon::parse($rec->production_start)->format('d/m/Y H:i') : '—' }}
                                </td>

                                <td class="py-3 text-muted small">
                                    {{ $rec->production_end ? \Carbon\Carbon::parse($rec->production_end)->format('d/m/Y H:i') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">No se encontraron registros</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="4" class="text-end py-3">Totales:</th>
                            <th class="text-end py-3">
                                <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                    {{ number_format($productionRecords->sum('planned_quantity')) }}
                                </span>
                            </th>
                            <th class="text-end py-3">
                                <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                    {{ number_format($productionRecords->sum('produced_quantity')) }}
                                </span>
                            </th>
                            <th class="text-end py-3">
                                <span class="fw-bold">{{ number_format($productionRecords->sum('scrap_quantity')) }}</span>
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        .card, .btn, .form-control, .table, .content-header h1 {
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

        .table tfoot th {
            font-weight: 600 !important;
            font-size: 0.875rem;
            color: #495057;
        }

        .form-control, .form-select {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.938rem;
            transition: all 0.15s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }

        .btn-primary {
            background: #1a73e8;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .btn-primary:hover {
            background: #1557b0;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
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

        .text-muted.small {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .fw-500 {
            font-weight: 500;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#work_center').select2({
                placeholder: "Seleccione estación",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@stop
