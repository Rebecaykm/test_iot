@extends('adminlte::page')

@section('title', 'Editar Cliente')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Editar Cliente</h1>
        <a href="{{ route('clients.index') }}" class="btn btn-secondary">
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
                        <h3 class="card-title">Información del Cliente</h3>
                    </div>

                    <form action="{{ route('clients.update', $client->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="code" class="font-weight-bold">Código <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                                               id="code" name="code"
                                               value="{{ old('code', $client->code) }}"
                                               placeholder="Ej: CLT-001" required>
                                        @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name"
                                               value="{{ old('name', $client->name) }}"
                                               placeholder="Ej: Empresa ABC" required>
                                        @error('name')
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
                                <i class="fas fa-save mr-1"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
