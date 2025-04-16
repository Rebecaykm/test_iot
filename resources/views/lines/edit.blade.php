@extends('adminlte::page')

@section('title', 'Editar Línea')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark"></i>Editar Línea</h1>
    <a href="{{ route('lines.index') }}" class="btn btn-secondary">
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
                        Información de la Línea
                    </h3>
                </div>

                <form action="{{ route('lines.update', $line->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name" class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $line->name) }}"
                                        placeholder="Ej: Línea de Ensamblaje Principal" required>
                                    @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area_id" class="font-weight-bold">Área</label>
                                    <select class="form-control @error('area_id') is-invalid @enderror"
                                        id="area_id" name="area_id">
                                        <option value="">Seleccione un área...</option>
                                        @foreach($areas as $area)
                                        <option value="{{ $area->id }}" {{ old('area_id', $line->area_id) == $area->id ? 'selected' : '' }}>
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

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="color" class="font-weight-bold">Color de la Línea <span class="text-danger">*</span></label>
                                    <select class="form-control @error('color') is-invalid @enderror"
                                        id="color" name="color" required>
                                        <option value="">Seleccione un color...</option>
                                        @foreach([
                                            '#D3D3D3' => 'Gris',
                                            '#FFB6C1' => 'Rojo',
                                            '#FFD8B1' => 'Naranja',
                                            '#FFFACD' => 'Amarillo',
                                            '#98FB98' => 'Verde',
                                            '#AFEEEE' => 'Teal',
                                            '#ADD8E6' => 'Azul',
                                            '#C3CDE6' => 'Índigo',
                                            '#E6E6FA'=> 'Púrpura',
                                            '#FFD1DC' => 'Rosa'
                                        ] as $hex => $name)
                                        <option value="{{ $hex }}" {{ old('color', $line->color) == $hex ? 'selected' : '' }} style="background-color: {{ $hex }};">
                                            {{ $name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('color')
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
                                        placeholder="Descripción detallada de la línea">{{ old('description', $line->description) }}</textarea>
                                    @error('description')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="font-weight-bold">Estaciones de Trabajo Disponibles</label>
                                    <div class="stations-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                                        <div class="row">
                                            @foreach($workCenters->chunk(ceil($workCenters->count() / 3)) as $column)
                                            <div class="col-md-4">
                                                @foreach($column as $workCenter)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="work_centers[]"
                                                        id="wc_{{ $workCenter->id }}"
                                                        value="{{ $workCenter->id }}"
                                                        {{ (is_array(old('work_centers')) && in_array($workCenter->id, old('work_centers'))) ||
                                                           (!old('work_centers') && $workCenter->line_id == $line->id) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="wc_{{ $workCenter->id }}">
                                                        {{ $workCenter->number }} - {{ $workCenter->name }}
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @error('work_centers')
                                    <span class="text-danger" style="font-size: 0.875em;">
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
                            <i class="fas fa-save mr-1"></i> Actualizar Línea
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
    select option {
        padding: 5px;
    }
    .stations-container {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
    }
</style>
@stop
