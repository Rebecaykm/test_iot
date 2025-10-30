@extends('adminlte::page')

@section('title', 'Editar Tipo de Paro de Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Editar Tipo de Paro de Línea</h1>
        <a href="{{ route('type-line-stoppages.index') }}" class="btn btn-secondary">
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
                        <h3 class="card-title">
                            Información del Tipo de Paro de Línea
                        </h3>
                    </div>

                    <form action="{{ route('type-line-stoppages.update', $typeLineStoppage->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $typeLineStoppage->name) }}"
                                            placeholder="Ej: Paro Programada" required>
                                        @error('name')
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
                                            id="description" name="description" rows="3"
                                            placeholder="Descripción detallada del tipo de paro">{{ old('description', $typeLineStoppage->description) }}</textarea>
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
                                <i class="fas fa-save mr-1"></i> Actualizar Tipo de Paro de Línea
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
