@extends('adminlte::page')

@section('title', 'Editar Registro de Scrap')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Registro de Scrap</h1>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-recycle mr-2" style="color: #94a3b8;"></i>Información del Registro
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('scrap-records.update', $scrapRecord) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="part_number_id" class="field-label">Número de Parte *</label>
                        <select name="part_number_id" id="part_number_id"
                            class="field-input select2 @error('part_number_id') is-invalid @enderror"
                            style="width: 100%;" required>
                            <option value="">Seleccione un número de parte</option>
                            @foreach ($partNumbers as $partNumber)
                                <option value="{{ $partNumber->id }}"
                                    {{ old('part_number_id', $scrapRecord->part_number_id) == $partNumber->id ? 'selected' : '' }}>
                                    {{ $partNumber->number }} – {{ $partNumber->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('part_number_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="scrap_id" class="field-label">Tipo de Scrap *</label>
                        <select name="scrap_id" id="scrap_id"
                            class="field-input select2 @error('scrap_id') is-invalid @enderror"
                            style="width: 100%;" required>
                            <option value="">Seleccione un tipo de scrap</option>
                            @foreach ($scraps as $scrap)
                                <option value="{{ $scrap->id }}"
                                    {{ old('scrap_id', $scrapRecord->scrap_id) == $scrap->id ? 'selected' : '' }}>
                                    {{ $scrap->code }} – {{ $scrap->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('scrap_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="quantity" class="field-label">Cantidad *</label>
                        <input type="number" min="1" step="1" name="quantity" id="quantity"
                            class="field-input @error('quantity') is-invalid @enderror"
                            value="{{ old('quantity', $scrapRecord->quantity) }}" required
                            placeholder="Ingrese la cantidad">
                        @error('quantity')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('scrap-records.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Actualizar Registro</span>
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
            $('#part_number_id, #scrap_id').select2({
                placeholder: 'Seleccione una opción',
                allowClear: false,
                width: '100%'
            });

            @if ($errors->has('part_number_id'))
                $('#part_number_id').next('.select2-container').find('.select2-selection').addClass('is-invalid');
            @endif
            @if ($errors->has('scrap_id'))
                $('#scrap_id').next('.select2-container').find('.select2-selection').addClass('is-invalid');
            @endif

            document.getElementById('quantity').addEventListener('input', function () {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
@stop
