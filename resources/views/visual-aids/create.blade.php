@extends('adminlte::page')

@section('title', 'Agregar Imagen')

@section('content_header')
    <h1>{{ __('Agregar Imagen') }}</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body">
            <form action="{{ route('visual-aids.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="part_number_id" value="{{ $partNumber->id }}">

                <div class="row mb-3">
                    <!-- Número de Parte (bloqueado) -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary">{{ __('Número de Parte') }}</label>
                        <input type="text"
                               class="form-control border-light-subtle"
                               value="{{ $partNumber->number }} — {{ $partNumber->name }}"
                               disabled>
                    </div>

                    <!-- Imagen -->
                    <div class="col-md-6">
                        <label for="image" class="form-label fw-bold text-secondary">
                            {{ __('Imagen') }} *
                        </label>
                        <input type="file"
                               class="form-control border-light-subtle @error('image') is-invalid @enderror"
                               id="image" name="image" accept="image/*" required>
                        @error('image')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Vista previa -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-secondary">{{ __('Vista Previa') }}</label>
                        <div class="preview-container border rounded-3 d-flex align-items-center justify-content-center"
                             style="height: 160px; background-color: #f8f9fa;">
                            <img id="imagePreview" src="#" alt="Vista previa"
                                 class="d-none" style="max-height: 150px; border-radius: 8px;">
                            <span id="previewText" class="text-muted">
                                <i class="fas fa-image mr-2"></i>No hay imagen seleccionada
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Descripción -->
                    <div class="col-md-6">
                        <label for="alt_text" class="form-label fw-bold text-secondary">
                            {{ __('Descripción') }}
                        </label>
                        <input type="text"
                               class="form-control border-light-subtle"
                               id="alt_text" name="alt_text"
                               value="{{ old('alt_text') }}"
                               placeholder="Descripción de la imagen">
                    </div>

                    <!-- Estado activo -->
                    <div class="col-md-6 d-flex flex-column justify-content-center">
                        <label class="form-label fw-bold text-secondary">{{ __('Estado') }}</label>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="is_active" name="is_active" value="1"
                                   {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Imagen activa</label>
                        </div>
                        <small class="text-muted">Solo una imagen puede estar activa por número de parte</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('part-numbers.edit', $partNumber) }}"
                       class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times mr-2"></i>
                        {{ __('Cancelar') }}
                    </a>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save mr-2"></i>
                        {{ __('Guardar Imagen') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        body, .card, .btn, .form-control, h1 {
            font-family: 'Roboto', sans-serif !important;
        }

        .border-light-subtle { border-color: #f0f0f0 !important; }

        .form-control {
            border-radius: 8px !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.95rem !important;
            border: 1px solid #e0e0e0 !important;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        .form-control:disabled {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            cursor: not-allowed;
        }

        .form-label {
            font-size: 0.9rem !important;
            margin-bottom: 0.5rem !important;
            font-weight: 600 !important;
        }

        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn i { font-size: 0.9rem !important; }

        .gap-2 { gap: 0.5rem; }
        .rounded-3 { border-radius: 12px !important; }
        .invalid-feedback { font-size: 0.85rem !important; }
        .alert { border-radius: 8px; }
        .preview-container { border-color: #e0e0e0 !important; }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
@stop

@section('js')
    <script>
        document.getElementById('image').addEventListener('change', function (e) {
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
