@extends('adminlte::page')

@section('title', 'Crear Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Crear Línea</h1>
        </div>

        <div class="d-flex" style="gap: 0.5rem;">
            <a href="{{ route('lines.index') }}" class="btn-action btn-action-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="d-none d-md-inline">Volver</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
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
                        <label for="area_id" class="field-label">Área</label>
                        <select name="area_id" id="area_id"
                            class="field-input select2 @error('area_id') is-invalid @enderror">
                            <option value="">{{ __('Seleccione un área') }}</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('area_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="color" class="field-label">Color *</label>
                        <select name="color" id="color"
                            class="field-input select2 @error('color') is-invalid @enderror" required>
                            <option value="">{{ __('Seleccione un color') }}</option>
                            @foreach ([
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
                            ] as $hex => $name)
                                <option value="{{ $hex }}" {{ old('color') == $hex ? 'selected' : '' }}
                                    data-color="{{ $hex }}">
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
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
                        <label class="field-label">Estaciones de Trabajo Disponibles</label>
                        <div class="check-container">
                            <div class="row">
                                @foreach ($workCenters->chunk(ceil($workCenters->count() / 3)) as $column)
                                    <div class="col-md-4">
                                        @foreach ($column as $workCenter)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="work_centers[]"
                                                    id="wc_{{ $workCenter->id }}"
                                                    value="{{ $workCenter->id }}"
                                                    {{ is_array(old('work_centers')) && in_array($workCenter->id, old('work_centers')) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="wc_{{ $workCenter->id }}">
                                                    {{ $workCenter->number }} - {{ $workCenter->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('work_centers')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end" style="gap: 0.5rem; margin-top: 1.5rem;">
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
@stop

@section('js')
    @include('partials.theme-scripts')
    <script>
        $(document).ready(function() {
            // Formatear opciones del select de color con su muestra
            function formatColorOption(option) {
                if (!option.id) {
                    return option.text;
                }

                var color = $(option.element).data('color');
                if (color) {
                    return $(
                        '<span><span style="display: inline-block; width: 16px; height: 16px; border-radius: 4px; background-color: ' +
                        color + '; margin-right: 10px; border: 1px solid #e2e8f0; vertical-align: text-bottom;"></span>' +
                        option.text + '</span>'
                    );
                }
                return option.text;
            }

            $('#area_id, #color').select2({
                placeholder: 'Seleccione una opción',
                allowClear: false,
                width: '100%',
                templateResult: formatColorOption,
                templateSelection: formatColorOption
            });
        });
    </script>
@stop
