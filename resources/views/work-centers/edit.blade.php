@extends('adminlte::page')

@section('title', 'Editar Estación')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Estación</h1>
        </div>

        <div class="d-flex" style="gap: 0.5rem;">
            <a href="{{ route('work-centers.index') }}" class="btn-action btn-action-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="d-none d-md-inline">Volver</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    {{-- Formulario Principal --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-cog mr-2" style="color: #94a3b8;"></i>Información General
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('work-centers.update', $workCenter->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="field-label">Número</label>
                        <div class="field-readonly" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                            {{ $workCenter->number }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="field-label">Nombre</label>
                        <div class="field-readonly">
                            {{ $workCenter->name }}
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="field-label">Línea</label>
                        <div class="field-readonly">
                            @if ($workCenter->line)
                                <span class="badge-soft badge-primary">{{ $workCenter->line->name }}</span>
                            @else
                                Sin línea asignada
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="ip" class="field-label">Dirección IP</label>
                        <input type="text" name="ip" id="ip"
                            class="field-input @error('ip') is-invalid @enderror"
                            value="{{ old('ip', $workCenter->ip) }}" placeholder="Ej: 192.168.1.100"
                            style="font-family: 'SFMono-Regular', Consolas, monospace;">
                        @error('ip')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                        <small class="field-hint">Dirección IP de la estación para comunicación</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end" style="gap: 0.5rem; margin-top: 1.5rem;">
                    <a href="{{ route('work-centers.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Actualizar Estación</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Listado de Tags --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                <h5 class="mb-0 section-title">
                    <i class="fas fa-tags mr-2" style="color: #94a3b8;"></i>Tags Asociados
                </h5>
                @can('create tags')
                    <a href="{{ route('tags.create', ['work_center' => $workCenter->id]) }}"
                        class="btn-action btn-action-primary">
                        <i class="fas fa-plus"></i>
                        <span>Agregar Tag</span>
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell">Dirección</th>
                            <th class="th-cell">Longitud</th>
                            <th class="th-cell">Tipo</th>
                            <th class="th-cell">Descripción</th>
                            <th class="th-cell text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tags as $tag)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <span class="fw-600 text-dark"
                                        style="font-size: 0.85rem; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $tag->address ?? '-' }}
                                    </span>
                                </td>
                                <td class="td-cell">
                                    <span style="font-size: 0.85rem; color: #334155;">{{ $tag->long ?? '-' }}</span>
                                </td>
                                <td class="td-cell">
                                    <span class="badge-soft badge-primary">{{ $tag->tagType->name ?? '-' }}</span>
                                </td>
                                <td class="td-cell">
                                    <span class="text-muted" style="font-size: 0.8rem;">{{ $tag->description ?? '-' }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <div class="d-flex justify-content-center" style="gap: 0.5rem;">
                                        @can('edit tags')
                                            <a href="{{ route('tags.edit', $tag) }}"
                                                class="btn-action btn-action-primary btn-action-sm">
                                                <i class="fas fa-edit"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete tags')
                                            <form action="{{ route('tags.destroy', $tag) }}" method="POST"
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
                                        <i class="fas fa-tags fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-0 fw-500" style="color: #475569;">No hay tags asociados</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
@stop

@section('js')
    @include('partials.theme-scripts', ['deleteMessage' => '¿Estás seguro de que deseas eliminar este tag? Esta acción no se puede deshacer.'])
@stop
