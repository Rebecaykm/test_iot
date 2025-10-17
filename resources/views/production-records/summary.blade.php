@extends('adminlte::page')

@section('title', 'Resumen de Producción')

@section('content_header')
    <h1 class="m-0 text-dark">{{ __('Resumen de Producción') }}</h1>
@stop

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <!-- Filtros -->
            <form method="GET" action="{{ route('production-records.summary') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <!-- Estación -->
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

                    <!-- Fechas -->
                    <div class="col-md-2">
                        <label for="startDate" class="form-label small fw-semibold text-muted mb-1">Desde</label>
                        <input type="date" name="startDate" id="startDate" class="form-control"
                            value="{{ $startDate }}">
                    </div>
                    <div class="col-md-2">
                        <label for="endDate" class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                        <input type="date" name="endDate" id="endDate" class="form-control"
                            value="{{ $endDate }}">
                    </div>

                    <!-- Búsqueda -->
                    <div class="col-md-3">
                        <label for="search" class="form-label small fw-semibold text-muted mb-1">N° Parte</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Buscar..."
                            value="{{ $search }}">
                    </div>

                    <!-- Botón -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tabla -->
            <div class="table-container">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Estación</th>
                            <th>N° de Parte</th>
                            <th>Fecha Planeada</th>
                            <th>Turno</th>
                            <th class="text-end">Cantidad Plan</th>
                            <th class="text-end">Cantidad Real</th>
                            <th class="text-end">Scrap</th>
                            <th>Inicio</th>
                            <th>Término</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $rec)
                            <tr>
                                <td>
                                    <div class="fw-medium text-dark">{{ $rec->work_number }}</div>
                                    <small class="text-muted">{{ $rec->work_name }}</small>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $rec->part_number }}</div>
                                    <small class="text-muted">{{ $rec->part_name }}</small>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($rec->planned_date)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $rec->shift_name }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-secondary">{{ number_format($rec->planned_quantity) }}</span>
                                </td>
                                <td class="text-end">
                                    @php
                                        $prod = $rec->produced_quantity;
                                        $plan = $rec->planned_quantity;
                                        $color = $prod < $plan ? 'danger' : ($prod == $plan ? 'success' : 'warning');
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ number_format($prod) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-dark fw-medium">{{ number_format($rec->scrap_quantity) }}</span>
                                </td>
                                <td class="text-muted small">
                                    {{ $rec->production_start ? \Carbon\Carbon::parse($rec->production_start)->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="text-muted small">
                                    {{ $rec->production_end ? \Carbon\Carbon::parse($rec->production_end)->format('d/m/Y H:i') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No se encontraron registros
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Fuente moderna */
        body,
        .card,
        .btn,
        .form-control,
        .table,
        h1,
        .main-header,
        .main-sidebar,
        .content-wrapper {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        }

        /* Card mejorada */
        .card {
            border-radius: 12px;
            overflow: hidden;
        }

        /* Tabla contenedor con scroll */
        .table-container {
            max-height: 650px;
            overflow-y: auto;
            overflow-x: hidden;
            /* Oculta horizontal por defecto */
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            position: relative;
            /* Necesario para hover */
        }

        .table-container:hover::-webkit-scrollbar {
            height: 8px;
        }

        /* Encabezados sticky */
        .table thead th {
            position: sticky;
            top: 0;
            background: linear-gradient(to bottom, #f9fafb 0%, #f3f4f6 100%);
            font-weight: 600;
            font-size: 0.813rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
            padding: 1rem 0.75rem;
            z-index: 10;
        }

        /* Footer sticky */
        .table tfoot th {
            position: sticky;
            bottom: 0;
            background: #f9fafb;
            font-weight: 600;
            border-top: 2px solid #e5e7eb;
            padding: 1rem 0.75rem;
            z-index: 10;
        }

        /* Filas de la tabla */
        .table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.15s ease;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
            transform: scale(1.001);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .table tbody td {
            padding: 0.875rem 0.75rem;
            vertical-align: middle;
        }

        /* Badges mejorados */
        .badge {
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            font-size: 0.813rem;
            border-radius: 6px;
            min-width: 60px;
            display: inline-block;
        }

        /* Formulario */
        .form-control,
        .form-select {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.938rem;
            transition: all 0.15s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Botón */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        /* Select2 */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        /* Textos */
        .fw-medium {
            font-weight: 500;
        }

        /* Scrollbar personalizada */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 0px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f3f4f6;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
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
