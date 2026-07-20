@extends('adminlte::page')

@section('title', 'Líneas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Líneas</h1>
        </div>

        <div class="d-flex" style="gap: 0.5rem;">
            @can('create lines')
                <a href="{{ route('lines.create') }}" class="btn-action btn-action-primary">
                    <i class="fas fa-plus"></i>
                    <span class="d-none d-md-inline">Agregar Línea</span>
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('lines.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: 0.5rem;">

                    <div class="filter-group" style="width: 280px;">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" name="search" class="filter-input"
                            placeholder="Buscar línea, área..."
                            value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-filter-submit">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('lines.index') }}" class="btn-filter-clear">
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
                            <th class="th-cell">Descripción</th>
                            <th class="th-cell">Área</th>
                            <th class="th-cell">Color</th>
                            <th class="th-cell">Creado</th>
                            <th class="th-cell text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $line)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <span class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $line->name }}</span>
                                </td>

                                <td class="td-cell">
                                    <span class="text-muted" style="font-size: 0.8rem;">{{ $line->description ?? '-' }}</span>
                                </td>

                                <td class="td-cell">
                                    @if ($line->area)
                                        <span class="badge-soft badge-primary">{{ $line->area->name }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">Sin Área</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    @if ($line->color)
                                        <div class="d-flex align-items-center" style="gap: 0.45rem;">
                                            <span
                                                style="display: inline-block; width: 18px; height: 18px; border-radius: 5px; background-color: {{ $line->color }}; border: 1px solid #e2e8f0;"></span>
                                            <span class="text-muted"
                                                style="font-size: 0.78rem; font-family: 'SFMono-Regular', Consolas, monospace;">
                                                {{ $line->color }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    <div class="fw-600" style="font-size: 0.82rem; color: #334155;">
                                        {{ optional($line->created_at)->format('Y-m-d') }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ optional($line->created_at)->format('H:i') }}
                                    </div>
                                </td>

                                <td class="td-cell text-center">
                                    <div class="d-flex justify-content-center" style="gap: 0.5rem;">
                                        @can('edit lines')
                                            <a href="{{ route('lines.edit', $line->id) }}"
                                                class="btn-action btn-action-primary btn-action-sm">
                                                <i class="fas fa-edit"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete lines')
                                            <form action="{{ route('lines.destroy', $line->id) }}" method="POST"
                                                style="display:inline;" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action btn-action-danger btn-action-sm">
                                                    <i class="fas fa-trash"></i>
                                                    <span>Eliminar</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
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
                                                No hay líneas registradas
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
        @if ($lines->hasPages() || $lines->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                    <small class="text-muted">
                        Mostrando <strong>{{ $lines->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $lines->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $lines->total() }}</strong> registros
                    </small>
                    @if ($lines->hasPages())
                        {{ $lines->links('pagination::bootstrap-4') }}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.theme-scripts', ['deleteMessage' => '¿Estás seguro de que deseas eliminar esta línea? Esta acción no se puede deshacer.'])
@stop
