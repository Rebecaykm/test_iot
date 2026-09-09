@extends('adminlte::page')

@section('title', 'Secuencias de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Secuencias de Producción</h1>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('production-sequences.index') }}">
                <div class="d-flex flex-wrap align-items-end justify-content-end gap-2">

                    <div class="filter-field" style="min-width: 220px;">
                        <label for="ps-search" class="filter-label-text">N° de Parte</label>
                        <div class="filter-group">
                            <i class="fas fa-barcode filter-icon" aria-hidden="true"></i>
                            <input type="text" id="ps-search" name="search" class="filter-input"
                                placeholder="Buscar parte..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="ps-date" class="filter-label-text">Fecha</label>
                        <div class="filter-group">
                            <i class="fas fa-calendar-alt filter-icon" aria-hidden="true"></i>
                            <input type="text" id="ps-date" name="date" class="filter-input flatpickr-date"
                                placeholder="Seleccionar fecha" value="{{ request('date') }}"
                                max="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <x-select
                        name="shift"
                        label="Turno"
                        :options="collect([['value' => '', 'label' => 'Todos', 'selected' => !request('shift')]])
                            ->concat($shifts->map(fn ($shift) => [
                                'value' => $shift->abbreviation,
                                'label' => $shift->abbreviation . ' – ' . $shift->name,
                                'selected' => request('shift') === $shift->abbreviation,
                            ]))"
                    />

                    <div class="d-flex" style="gap: 0.4rem; padding-bottom: 1px;">
                        <button type="submit" class="btn-action btn-action-solid">
                            <i class="fas fa-search mr-1"></i>Buscar
                        </button>
                        @if (request()->filled('search') || request()->filled('date') || request()->filled('shift'))
                            <a href="{{ route('production-sequences.index') }}" class="btn-action btn-action-secondary">
                                <i class="fas fa-times mr-1"></i>Limpiar
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell" scope="col">N° de Orden</th>
                            <th class="th-cell" scope="col">N° de Parte</th>
                            <th class="th-cell" scope="col">Fecha</th>
                            <th class="th-cell" scope="col">Turno</th>
                            <th class="th-cell text-center" scope="col">Secuencia</th>
                            <th class="th-cell text-center" scope="col">Cantidad</th>
                            <th class="th-cell text-center" scope="col">Procesado</th>
                            <th class="th-cell" scope="col">Actualizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sequences as $sequence)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $sequence->order_number }}</div>
                                </td>
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $sequence->part_number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $sequence->part_name }}</div>
                                </td>
                                <td class="td-cell">
                                    <div style="font-size: 0.82rem; color: #334155;">
                                        {{ \Carbon\Carbon::parse($sequence->planned_date)->format('d-m-Y') }}
                                    </div>
                                </td>
                                <td class="td-cell">
                                    @if ($sequence->shift_name)
                                        <span class="badge-soft badge-primary">{{ $sequence->shift_name }}</span>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 0.82rem;">—</span>
                                    @endif
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $sequence->sequence_number }}
                                    </span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary">{{ number_format($sequence->quantity) }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    @if ($sequence->is_processed)
                                        <span class="badge-soft badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>Procesado
                                        </span>
                                    @else
                                        <span class="badge-soft badge-danger">
                                            <i class="fas fa-clock mr-1"></i>Pendiente
                                        </span>
                                    @endif
                                </td>
                                <td class="td-cell">
                                    <span style="font-size: 0.82rem; color: #475569; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ \Carbon\Carbon::parse($sequence->updated_at)->format('d-m-Y H:i') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">No se encontraron secuencias</p>
                                        <small>Intenta ajustar los filtros de búsqueda</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación --}}
        @if ($sequences->hasPages())
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando <strong>{{ $sequences->firstItem() }}</strong> –
                        <strong>{{ $sequences->lastItem() }}</strong> de
                        <strong>{{ $sequences->total() }}</strong> secuencias
                    </small>
                    {{ $sequences->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif

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
