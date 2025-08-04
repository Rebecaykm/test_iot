@extends('adminlte::page')

@section('title', 'Resumen de Producción')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark">{{ __('Resumen de Producción') }}</h1>
</div>
@stop

@section('content')
<div class="card border-0 shadow-sm rounded-3">
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
                        <th>Estación</th>
                        <th>Número de Parte</th>
                        <th>Fecha Planeada</th>
                        <th>Turno</th>
                        <th class="text-end">Planeada</th>
                        <th class="text-end">Producida</th>
                        <th class="text-end">Scrap</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productionRecords as $rec)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $rec->work_number }}</div>
                                <small class="text-muted">{{ $rec->work_name }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $rec->part_number }}</div>
                                <small class="text-muted">{{ $rec->part_name }}</small>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($rec->planned_date)->format('d-m-Y') }}</td>
                            <td><span class="badge bg-primary">{{ $rec->shift_name }}</span></td>
                            <td class="text-end">{{ number_format($rec->planned_quantity) }}</td>
                            <td class="text-end">
                                @php
                                    $prod = $rec->produced_quantity;
                                    $plan = $rec->planned_quantity;
                                    $color = $prod < $plan ? 'bg-danger' : ($prod == $plan ? 'bg-success' : 'bg-warning');
                                @endphp
                                <span class="badge {{ $color }}">{{ number_format($prod) }}</span>
                            </td>
                            <td class="text-end">{{ number_format($rec->scrap_quantity) }}</td>
                            <td><small class="text-muted">{{ \Carbon\Carbon::parse($rec->production_start)->format('d-m-Y H:i') }}</small></td>
                            <td><small class="text-muted">{{ \Carbon\Carbon::parse($rec->production_end)->format('d-m-Y H:i') }}</small></td>
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
<style>
    /* Estilos mínimos y esenciales */
    .table-responsive {
        max-height: 600px;
        overflow-y: auto;
    }

    .table-responsive thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 10;
    }

    .table-responsive tfoot th {
        position: sticky;
        bottom: 0;
        background: #f8f9fa;
        z-index: 10;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge {
        min-width: 60px;
        font-weight: 500;
        padding: 0.35em 0.65em;
    }

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
