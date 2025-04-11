@extends('adminlte::page')

@section('title', 'Agregar Nueva Imagen')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Agregar Imagen</h1>
        <a href="{{ route('part-numbers.edit', $partNumber->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Información de la Imagen</h3>
                    </div>

                    <form action="{{ route('visual-aids.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="part_number_id" value="{{ $partNumber->id }}">

                        <div class="card-body">
                            <!-- Campo para subir imagen -->
                            <div class="form-group">
                                <label for="image">Imagen</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image" name="image" required>
                                        <label class="custom-file-label" for="image">Seleccionar archivo</label>
                                    </div>
                                </div>
                                <!-- <small class="text-muted">Formatos soportados: JPG, PNG, GIF. Tamaño máximo: 5MB</small> -->
                                @error('image')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Campo de Vista Previa -->
                            <div class="form-group">
                                <label>Vista Previa</label>
                                <div class="image-preview-container text-center border p-2"
                                     style="height: 100px; background-color: #f8f9fa;">
                                    <img id="imagePreview" src="#" alt="Vista previa de la imagen"
                                         class="img-fluid d-none" style="max-height: 100%;">
                                    <span class="text-muted align-middle"
                                          id="previewText">No hay imagen seleccionada</span>
                                </div>
                            </div>

                            <!-- Campo para texto alternativo -->
                            <div class="form-group">
                                <label for="alt_text">Descripción</label>
                                <input type="text" class="form-control" id="alt_text" name="alt_text"
                                       value="{{ old('alt_text') }}"
                                       placeholder="Ingrese una descripción para la imagen">
                            </div>

                            <!-- Campo para estado activo -->
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active"
                                           name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">Imagen activa</label>
                                </div>
                                <small class="text-muted">Solo una imagen puede estar activa por número de parte</small>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Imagen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Mostrar el nombre del archivo seleccionado
        document.getElementById('image').addEventListener('change', function(e) {
            var fileName = e.target.files[0]?.name || "No hay imagen seleccionada";
            var label = e.target.nextElementSibling;
            label.innerText = fileName;

            // Mostrar vista previa
            var preview = document.getElementById('imagePreview');
            var previewText = document.getElementById('previewText');

            if (e.target.files && e.target.files[0]) {
                var reader = new FileReader();

                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.classList.remove('d-none');
                    previewText.classList.add('d-none');
                }

                reader.readAsDataURL(e.target.files[0]);
            } else {
                preview.classList.add('d-none');
                previewText.classList.remove('d-none');
            }
        });
    </script>
@stop

