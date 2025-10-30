@extends('adminlte::page')

@section('title', 'Crear Tipo de Scrap')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Crear Tipo de Scrap</h1>
        <a href="{{ route('type-scraps.index') }}" class="btn btn-secondary">
            Volver
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Información del Tipo de Scrap</h3>
                    </div>

                    <form action="{{ route('type-scraps.store') }}" method="POST">
                        @csrf

                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <h5 class="alert-heading">Error de Validación</h5>
                                    <ul class="mb-0 pl-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="font-weight-bold">
                                            Nombre <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               class="form-control @error('name') is-invalid @enderror"
                                               id="name"
                                               name="name"
                                               value="{{ old('name') }}"
                                               placeholder="Ej: Scrap de Plástico, Scrap de Metal..."
                                               required>
                                        @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description" class="font-weight-bold">Descripción</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                                  id="description"
                                                  name="description"
                                                  rows="3"
                                                  placeholder="Descripción detallada del tipo de scrap...">{{ old('description') }}</textarea>
                                        @error('description')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-end">
                            <button type="reset" class="btn btn-default mr-2">
                                Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Crear Tipo de Scrap
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
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .card-title {
            color: #495057;
            font-weight: 600;
            margin: 0;
        }

        .alert-danger {
            border-left: 4px solid #dc3545;
        }

        .alert-heading {
            font-weight: 600;
            font-size: 1.1em;
        }

        .form-group label {
            margin-bottom: 0.5rem;
        }

        .btn-default {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #495057;
        }

        .btn-default:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .invalid-feedback {
            display: block;
        }

        .card-footer {
            background-color: #fff;
            padding: 1rem 1.25rem;
        }
    </style>
@stop
