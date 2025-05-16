@extends('adminlte::page')

@section('title', 'Crear Estado')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Crear Estado</h1>
        <a href="{{ route('statuses.index') }}" class="btn btn-secondary">
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
                        <h3 class="card-title">Información del Estado</h3>
                    </div>

                    <form action="{{ route('statuses.store') }}" method="POST">
                        @csrf

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name" value="{{ old('name') }}"
                                               placeholder="Ej: En Proceso" required>
                                        @error('name')
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="description" class="font-weight-bold">Descripción</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                                  id="description" name="description" rows="1"
                                                  placeholder="Descripción del estado">{{ old('description') }}</textarea>
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
                                <i class="fas fa-save mr-1"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
