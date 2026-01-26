@extends('adminlte::page')

@section('title', 'Resumen de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="fw-bold">{{ __('Resumen de Producción') }}</h1>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('production-records.summary') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">N° de Parte</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Ej: PN-100..." value="{{ $search }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Estaciones</label>
                        <select name="work_centers[]" id="work_centers" class="form-select select2" multiple="multiple">
                            @foreach ($workCenters as $wc)
                                <option value="{{ $wc->name }}"
                                    {{ (is_array($selectedCenters) && in_array($wc->name, $selectedCenters)) ? 'selected' : '' }}>
                                    {{ $wc->number }} - {{ $wc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-secondary mb-1">Desde</label>
                        <input type="date" name="startDate" class="form-control" value="{{ $startDate }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-secondary mb-1">Hasta</label>
                        <input type="date" name="endDate" class="form-control" value="{{ $endDate }}">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-3 shadow-sm fw-500">
                            <i class="fas fa-filter mr-2"></i> Filtrar
                        </button>

                        {{-- El botón Limpiar solo aparece si hay filtros activos --}}
                        @if($hasFilters)
                            <a href="{{ route('production-records.summary') }}"
                               class="btn btn-outline-danger rounded-3 ml-2"
                               title="Borrar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive table-container">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Estación</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">N° de Parte</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Fecha</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Turno</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Plan</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Real</th>
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
                                <td>
                                    <div class="fw-500">{{ $rec->part_number }}</div>
                                    <div class="text-muted small">{{ $rec->part_name }}</div>
                                </td>
                                <td class="small fw-500 text-secondary">
                                    {{ \Carbon\Carbon::parse($rec->planned_date)->format('d/m/Y') }}
                                </td>
                                <td>
                                    <span class="badge-status badge-primary">{{ $rec->shift_name }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-status badge-secondary">{{ number_format($plan) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-status badge-{{ $color }}">{{ number_format($prod) }}</span>
                                </td>
                                <td class="text-muted small">
                                    {{ $rec->production_start ? \Carbon\Carbon::parse($rec->production_start)->format('H:i') : '--:--' }}
                                </td>
                                <td class="text-muted small">
                                    {{ $rec->production_end ? \Carbon\Carbon::parse($rec->production_end)->format('H:i') : '--:--' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-search fa-3x text-light mb-3"></i>
                                    <p class="text-muted">No se encontraron registros coincidentes.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($productionRecords->count() > 0)
                    <tfoot class="bg-light sticky-bottom">
                        <tr>
                            <th colspan="4" class="text-end py-3 text-secondary">TOTALES:</th>
                            <th class="text-center">
                                <span class="badge-status badge-secondary">
                                    {{ number_format($productionRecords->sum('planned_quantity')) }}
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="badge-status badge-primary">
                                    {{ number_format($productionRecords->sum('produced_quantity')) }}
                                </span>
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .card, .btn, .form-control, .form-select, .table, .content-header h1 { font-family: 'Roboto', sans-serif !important; }

        /* Contenedor de tabla con scroll elegante */
        .table-container { max-height: 600px; overflow-y: auto; position: relative; }
        .table-container thead th { position: sticky; top: 0; z-index: 10; background-color: #f8f9fa; }
        .table-container tfoot th { position: sticky; bottom: 0; z-index: 10; background-color: #f8f9fa; box-shadow: 0 -2px 5px rgba(0,0,0,0.05); }

        .border-light-subtle { border-color: #f0f0f0 !important; }
        .rounded-3 { border-radius: 12px !important; }
        .fw-500 { font-weight: 500; }

        /* RE-APLICANDO BADGES PASTELES CON BORDE */
        .badge-status {
            display: inline-block;
            min-width: 85px;
            padding: 0.5em 0.75em;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            border: 1px solid transparent;
        }
        .badge-primary { background: rgba(13,110,253,0.1) !important; color: #0d6efd !important; border-color: rgba(13,110,253,0.2) !important; }
        .badge-secondary { background: rgba(108,117,125,0.1) !important; color: #6c757d !important; border-color: rgba(108,117,125,0.2) !important; }
        .badge-success { background: rgba(25,135,84,0.1) !important; color: #198754 !important; border-color: rgba(25,135,84,0.2) !important; }
        .badge-danger { background: rgba(220,53,69,0.1) !important; color: #dc3545 !important; border-color: rgba(220,53,69,0.2) !important; }
        .badge-warning { background: rgba(255,193,7,0.1) !important; color: #856404 !important; border-color: rgba(255,193,7,0.2) !important; }

        /* RE-APLICANDO ESTILO SELECT2 AZUL */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            min-height: 42px !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #0d6efd !important;
            border: none !important;
            color: white !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            margin-top: 6px !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white !important;
            margin-right: 5px !important;
            border: none !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            background: transparent !important;
            color: #ffcccc !important;
        }

        .btn-primary { background: #1a73e8; border: none; height: 42px; }
        .form-control { height: 42px; border-radius: 8px; }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#work_centers').select2({
                placeholder: " Todas las estaciones",
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            });
        });
    </script>
@stop
