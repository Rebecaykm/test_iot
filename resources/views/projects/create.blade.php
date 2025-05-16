@extends('adminlte::page')

@section('title', 'Crear Proyecto')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Crear Proyecto</h1>
        <a href="{{ route('projects.index') }}" class="btn btn-secondary">
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
                        <h3 class="card-title">Información del Proyecto</h3>
                    </div>

                    <form action="{{ route('projects.store') }}" method="POST">
                        @csrf

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="client_id" class="font-weight-bold">Cliente <span class="text-danger">*</span></label>
                                        <select class="form-control @error('client_id') is-invalid @enderror"
                                                id="client_id" name="client_id" required>
                                            <option value="">Seleccione un cliente...</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}"
                                                    {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->code }} - {{ $client->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('client_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type" class="font-weight-bold">Tipo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('type') is-invalid @enderror"
                                               id="type" name="type" value="{{ old('type') }}"
                                               placeholder="Ej: Automotriz" required>
                                        @error('type')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="model" class="font-weight-bold">Modelo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('model') is-invalid @enderror"
                                               id="model" name="model" value="{{ old('model') }}"
                                               placeholder="Ej: Modelo X2024" required>
                                        @error('model')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="prefix" class="font-weight-bold">Prefijo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('prefix') is-invalid @enderror"
                                               id="prefix" name="prefix" value="{{ old('prefix') }}"
                                               placeholder="Ej: PRJ-" maxlength="10" required>
                                        @error('prefix')
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
