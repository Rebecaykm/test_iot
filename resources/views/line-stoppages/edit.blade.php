@extends('adminlte::page')

@section('title', 'Editar Paro de Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Editar Paro de Línea</h1>
        <a href="{{ route('line-stoppages.index') }}" class="btn btn-secondary">
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
                            Información de la Paro de Línea
                        </h3>
                    </div>

                    <form action="{{ route('line-stoppages.update', $lineStoppage->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="code" class="font-weight-bold">Código <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                                            id="code" name="code" value="{{ old('code', $lineStoppage->code) }}"
                                            placeholder="Ej: PAR-001" required>
                                        @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="name" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $lineStoppage->name) }}"
                                            placeholder="Ej: Paro por Mantenimiento" required>
                                        @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type_line_stoppage_id" class="font-weight-bold">Tipo de Paro <span class="text-danger">*</span></label>
                                        <select class="form-control @error('type_line_stoppage_id') is-invalid @enderror"
                                            id="type_line_stoppage_id" name="type_line_stoppage_id" required>
                                            <option value="">Seleccione un tipo...</option>
                                            @foreach($typeLineStoppages as $type)
                                            <option value="{{ $type->id }}" {{ old('type_line_stoppage_id', $lineStoppage->type_line_stoppage_id) == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('type_line_stoppage_id')
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
                                            placeholder="Descripción detallada de la paro">{{ old('description', $lineStoppage->description) }}</textarea>
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
                                <i class="fas fa-save mr-1"></i> Actualizar Paro de Línea
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
