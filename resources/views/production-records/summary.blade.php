@extends('adminlte::page')

@section('title', 'Resumen de Producción')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark">{{ __('Resumen de Producción') }}</h1>
</div>
@stop

@section('content')
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-body">
        <!-- Filtros Simplificados -->
        <form method="GET" action="{{ route('production-records.summary') }}" class="row g-3 mb-4 align-items-end">
            <!-- Estación -->
            <div class="col-md-3 col-sm-6">
                <label for="work_center" class="form-label mb-1 small fw-semibold text-muted">Estación</label>
                <select name="work_center" id="work_center" class="form-select select2">
                    <option value="">Todas las estaciones</option>
                    @foreach($workCenters as $wc)
                        <option value="{{ $wc->name }}" @selected($selectedCenter === $wc->name)>
                            {{ $wc->number }} - {{ $wc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Fechas -->
            <div class="col-md-2 col-sm-6">
                <label for="startDate" class="form-label mb-1">Desde</label>
                <input type="date" name="startDate" id="startDate" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="endDate" class="form-label mb-1">Hasta</label>
                <input type="date" name="endDate" id="endDate" class="form-control" value="{{ $endDate }}">
            </div>

            <!-- Búsqueda -->
            <div class="col-md-3 col-sm-6">
                <label for="search" class="form-label mb-1">N° Parte</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Buscar..." value="{{ $search }}">
            </div>

            <!-- Botón Filtrar -->
            <div class="col-md-2 col-sm-6">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>

        {{-- Tabla --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Estación') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Número de Parte') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Fecha Planeada') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Turno') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">{{ __('Planeada') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">{{ __('Producida') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle text-end">{{ __('Scrap') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Inicio') }}</th>
                        <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Fin') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productionRecords as $rec)
                        <tr class="border-light-subtle">
                            <td class="py-3">
                                <div class="fw-500">{{ $rec->work_number }}</div>
                                <small class="text-muted">{{ $rec->work_name }}</small>
                            </td>
                            <td class="py-3">
                                <div class="fw-500">{{ $rec->part_number }}</div>
                                <small class="text-muted">{{ $rec->part_name }}</small>
                            </td>
                            <td class="py-3">{{ \Carbon\Carbon::parse($rec->planned_date)->format('d-m-Y') }}</td>
                            <td class="py-3">
                                <span class="badge-status bg-primary text-white">
                                    {{ $rec->shift_name }}
                                </span>
                            </td>
                            <td class="py-3 text-end">
                                <span class="badge-status bg-primary text-white">
                                    {{ number_format($rec->planned_quantity) }}
                                </span>
                            </td>
                            <td class="py-3 text-end">
                                @php
                                    $prod = $rec->produced_quantity;
                                    $plan = $rec->planned_quantity;
                                    $color = $prod < $plan ? 'bg-danger text-white'
                                            : ($prod == $plan ? 'bg-success text-white' : 'bg-warning text-dark');
                                @endphp
                                <span class="badge-status {{ $color }}">
                                    {{ number_format($prod) }}
                                </span>
                            </td>
                            <td class="py-3 text-end">{{ number_format($rec->scrap_quantity) }}</td>
                            <td class="py-3"><small class="text-muted">{{ \Carbon\Carbon::parse($rec->production_start)->format('d-m-Y H:i') }}</small></td>
                            <td class="py-3"><small class="text-muted">{{ \Carbon\Carbon::parse($rec->production_end)->format('d-m-Y H:i') }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No se encontraron registros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="4" class="text-end">Totales:</th>
                        <th class="text-end">{{ number_format($productionRecords->sum('planned_quantity')) }}</th>
                        <th class="text-end">{{ number_format($productionRecords->sum('produced_quantity')) }}</th>
                        <th class="text-end">{{ number_format($productionRecords->sum('scrap_quantity')) }}</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
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
        /* Aplicar fuente */
        body, .main-header, .main-sidebar, .content-wrapper,
        .card, .btn, .form-control, .table, h1 {
            font-family: 'Roboto', sans-serif !important;
        }

        /* Tabla con altura máxima */
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        /* Sticky headers/footers */
        .table-responsive thead th,
        .table-responsive tfoot th {
            position: sticky;
            background: #f8f9fa;
            z-index: 10;
        }
        thead th { top: 0; }
        tfoot th { bottom: 0; }

        /* Hover suavizado */
        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        /* Bordes y tipografía */
        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }
        .fw-500 { font-weight: 500 !important; }
        .fw-semibold { font-weight: 600 !important; }

        /* Badges personalizados */
        .badge-status {
            display: inline-block;
            min-width: 60px;
            padding: 0.35em 0.65em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Select2 adaptado */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
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
