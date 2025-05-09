@extends('adminlte::page')

@section('title', 'Editar Tag')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Editar Tag</h1>
        <a href="{{ route('work-centers.edit', $workCenter->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Editar Tag</h3>
                    </div>

                    <form action="{{ route('tags.update', $tag->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="work_center_id" value="{{ $workCenter->id }}">

                        <div class="card-body">
                            <div class="row">
                                <!-- Dirección -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="address" class="font-weight-bold">Dirección <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror"
                                               id="address" name="address"
                                               value="{{ old('address', $tag->address) }}"
                                               placeholder="Ej: 001A" required>
                                        @error('address')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Longitud -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="long" class="font-weight-bold">Longitud <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01"
                                               class="form-control @error('long') is-invalid @enderror"
                                               id="long" name="long"
                                               value="{{ old('long', $tag->long) }}"
                                               placeholder="Ej: 16" required>
                                        @error('long')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Valor -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="value" class="font-weight-bold">Valor</label>
                                        <input type="text" class="form-control @error('value') is-invalid @enderror"
                                               id="value" name="value"
                                               value="{{ old('value', $tag->value) }}"
                                               placeholder="Ej: ">
                                        @error('value')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Tipo de Etiqueta -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tag_type_id" class="font-weight-bold">Tipo de Etiqueta <span class="text-danger">*</span></label>
                                        <select class="form-control @error('tag_type_id') is-invalid @enderror"
                                                id="tag_type_id" name="tag_type_id" required>
                                            <option value="">Seleccione un tipo...</option>
                                            @foreach($tagTypes as $tagType)
                                                <option value="{{ $tagType->id }}"
                                                    {{ (old('tag_type_id', $tag->tag_type_id) == $tagType->id) ? 'selected' : '' }}>
                                                    {{ $tagType->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('tag_type_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="description" class="font-weight-bold">Descripción</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                                  id="description" name="description" rows="1"
                                                  placeholder="Descripción de la etiqueta">{{ old('description', $tag->description) }}</textarea>
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
                                <i class="fas fa-undo mr-1"></i> Restablecer
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
    </style>
@stop
