@extends('adminlte::page')

@section('title', 'Crear Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Crear Línea</h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('lines.index') }}" class="btn-action btn-action-secondary"
                aria-label="Volver al listado">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Volver</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-stream mr-2" style="color: #94a3b8;"></i>Información de la Línea
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('lines.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="name" class="field-label">Nombre *</label>
                        <input type="text" name="name" id="name"
                            class="field-input @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required placeholder="Ej: Línea de Ensamblaje Principal">
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3 mb-md-0">
                        <x-select
                            name="area_id"
                            label="Área"
                            :options="collect([['value' => '', 'label' => 'Seleccione un área', 'selected' => !old('area_id')]])
                                ->concat($areas->map(fn ($area) => [
                                    'value' => $area->id,
                                    'label' => $area->name,
                                    'selected' => old('area_id') == $area->id,
                                ]))"
                        />
                        @error('area_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        @php
                            $lineColors = [
                                '#1E40AF' => 'Azul Marino',
                                '#2563EB' => 'Azul',
                                '#0284C7' => 'Azul Cielo',
                                '#0891B2' => 'Cian',
                                '#0F766E' => 'Verde Azulado',
                                '#059669' => 'Esmeralda',
                                '#16A34A' => 'Verde',
                                '#65A30D' => 'Verde Lima',
                                '#CA8A04' => 'Amarillo Oro',
                                '#D97706' => 'Ámbar',
                                '#EA580C' => 'Naranja',
                                '#DC2626' => 'Rojo',
                                '#E11D48' => 'Carmesí',
                                '#DB2777' => 'Rosa',
                                '#9333EA' => 'Púrpura',
                                '#7C3AED' => 'Violeta',
                                '#4F46E5' => 'Índigo',
                            ];
                        @endphp
                        <x-select
                            name="color"
                            label="Color *"
                            :options="collect([['value' => '', 'label' => 'Seleccione un color', 'selected' => !old('color')]])
                                ->concat(collect($lineColors)->map(fn ($name, $hex) => [
                                    'value' => $hex,
                                    'label' => $name,
                                    'swatch' => $hex,
                                    'selected' => old('color') == $hex,
                                ])->values())"
                        />
                        @error('color')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="description" class="field-label">Descripción</label>
                        <textarea name="description" id="description" rows="3"
                            class="field-input @error('description') is-invalid @enderror"
                            placeholder="{{ __('Descripción detallada de la línea') }}">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <x-multi-select
                            name="work_centers"
                            label="Estaciones de Trabajo Disponibles"
                            placeholder="Ninguna estación seleccionada"
                            search-placeholder="Buscar estación..."
                            empty-message="No hay estaciones disponibles."
                            :options="$workCenters->map(fn ($wc) => [
                                'value' => $wc->id,
                                'label' => $wc->number . ' — ' . $wc->name,
                                'selected' => is_array(old('work_centers')) && in_array($wc->id, old('work_centers')),
                            ])"
                        />
                        @error('work_centers')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('lines.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="reset" class="btn-action btn-action-secondary">
                        <i class="fas fa-undo"></i>
                        <span>Limpiar</span>
                    </button>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Guardar Línea</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
@stop

@section('js')
    @include('partials.theme-scripts')
@stop
