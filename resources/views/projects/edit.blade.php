@extends('adminlte::page')

@section('title', 'Editar Proyecto')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Proyecto</h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('projects.index') }}" class="btn-action btn-action-secondary"
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
                <i class="fas fa-project-diagram mr-2" style="color: #94a3b8;"></i>Información del Proyecto
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('projects.update', $project->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        @php $currentClientId = old('client_id', $project->client_id); @endphp
                        <x-select
                            name="client_id"
                            label="Cliente *"
                            :options="collect([['value' => '', 'label' => 'Seleccione un cliente', 'selected' => !$currentClientId]])
                                ->concat($clients->map(fn ($client) => [
                                    'value' => $client->id,
                                    'label' => $client->code . ' - ' . $client->name,
                                    'selected' => $currentClientId == $client->id,
                                ]))"
                        />
                        @error('client_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="type" class="field-label">Tipo *</label>
                        <input type="text" name="type" id="type"
                            class="field-input @error('type') is-invalid @enderror"
                            value="{{ old('type', $project->type) }}" required placeholder="Ej: Automotriz">
                        @error('type')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="model" class="field-label">Modelo *</label>
                        <input type="text" name="model" id="model"
                            class="field-input @error('model') is-invalid @enderror"
                            value="{{ old('model', $project->model) }}" required placeholder="Ej: Modelo X2024">
                        @error('model')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="prefix" class="field-label">Prefijo *</label>
                        <input type="text" name="prefix" id="prefix"
                            class="field-input @error('prefix') is-invalid @enderror"
                            value="{{ old('prefix', $project->prefix) }}" required placeholder="Ej: PRJ-" maxlength="10">
                        @error('prefix')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('projects.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="reset" class="btn-action btn-action-secondary">
                        <i class="fas fa-undo"></i>
                        <span>Restablecer</span>
                    </button>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Actualizar Proyecto</span>
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
