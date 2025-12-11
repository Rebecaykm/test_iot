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
                    <!-- N° Parte -->
                    <div class="col-md-3">
                        <label for="search" class="form-label small fw-semibold text-muted mb-1">N° Parte</label>
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="Buscar..." value="{{ $search }}">
                    </div>

                    <!-- Estación (Múltiple) -->
                    <div class="col-md-3">
                        <label for="work_centers" class="form-label small fw-semibold text-muted mb-1">Estación</label>
                        <select name="work_centers[]" id="work_centers" class="form-select select2" multiple="multiple">
                            @foreach ($workCenters as $wc)
                                <option value="{{ $wc->name }}"
                                    @if(is_array($selectedCenters))
                                        {{ in_array($wc->name, $selectedCenters) ? 'selected' : '' }}
                                    @else
                                        {{ $selectedCenters === $wc->name ? 'selected' : '' }}
                                    @endif>
                                    {{ $wc->number }} - {{ $wc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha Desde -->
                    <div class="col-md-2">
                        <label for="startDate" class="form-label small fw-semibold text-muted mb-1">Desde</label>
                        <input type="date" name="startDate" id="startDate" class="form-control" value="{{ $startDate }}">
                    </div>

                    <!-- Fecha Hasta -->
                    <div class="col-md-2">
                        <label for="endDate" class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                        <input type="date" name="endDate" id="endDate" class="form-control" value="{{ $endDate }}">
                    </div>

                    <!-- Botón Filtrar -->
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
            <div class="table-responsive table-container">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Estación</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">N° de Parte</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Fecha Planeada</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Turno</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Cantidad Plan</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Cantidad Real</th>
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

                                <td class="py-3 text-center">
                                    <span class="badge-status bg-secondary bg-opacity-10 text-secondary">{{ number_format($plan) }}</span>
                                </td>

                                <td class="py-3 text-center">
                                    <span class="badge-status bg-{{ $color }} bg-opacity-10 text-{{ $color }}">{{ number_format($prod) }}</span>
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
                                        <span class="text-secondary">
                                            @if (!empty($search) || !empty($selectedCenters) || !empty($startDate) || !empty($endDate))
                                                No se encontraron registros para los filtros seleccionados
                                            @else
                                                No se encontraron registros
                                            @endif
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="4" class="text-end py-3">Totales:</th>
                            <th class="text-center py-3">
                                <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                    {{ number_format($productionRecords->sum('planned_quantity')) }}
                                </span>
                            </th>
                            <th class="text-center py-3">
                                <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                    {{ number_format($productionRecords->sum('produced_quantity')) }}
                                </span>
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
    <!-- Fuente Google Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Aplicar fuente a elementos específicos */
        .card,
        .btn,
        .form-control,
        .form-select,
        .table,
        .content-header h1 {
            font-family: 'Roboto', sans-serif !important;
        }

        /* Tabla con scroll - aumentada un poco */
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            position: relative;
        }

        /* Scrollbar personalizado */
        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .table-container thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa;
        }

        .table-container tfoot {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background-color: #f8f9fa;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
        }

        /* Estilos adicionales para la tabla */
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

        /* Mejoras en jerarquía tipográfica */
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

        /* Formularios */
        .form-control, .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.938rem;
            transition: all 0.15s ease;
            height: 42px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        /* Botón primario */
        .btn-primary {
            background: #1a73e8;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.15s ease;
            height: 42px;
        }

        .btn-primary:hover {
            background: #1557b0;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
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

        /* Texto adicional */
        .text-muted.small {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .fw-500 {
            font-weight: 500;
        }

        /* Select2 para múltiple selección */
        .select2-container--default .select2-selection--multiple {
            min-height: 42px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e7f1ff;
            border: 1px solid #0d6efd;
            border-radius: 6px;
            color: #0d6efd;
            padding: 2px 8px;
            margin-top: 5px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #0d6efd;
            margin-right: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .input-group {
                width: 100% !important;
            }

            .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .table-container {
                max-height: 500px;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Inicializar Select2 para selección múltiple
            $('#work_centers').select2({
                placeholder: "Seleccione estación(es)",
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            });

            // Auto-focus en el campo de búsqueda
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && !searchInput.value) {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            }

            // Limpiar búsqueda con Escape
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = '';
                    }
                });
            }

            // Configurar fecha máxima en los inputs de fecha
            const dateInputs = document.querySelectorAll('input[type="date"]');
            const today = new Date().toISOString().split('T')[0];

            dateInputs.forEach(input => {
                input.setAttribute('max', today);

                // Si no hay valor, establecer fechas por defecto
                if (!input.value && input.id === 'startDate') {
                    // Para la fecha inicial, poner la fecha de hace una semana
                    const oneWeekAgo = new Date();
                    oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
                    input.value = oneWeekAgo.toISOString().split('T')[0];
                }

                if (!input.value && input.id === 'endDate') {
                    input.value = today;
                }
            });
        });
    </script>
@stop
