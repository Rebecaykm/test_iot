@extends('adminlte::page')

@section('title', 'Editar Tag')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Tag</h1>
            @if ($workCenter)
                <small class="text-muted">Estación: {{ $workCenter->name }}</small>
            @endif
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('work-centers.edit', $workCenter->id) }}" class="btn-action btn-action-secondary"
                aria-label="Volver a la estación">
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
                <i class="fas fa-tag mr-2" style="color: #94a3b8;"></i>Información de la Tag
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('tags.update', $tag->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="work_center_id" value="{{ $workCenter->id }}">

                <div class="row mb-3">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="address" class="field-label">Dirección *</label>
                        <input type="text" name="address" id="address"
                            class="field-input @error('address') is-invalid @enderror"
                            value="{{ old('address', $tag->address) }}" required placeholder="Ej: 001A">
                        @error('address')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="long" class="field-label">Longitud *</label>
                        <input type="number" step="0.01" name="long" id="long"
                            class="field-input @error('long') is-invalid @enderror"
                            value="{{ old('long', $tag->long) }}" required placeholder="Ej: 16">
                        @error('long')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="value" class="field-label">Valor</label>
                        <input type="text" name="value" id="value"
                            class="field-input @error('value') is-invalid @enderror"
                            value="{{ old('value', $tag->value) }}" placeholder="Ej: 100">
                        @error('value')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="tag_type_id" class="field-label">Tipo de Etiqueta *</label>
                        <select name="tag_type_id" id="tag_type_id"
                            class="field-input select2 @error('tag_type_id') is-invalid @enderror"
                            style="width: 100%;" required>
                            <option value="">Seleccione un tipo</option>
                            @foreach ($tagTypes as $tagType)
                                <option value="{{ $tagType->id }}"
                                    {{ old('tag_type_id', $tag->tag_type_id) == $tagType->id ? 'selected' : '' }}>
                                    {{ $tagType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('tag_type_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="description" class="field-label">Descripción</label>
                        <textarea name="description" id="description" rows="1"
                            class="field-input @error('description') is-invalid @enderror"
                            placeholder="Descripción de la etiqueta">{{ old('description', $tag->description) }}</textarea>
                        @error('description')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('work-centers.edit', $workCenter->id) }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="reset" class="btn-action btn-action-secondary">
                        <i class="fas fa-undo"></i>
                        <span>Restablecer</span>
                    </button>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Guardar Cambios</span>
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

    <script>
        $(document).ready(function () {
            $('#tag_type_id').select2({
                placeholder: 'Seleccione un tipo',
                allowClear: false,
                width: '100%'
            });

            @if ($errors->has('tag_type_id'))
                $('#tag_type_id').next('.select2-container').find('.select2-selection').addClass('is-invalid');
            @endif
        });
    </script>
@stop
