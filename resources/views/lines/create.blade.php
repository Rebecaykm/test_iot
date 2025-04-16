@extends('adminlte::page')

@section('title', 'Crear Línea')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark"></i>Crear Línea</h1>
    <a href="{{ route('lines.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver al Listado
    </a>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header bg-white">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-1"></i>
                        Información de la Línea
                    </h3>
                </div>

                <form action="{{ route('lines.store') }}" method="POST" id="line-form">
                    @csrf

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}"
                                           placeholder="Ej: Línea de Ensamblaje Principal" required>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="area_id" class="font-weight-bold">Área</label>
                                    <select class="form-control select2 @error('area_id') is-invalid @enderror"
                                            id="area_id" name="area_id" style="width: 100%;">
                                        <option value="">Seleccione un área...</option>
                                        @foreach($areas as $area)
                                            <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>
                                                {{ $area->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('area_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description" class="font-weight-bold">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description" rows="2"
                                              placeholder="Descripción detallada de la línea">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-end py-3">
                        <button type="reset" class="btn btn-default mr-2">
                            <i class="fas fa-undo mr-1"></i> Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar Línea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
<style>
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,.125);
    }
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px);
        padding: .375rem .75rem;
    }
</style>
@stop

@section('js')
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Inicializar Select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });

        // Validación de formulario
        $('#line-form').validate({
            rules: {
                name: {
                    required: true,
                    maxlength: 100
                },
                description: {
                    maxlength: 255
                }
            },
            messages: {
                name: {
                    required: "Por favor ingrese el nombre de la línea",
                    maxlength: "El nombre no debe exceder los 100 caracteres"
                }
            },
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
            }
        });
    });
</script>
@stop
