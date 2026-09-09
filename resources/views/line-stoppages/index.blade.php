@extends('adminlte::page')

@section('title', 'Paros de Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Paros de Línea</h1>
        </div>

        <div class="d-flex gap-2">
            @can('create line stoppages')
                <a href="{{ route('line-stoppages.create') }}" class="btn-action btn-action-primary"
                    aria-label="Agregar nuevo paro de línea">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span class="d-none d-md-inline">Agregar Paro</span>
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('line-stoppages.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">

                    <div class="filter-group" style="width: 280px;">
                        <label for="ls-search" class="sr-only">Buscar paro de línea</label>
                        <i class="fas fa-search filter-icon" aria-hidden="true"></i>
                        <input type="text" id="ls-search" name="search" class="filter-input"
                            placeholder="Código, nombre, tipo..." value="{{ $search ?? '' }}">
                    </div>

                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (!empty($search))
                        <a href="{{ route('line-stoppages.index') }}" class="btn-action btn-action-secondary">
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
                            <th class="th-cell" scope="col">Código</th>
                            <th class="th-cell" scope="col">Nombre</th>
                            <th class="th-cell" scope="col">Tipo</th>
                            <th class="th-cell" scope="col">Descripción</th>
                            <th class="th-cell" scope="col">Creado</th>
                            <th class="th-cell text-center" scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lineStoppages as $stoppage)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <span class="badge-soft badge-primary"
                                        style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $stoppage->code }}
                                    </span>
                                </td>

                                <td class="td-cell">
                                    <span class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $stoppage->name }}</span>
                                </td>

                                <td class="td-cell">
                                    @if ($stoppage->typeLineStoppage)
                                        <span class="badge-soft badge-secondary">{{ $stoppage->typeLineStoppage->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    <span class="text-muted" style="font-size: 0.8rem;">{{ $stoppage->description ?? '-' }}</span>
                                </td>

                                <td class="td-cell">
                                    <div class="fw-600" style="font-size: 0.82rem; color: #334155;">
                                        {{ optional($stoppage->created_at)->format('Y-m-d') }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ optional($stoppage->created_at)->format('H:i') }}
                                    </div>
                                </td>

                                <td class="td-cell text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit line stoppages')
                                            <a href="{{ route('line-stoppages.edit', $stoppage) }}"
                                                class="btn-action btn-action-primary btn-action-sm">
                                                <i class="fas fa-edit"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete line stoppages')
                                            <form action="{{ route('line-stoppages.destroy', $stoppage) }}" method="POST"
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
                                        <i class="fas fa-ban fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">
                                            @if (!empty($search))
                                                No se encontraron resultados para "{{ $search }}"
                                            @else
                                                No hay paros de línea registrados
                                            @endif
                                        </p>
                                        @if (!empty($search))
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
        @if ($lineStoppages->hasPages() || $lineStoppages->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando <strong>{{ $lineStoppages->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $lineStoppages->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $lineStoppages->total() }}</strong> registros
                    </small>
                    @if ($lineStoppages->hasPages())
                        {{ $lineStoppages->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @endif

    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.theme-scripts', ['deleteMessage' => '¿Estás seguro de que deseas eliminar este paro de línea? Esta acción no se puede deshacer.'])
@stop
