@extends('adminlte::page')

@section('title', 'Scraps')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Scraps</h1>
        </div>

        <div class="d-flex gap-2">
            @can('create scraps')
                <a href="{{ route('scraps.create') }}" class="btn-action btn-action-primary"
                    aria-label="Agregar nuevo scrap">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span class="d-none d-md-inline">Agregar Scrap</span>
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
            <form method="GET" action="{{ route('scraps.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">

                    <div class="filter-group" style="width: 280px;">
                        <label for="scrap-search" class="sr-only">Buscar scrap</label>
                        <i class="fas fa-search filter-icon" aria-hidden="true"></i>
                        <input type="text" id="scrap-search" name="search" class="filter-input"
                            placeholder="Buscar scrap..." value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('scraps.index') }}" class="btn-action btn-action-secondary">
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
                            <th class="th-cell text-center" scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scraps as $scrap)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <span class="badge-soft badge-primary"
                                        style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $scrap->code }}
                                    </span>
                                </td>

                                <td class="td-cell">
                                    <span class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $scrap->name }}</span>
                                </td>

                                <td class="td-cell">
                                    @if ($scrap->typeScrap)
                                        <span class="badge-soft badge-secondary">{{ $scrap->typeScrap->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    <span class="text-muted" style="font-size: 0.8rem;">{{ $scrap->description ?? '-' }}</span>
                                </td>

                                <td class="td-cell text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit scraps')
                                            <a href="{{ route('scraps.edit', $scrap->id) }}"
                                                class="btn-action btn-action-primary btn-action-sm">
                                                <i class="fas fa-edit"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete scraps')
                                            <form action="{{ route('scraps.destroy', $scrap->id) }}" method="POST"
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
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-recycle fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">
                                            @if (request()->filled('search'))
                                                No se encontraron scraps que coincidan con "{{ request('search') }}"
                                            @else
                                                No hay scraps registrados
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
        @if ($scraps->hasPages() || $scraps->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando <strong>{{ $scraps->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $scraps->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $scraps->total() }}</strong> registros
                    </small>
                    @if ($scraps->hasPages())
                        {{ $scraps->links('pagination::bootstrap-4') }}
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
    @include('partials.theme-scripts', ['deleteMessage' => '¿Estás seguro de que deseas eliminar este scrap? Esta acción no se puede deshacer.'])
@stop
