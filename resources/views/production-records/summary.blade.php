@extends('adminlte::page')

@section('title', 'Resumen de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Resumen de Producción</h1>
        </div>

        <a href="{{ route('production-records.summary.export', request()->query()) }}"
            class="btn-action btn-action-success" aria-label="Descargar resumen en Excel">
            <i class="fas fa-file-excel" aria-hidden="true"></i>
            <span class="d-none d-md-inline">Descargar Excel</span>
        </a>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('production-records.summary') }}">
                <div class="d-flex flex-wrap align-items-end justify-content-end" style="gap: 0.65rem;">

                    <div class="filter-field">
                        <label for="pr-summary-search" class="filter-label-text">N° de Parte</label>
                        <div class="filter-group">
                            <i class="fas fa-barcode filter-icon" aria-hidden="true"></i>
                            <input type="text" id="pr-summary-search" name="search" class="filter-input"
                                placeholder="Ej: PN-100..." value="{{ $search }}">
                        </div>
                    </div>

                    <x-multi-select
                        name="work_centers"
                        label="Estaciones"
                        placeholder="Todas las estaciones"
                        search-placeholder="Buscar estación..."
                        style="min-width: 220px;"
                        :options="$workCenters->map(fn ($wc) => [
                            'value' => $wc->name,
                            'label' => $wc->number . ' – ' . $wc->name,
                            'selected' => is_array($selectedCenters) && in_array($wc->name, $selectedCenters),
                        ])"
                    />

                    <x-select
                        name="shift"
                        label="Turno"
                        :options="collect([['value' => '', 'label' => 'Todos', 'selected' => !$selectedShift]])
                            ->concat($shifts->map(fn ($shift) => [
                                'value' => $shift->abbreviation,
                                'label' => $shift->abbreviation . ' – ' . $shift->name,
                                'selected' => $selectedShift === $shift->abbreviation,
                            ]))"
                    />

                    <div class="filter-field">
                        <label for="pr-summary-start" class="filter-label-text">Desde</label>
                        <div class="filter-group">
                            <i class="fas fa-calendar filter-icon" aria-hidden="true"></i>
                            <input type="text" id="pr-summary-start" name="startDate"
                                class="filter-input flatpickr-date" placeholder="Seleccionar fecha"
                                value="{{ $startDate }}">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="pr-summary-end" class="filter-label-text">Hasta</label>
                        <div class="filter-group">
                            <i class="fas fa-calendar-check filter-icon" aria-hidden="true"></i>
                            <input type="text" id="pr-summary-end" name="endDate"
                                class="filter-input flatpickr-date" placeholder="Seleccionar fecha"
                                value="{{ $endDate }}">
                        </div>
                    </div>

                    <div class="d-flex" style="gap: 0.4rem; padding-bottom: 1px;">
                        <button type="submit" class="btn-action btn-action-solid">
                            <i class="fas fa-filter mr-1"></i>Filtrar
                        </button>
                        @if ($hasFilters)
                            <a href="{{ route('production-records.summary') }}" class="btn-action btn-action-secondary">
                                <i class="fas fa-times mr-1"></i>Limpiar
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell" scope="col">Estación</th>
                            <th class="th-cell" scope="col">N° de Parte</th>
                            <th class="th-cell" scope="col">Fecha</th>
                            <th class="th-cell" scope="col">Turno</th>
                            <th class="th-cell text-center" scope="col">Plan</th>
                            <th class="th-cell text-center" scope="col">Real</th>
                            <th class="th-cell" scope="col">Inicio</th>
                            <th class="th-cell" scope="col">Término</th>
                            <th class="th-cell text-center" scope="col">Tiempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $rec)
                            @php
                                $prod = $rec->produced_quantity;
                                $plan = $rec->planned_quantity;
                                $color = $prod >= $plan ? 'success' : ($prod >= $plan * 0.8 ? 'warning' : 'danger');
                            @endphp
                            <tr class="td-row">
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $rec->work_number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $rec->work_name }}</div>
                                </td>
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $rec->part_number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $rec->part_name }}</div>
                                </td>
                                <td class="td-cell">
                                    <div style="font-size: 0.82rem; color: #334155;">
                                        {{ \Carbon\Carbon::parse($rec->planned_date)->format('d-m-Y') }}
                                    </div>
                                </td>
                                <td class="td-cell">
                                    <span class="badge-soft badge-primary">{{ $rec->shift_name }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary">{{ number_format($plan) }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-{{ $color }}">{{ number_format($prod) }}</span>
                                </td>
                                <td class="td-cell">
                                    <span style="font-size: 0.82rem; color: #475569; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $rec->production_start ? \Carbon\Carbon::parse($rec->production_start)->format('H:i') : '—' }}
                                    </span>
                                </td>
                                <td class="td-cell">
                                    <span style="font-size: 0.82rem; color: #475569; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $rec->production_end ? \Carbon\Carbon::parse($rec->production_end)->format('H:i') : '—' }}
                                    </span>
                                </td>
                                <td class="td-cell text-center">
                                    @if ($rec->production_start && $rec->production_end)
                                        @php
                                            $minutes = (int) \Carbon\Carbon::parse($rec->production_start)
                                                ->diffInMinutes(\Carbon\Carbon::parse($rec->production_end));
                                        @endphp
                                        <span class="badge-soft badge-secondary" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                            {{ $minutes }} min
                                        </span>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 0.82rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">No se encontraron registros</p>
                                        <small>Intenta ajustar los filtros de búsqueda</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($productionRecords->count() > 0)
                        <tfoot style="background: #f8fafc; border-top: 2px solid #e2e8f0; position: sticky; bottom: 0; z-index: 5;">
                            <tr>
                                <td colspan="4" class="td-cell text-right fw-600" style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em;">
                                    Totales
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary fw-600">
                                        {{ number_format($productionRecords->sum('planned_quantity')) }}
                                    </span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-primary fw-600">
                                        {{ number_format($productionRecords->sum('produced_quantity')) }}
                                    </span>
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
    @include('partials.theme-datepicker-styles')
@stop

@section('js')
    @include('partials.theme-scripts')
    @include('partials.theme-datepicker-scripts')
@stop
