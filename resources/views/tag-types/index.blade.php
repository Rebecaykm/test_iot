@extends('adminlte::page')

@section('title', 'Tipos de Tag')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Tipos de Tag</h1>
        </div>

        <div class="d-flex gap-2">
            @can('create tag types')
                <a href="{{ route('tag-types.create') }}" class="btn-action btn-action-primary"
                    aria-label="Agregar nuevo tipo de tag">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span class="d-none d-md-inline">Agregar Tipo</span>
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
            <form method="GET" action="{{ route('tag-types.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">

                    <div class="filter-group" style="width: 280px;">
                        <label for="tt-search" class="sr-only">Buscar tipo de tag</label>
                        <i class="fas fa-search filter-icon" aria-hidden="true"></i>
                        <input type="text" id="tt-search" name="search" class="filter-input"
                            placeholder="Buscar tipo de tag..."
                            value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('tag-types.index') }}" class="btn-action btn-action-secondary">
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
                            <th class="th-cell" scope="col">Nombre</th>
                            <th class="th-cell" scope="col">Descripción</th>
                            <th class="th-cell" scope="col">Creado</th>
                            <th class="th-cell" scope="col">Actualizado</th>
                            <th class="th-cell text-center" scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagTypes as $tagType)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <span class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $tagType->name }}</span>
                                </td>

                                <td class="td-cell">
                                    <span class="text-muted" style="font-size: 0.8rem;">{{ $tagType->description ?? '-' }}</span>
                                </td>

                                <td class="td-cell">
                                    <div class="fw-600" style="font-size: 0.82rem; color: #334155;">
                                        {{ $tagType->created_at->format('Y-m-d') }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ $tagType->created_at->format('H:i') }}
                                    </div>
                                </td>

                                <td class="td-cell">
                                    <div class="fw-600" style="font-size: 0.82rem; color: #334155;">
                                        {{ $tagType->updated_at->format('Y-m-d') }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ $tagType->updated_at->format('H:i') }}
                                    </div>
                                </td>

                                <td class="td-cell text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit tag types')
                                            <a href="{{ route('tag-types.edit', $tagType->id) }}"
                                                class="btn-action btn-action-primary btn-action-sm">
                                                <i class="fas fa-edit"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete tag types')
                                            <form action="{{ route('tag-types.destroy', $tagType->id) }}" method="POST"
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
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">
                                            @if (request()->filled('search'))
                                                No se encontraron resultados para "{{ request('search') }}"
                                            @else
                                                No hay tipos de etiqueta registrados
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
        @if ($tagTypes->hasPages() || $tagTypes->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando <strong>{{ $tagTypes->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $tagTypes->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $tagTypes->total() }}</strong> registros
                    </small>
                    @if ($tagTypes->hasPages())
                        {{ $tagTypes->links('pagination::bootstrap-4') }}
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
    @include('partials.theme-scripts', ['deleteMessage' => '¿Estás seguro de que deseas eliminar este tipo de tag? Esta acción no se puede deshacer.'])
@stop
