@extends('adminlte::page')

@section('title', 'Registros de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Registros de Producción</h1>
        </div>

        <div class="d-flex gap-2">
            {{-- <a href="{{ route('line-stoppage-records.create', ['source' => 'production-records.index']) }}"
                class="btn-action btn-action-warning">
                <i class="fas fa-pause"></i>
                <span class="d-none d-md-inline">Paro de Línea</span>
            </a> --}}
            <a href="{{ route('scrap-records.create', ['source' => 'production-records.index']) }}"
                class="btn-action btn-action-danger" aria-label="Registrar scrap">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Scrap</span>
            </a>
            <a href="{{ route('production.export-pdf-form') }}" class="btn-action btn-action-primary"
                aria-label="Generar reporte PDF">
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Reporte PDF</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('production-records.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">

                    <div class="filter-group">
                        <label for="pr-date" class="sr-only">Fecha</label>
                        <i class="fas fa-calendar-alt filter-icon" aria-hidden="true"></i>
                        <input type="text" id="pr-date" name="date" class="filter-input flatpickr-date"
                            placeholder="Seleccionar fecha"
                            value="{{ request('date') }}" max="{{ date('Y-m-d') }}"
                            data-auto-submit="1">
                    </div>

                    <div class="filter-group" style="width: 260px;">
                        <label for="pr-search" class="sr-only">Buscar parte, orden o estación</label>
                        <i class="fas fa-search filter-icon" aria-hidden="true"></i>
                        <input type="text" id="pr-search" name="search" class="filter-input"
                            placeholder="Buscar parte, orden, estación..."
                            value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search') || request()->filled('date'))
                        <a href="{{ route('production-records.index') }}" class="btn-action btn-action-secondary">
                            <i class="fas fa-times mr-1"></i>Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell" scope="col">Estación</th>
                            <th class="th-cell" scope="col">N° de Parte</th>
                            <th class="th-cell" scope="col">N° de Orden</th>
                            <th class="th-cell" scope="col">Fecha</th>
                            <th class="th-cell" scope="col">Turno</th>
                            <th class="th-cell text-center" scope="col">Planeada</th>
                            <th class="th-cell text-center" scope="col">Producida</th>
                            <th class="th-cell text-center" style="min-width: 120px;" scope="col">Progreso</th>
                            <th class="th-cell text-center" scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $record)
                            @php
                                $prod = $record->produced_quantity;
                                $plan = $record->planned_quantity;
                                $percentage = $plan > 0 ? ($prod / $plan) * 100 : 0;
                                $progressWidth = min(100, $percentage);

                                if ($percentage >= 100) {
                                    $color = 'success';
                                } elseif ($percentage >= 80) {
                                    $color = 'warning';
                                } else {
                                    $color = 'danger';
                                }

                                $statusConfig = [
                                    'Completado'  => ['badge' => 'success', 'icon' => 'check-circle'],
                                    'En progreso' => ['badge' => 'primary', 'icon' => 'sync-alt'],
                                    'Detenido'    => ['badge' => 'danger',  'icon' => 'stop-circle'],
                                ];
                                $statusCfg = $statusConfig[$record->status_name] ?? ['badge' => 'secondary', 'icon' => 'info-circle'];
                            @endphp

                            <tr class="td-row">
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $record->work_name }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $record->work_number }}</div>
                                </td>
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $record->part_number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $record->part_name }}</div>
                                </td>
                                <td class="td-cell">
                                    <span class="badge-soft badge-secondary" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $record->shop_order_number }}
                                    </span>
                                </td>
                                <td class="td-cell">
                                    <div style="font-size: 0.82rem; color: #334155;">
                                        {{ \Carbon\Carbon::parse($record->planned_date)->format('d-m-Y') }}
                                    </div>
                                </td>
                                <td class="td-cell">
                                    <span class="badge-soft badge-primary">{{ $record->shift_name }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary">{{ number_format($plan) }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-{{ $color }}">{{ number_format($prod) }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <div class="d-flex flex-column align-items-center" style="gap: 3px;">
                                        <div class="progress w-100" style="height: 5px; border-radius: 10px; background: #e2e8f0;">
                                            <div class="progress-bar bg-{{ $color }}"
                                                style="width: {{ $progressWidth }}%; border-radius: 10px;">
                                            </div>
                                        </div>
                                        <small class="fw-600 text-{{ $color }}" style="font-size: 0.7rem;">
                                            {{ number_format($percentage, 1) }}%
                                        </small>
                                    </div>
                                </td>
                                <td class="td-cell text-center">
                                    @if ($record->synced_to_infor)
                                        <span class="badge-soft badge-success">
                                            <i class="fas fa-check mr-1"></i>Enviado
                                        </span>
                                    @else
                                        <span class="badge-soft badge-{{ $statusCfg['badge'] }}">
                                            <i class="fas fa-{{ $statusCfg['icon'] }} mr-1"></i>{{ $record->status_name }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">No se encontraron registros</p>
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
        @if ($productionRecords->hasPages())
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando <strong>{{ $productionRecords->firstItem() }}</strong> –
                        <strong>{{ $productionRecords->lastItem() }}</strong> de
                        <strong>{{ $productionRecords->total() }}</strong> registros
                    </small>
                    {{ $productionRecords->links('pagination::bootstrap-4') }}
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
