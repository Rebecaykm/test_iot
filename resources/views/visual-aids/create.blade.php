@extends('adminlte::page')

@section('title', 'Agregar Imagen')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Agregar Imagen</h1>
            <small class="text-muted">Número de parte: {{ $partNumber->number }}</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('part-numbers.edit', $partNumber->id) }}" class="btn-action btn-action-secondary"
                aria-label="Volver al número de parte">
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
                <i class="fas fa-image mr-2" style="color: #94a3b8;"></i>Información de la Imagen
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('visual-aids.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="part_number_id" value="{{ $partNumber->id }}">

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="image" class="field-label">Imagen *</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input @error('image') is-invalid @enderror"
                                id="image" name="image" accept="image/*" required>
                            <label class="custom-file-label" for="image">Seleccionar archivo</label>
                        </div>
                        @error('image')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="field-label">Vista Previa</label>
                        <div class="image-preview-container">
                            <img id="imagePreview" src="#" alt="Vista previa de la imagen" class="d-none">
                            <span class="text-muted" id="previewText" style="font-size: 0.82rem;">No hay imagen seleccionada</span>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="alt_text" class="field-label">Descripción</label>
                        <input type="text" name="alt_text" id="alt_text"
                            class="field-input @error('alt_text') is-invalid @enderror"
                            value="{{ old('alt_text') }}" placeholder="Ingrese una descripción para la imagen">
                        @error('alt_text')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="custom-control-label fw-600" for="is_active"
                                style="font-size: 0.85rem; color: #334155;">Imagen activa</label>
                        </div>
                        <small class="field-hint">Solo una imagen puede estar activa por número de parte</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('part-numbers.edit', $partNumber->id) }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Guardar Imagen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
    <style>
        /* ── Selector de archivo (sin equivalente en theme-styles.blade.php) ── */
        .custom-file-input ~ .custom-file-label {
            height: 38px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            color: #94a3b8;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
        }
        .custom-file-input ~ .custom-file-label::after {
            height: calc(1.5em + 0.7rem);
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            background: #f8fafc;
            border-left: 1.5px solid #e2e8f0;
        }
        .custom-file-input:focus ~ .custom-file-label {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
        }
        .custom-file-input.is-invalid ~ .custom-file-label {
            border-color: #fca5a5;
        }

        .image-preview-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 38px;
            padding: 0 0.75rem;
            background: #f8fafc;
            border: 1.5px dashed #e2e8f0;
            border-radius: 7px;
            overflow: hidden;
        }
        .image-preview-container img {
            max-height: 100%;
            max-width: 100%;
        }
    </style>
@stop

@section('js')
    @include('partials.theme-scripts')

    <script>
        document.getElementById('image').addEventListener('change', function (e) {
            const fileName = e.target.files[0]?.name || 'Seleccionar archivo';
            e.target.nextElementSibling.innerText = fileName;

            const preview = document.getElementById('imagePreview');
            const previewText = document.getElementById('previewText');

            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    preview.src = event.target.result;
                    preview.classList.remove('d-none');
                    previewText.classList.add('d-none');
                };
                reader.readAsDataURL(e.target.files[0]);
            } else {
                preview.classList.add('d-none');
                previewText.classList.remove('d-none');
            }
        });
    </script>
@stop
