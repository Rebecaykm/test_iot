@extends('adminlte::page')

@section('title', 'Editar Número de Parte')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Número de Parte</h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('part-numbers.index') }}" class="btn-action btn-action-secondary"
                aria-label="Volver al listado">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Volver</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Formulario Principal --}}
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-cog mr-2" style="color: #94a3b8;"></i>Información General
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('part-numbers.update', $partNumber->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Información de solo lectura --}}
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <span class="field-label" id="lbl-estacion">Estación</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-estacion">
                            {{ $partNumber->workCenter->name ?? 'No asignada' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <span class="field-label" id="lbl-numero">Número</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-numero"
                            style="font-family: 'SFMono-Regular', Consolas, monospace;">
                            {{ $partNumber->number }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <span class="field-label" id="lbl-nombre">Nombre</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-nombre">
                            {{ $partNumber->name }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <span class="field-label" id="lbl-estado">Estado</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-estado">
                            @if ($partNumber->is_obsolete)
                                <span class="badge-soft badge-danger">
                                    <i class="fas fa-times-circle mr-1"></i>Obsoleto
                                </span>
                            @else
                                <span class="badge-soft badge-success">
                                    <i class="fas fa-check-circle mr-1"></i>Activo
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <span class="field-label" id="lbl-clase">Clase</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-clase">
                            <span
                                class="badge-soft badge-secondary">{{ $partNumber->itemClass->abbreviation ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <span class="field-label" id="lbl-standard-pack">Standard Pack</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-standard-pack">
                            {{ $partNumber->standardPack->name ?? '-' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <span class="field-label" id="lbl-cantidad-standard-pack">Cantidad Standard Pack</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-cantidad-standard-pack">
                            {{ $partNumber->standard_pack_quantity ?? '-' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <span class="field-label" id="lbl-tasa-produccion">Tasa de Producción</span>
                        <div class="field-readonly" role="group" aria-labelledby="lbl-tasa-produccion">
                            <span class="badge-soft badge-primary">
                                @if ($partNumber->production_rate != 0)
                                    {{ number_format(60 / $partNumber->production_rate, 2) }}
                                @else
                                    0.0
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Campos editables --}}
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <label for="production_order" class="field-label">Orden de Producción</label>
                        <input type="number" name="production_order" id="production_order" min="0" step="1"
                            class="field-input @error('production_order') is-invalid @enderror"
                            value="{{ old('production_order', $partNumber->production_order) }}"
                            placeholder="Dejar vacío si no tiene orden específica">
                        @error('production_order')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                        <small class="field-hint">Orden en que se debe producir este número de parte en la estación</small>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="efficiency" class="field-label">Eficiencia (%)</label>
                        <input type="number" name="efficiency" id="efficiency" step="0.01" min="0" max="100"
                            class="field-input @error('efficiency') is-invalid @enderror"
                            value="{{ old('efficiency', $partNumber->efficiency) }}">
                        @error('efficiency')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('part-numbers.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Orden de Producción en la Estación --}}
    @if ($partNumbersInSameWorkCenter->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
                <h5 class="mb-0 section-title">
                    <i class="fas fa-list-ol mr-2" style="color: #94a3b8;"></i>
                    Orden de Producción en {{ $partNumber->workCenter->name ?? 'Esta Estación' }}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="table-head-row">
                                <th class="th-cell" scope="col">Orden</th>
                                <th class="th-cell" scope="col">Número de Parte</th>
                                <th class="th-cell" scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $allPartNumbers = $partNumbersInSameWorkCenter
                                    ->concat(collect([$partNumber]))
                                    ->sortBy(fn($item) => $item->production_order ?? 9999);
                            @endphp
                            @foreach ($allPartNumbers as $pn)
                                <tr class="td-row {{ $pn->id === $partNumber->id ? 'row-current' : '' }}">
                                    <td class="td-cell">
                                        @if ($pn->production_order)
                                            <span class="badge-soft badge-primary"
                                                style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                                {{ $pn->production_order }}
                                            </span>
                                        @else
                                            <span class="badge-soft badge-secondary">Sin orden</span>
                                        @endif
                                    </td>
                                    <td class="td-cell">
                                        <div class="d-flex align-items-center gap-2">
                                            <div>
                                                <div class="fw-600 text-dark" style="font-size: 0.85rem;">
                                                    {{ $pn->number }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $pn->name }}
                                                </div>
                                            </div>
                                            @if ($pn->id === $partNumber->id)
                                                <span class="badge-soft badge-warning">
                                                    <i class="fas fa-star mr-1"></i>Actual
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="td-cell">
                                        @if ($pn->is_obsolete)
                                            <span class="badge-soft badge-danger">
                                                <i class="fas fa-times-circle mr-1"></i>Obsoleto
                                            </span>
                                        @else
                                            <span class="badge-soft badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>Activo
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Imágenes Asociadas --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 section-title">
                    <i class="fas fa-images mr-2" style="color: #94a3b8;"></i>Imágenes Asociadas
                </h5>
                @can('create visual aids')
                    <a href="{{ route('visual-aids.create', ['part_number' => $partNumber->id]) }}"
                        class="btn-action btn-action-primary">
                        <i class="fas fa-plus"></i>
                        <span>Agregar Imagen</span>
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell" scope="col">Imagen</th>
                            <th class="th-cell" scope="col">Descripción</th>
                            <th class="th-cell" scope="col">Estado</th>
                            <th class="th-cell text-center" scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visualAids as $visualAid)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <img src="{{ asset('storage/' . $visualAid->path) }}"
                                        alt="{{ $visualAid->alt_text }}" class="rounded-3"
                                        style="width: 56px; height: 56px; object-fit: cover; border: 1px solid #e2e8f0;">
                                </td>
                                <td class="td-cell">
                                    <div class="fw-500" style="font-size: 0.85rem; color: #334155;">
                                        {{ $visualAid->alt_text ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="td-cell">
                                    @if ($visualAid->is_active)
                                        <span class="badge-soft badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>Activa
                                        </span>
                                    @else
                                        <span class="badge-soft badge-secondary">
                                            <i class="fas fa-times-circle mr-1"></i>Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="td-cell text-center">
                                    @can('delete visual aids')
                                        <form action="{{ route('visual-aids.destroy', [$visualAid->id, $partNumber->id]) }}"
                                            method="POST" style="display:inline;" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action btn-action-danger btn-action-sm">
                                                <i class="fas fa-trash"></i>
                                                <span>Eliminar</span>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-image fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-0 fw-500" style="color: #475569;">No hay imágenes asociadas</p>
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
    @include('partials.theme-buttons-outline')
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Confirmación antes de eliminar imágenes
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: '¡No podrás revertir esta acción!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#1d4ed8',
                            cancelButtonColor: '#b91c1c',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.submit();
                            }
                        });
                    } else if (confirm(
                            '¿Estás seguro de que deseas eliminar esta imagen? Esta acción no se puede deshacer.'
                            )) {
                        this.submit();
                    }
                });
            });

            // Cerrar alertas automáticamente después de 5 segundos
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(el => $(el).alert('close'));
            }, 5000);
        });
    </script>
@stop
