@extends('adminlte::page')

@section('title', 'Editar Turno')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Turno</h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('shifts.index') }}" class="btn-action btn-action-secondary"
                aria-label="Volver al listado">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Volver</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-clock mr-2" style="color: #94a3b8;"></i>Información del Turno
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('shifts.update', $shift->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="abbreviation" class="field-label">Abreviatura *</label>
                        <input type="text" name="abbreviation" id="abbreviation"
                            class="field-input @error('abbreviation') is-invalid @enderror"
                            value="{{ old('abbreviation', $shift->abbreviation) }}" maxlength="2" required
                            placeholder="Ej: D, N">
                        @error('abbreviation')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="name" class="field-label">Nombre *</label>
                        <input type="text" name="name" id="name"
                            class="field-input @error('name') is-invalid @enderror"
                            value="{{ old('name', $shift->name) }}" required placeholder="Ej: Turno Diurno">
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="start_time" class="field-label">Hora de Inicio *</label>
                        <input type="time" name="start_time" id="start_time"
                            class="field-input @error('start_time') is-invalid @enderror"
                            value="{{ old('start_time', substr($shift->start_time, 0, 5)) }}" required>
                        @error('start_time')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="end_time" class="field-label">Hora de Fin *</label>
                        <input type="time" name="end_time" id="end_time"
                            class="field-input @error('end_time') is-invalid @enderror"
                            value="{{ old('end_time', substr($shift->end_time, 0, 5)) }}" required>
                        @error('end_time')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="description" class="field-label">Descripción</label>
                        <input type="text" name="description" id="description"
                            class="field-input @error('description') is-invalid @enderror"
                            value="{{ old('description', $shift->description) }}" placeholder="Descripción del turno">
                        @error('description')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('shifts.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="reset" class="btn-action btn-action-secondary">
                        <i class="fas fa-undo"></i>
                        <span>Restablecer</span>
                    </button>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Actualizar Turno</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
@stop

@section('js')
    @include('partials.theme-scripts')
@stop
