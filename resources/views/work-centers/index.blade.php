@extends('adminlte::page')

@section('title', 'Estaciones de Trabajo')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Estaciones de Trabajo</h1>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('work-centers.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: 0.5rem;">

                    <div class="filter-group" style="width: 280px;">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" name="search" class="filter-input"
                            placeholder="Buscar estación, línea, IP..."
                            value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-filter-submit">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('work-centers.index') }}" class="btn-filter-clear">
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
                            <th class="th-cell">Línea</th>
                            <th class="th-cell">Número</th>
                            <th class="th-cell">Nombre</th>
                            <th class="th-cell">IP</th>
                            <th class="th-cell">Creado</th>
                            <th class="th-cell text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workCenters as $workCenter)
                            <tr class="td-row">
                                <td class="td-cell">
                                    @if ($workCenter->line)
                                        <span class="badge-soft badge-primary">{{ $workCenter->line->name }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">Sin Línea</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    <span class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $workCenter->number }}</span>
                                </td>

                                <td class="td-cell">
                                    <span style="font-size: 0.85rem; color: #334155;">{{ $workCenter->name }}</span>
                                </td>

                                <td class="td-cell">
                                    @if ($workCenter->ip)
                                        <span class="badge-soft badge-secondary"
                                            style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                            {{ $workCenter->ip }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    <div class="fw-600" style="font-size: 0.82rem; color: #334155;">
                                        {{ $workCenter->created_at->format('Y-m-d') }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ $workCenter->created_at->format('H:i') }}
                                    </div>
                                </td>

                                <td class="td-cell text-center">
                                    @can('edit work centers')
                                        <a href="{{ route('work-centers.edit', $workCenter) }}"
                                            class="btn-action btn-action-primary btn-action-sm">
                                            <i class="fas fa-edit"></i>
                                            <span>Editar</span>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">
                                            @if (request()->filled('search'))
                                                No se encontraron resultados para "{{ request('search') }}"
                                            @else
                                                No hay estaciones de trabajo registradas
                                            @endif
                                        </p>
                                        @if (request()->filled('search'))
                                            <small>Intenta ajustar la búsqueda</small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación --}}
        @if ($workCenters->hasPages() || $workCenters->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                    <small class="text-muted">
                        Mostrando <strong>{{ $workCenters->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $workCenters->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $workCenters->total() }}</strong> registros
                    </small>
                    @if ($workCenters->hasPages())
                        {{ $workCenters->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @endif

    </div>
@stop

@section('css')
    @include('partials.theme-styles')
@stop

@section('js')
    @include('partials.theme-scripts')
@stop
