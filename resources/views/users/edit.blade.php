@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Usuario</h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn-action btn-action-secondary"
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
                <i class="fas fa-user mr-2" style="color: #94a3b8;"></i>Información General
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="name" class="field-label">Nombre *</label>
                        <input type="text" name="name" id="name"
                            class="field-input @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" required placeholder="Ej: Miguel Álcala">
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="nickname" class="field-label">Nombre de Usuario *</label>
                        <input type="text" name="nickname" id="nickname"
                            class="field-input @error('nickname') is-invalid @enderror"
                            value="{{ old('nickname', $user->nickname) }}" required placeholder="Ej: miguel_alcala">
                        <small class="field-hint">Solo letras, números, guiones y guiones bajos</small>
                        @error('nickname')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="email" class="field-label">Correo Electrónico *</label>
                        <input type="email" name="email" id="email"
                            class="field-input @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}" required placeholder="Ej: usuario@dominio.com">
                        @error('email')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        @php $currentRole = old('role', $user->roles->first()?->name); @endphp
                        <x-select
                            name="role"
                            label="Rol"
                            :options="collect([['value' => '', 'label' => 'Seleccione un rol', 'selected' => !$currentRole]])
                                ->concat($roles->map(fn ($role) => [
                                    'value' => $role->name,
                                    'label' => ucfirst($role->name),
                                    'selected' => $currentRole == $role->name,
                                ]))"
                        />
                        @error('role')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="password" class="field-label">Contraseña</label>
                        <input type="password" name="password" id="password"
                            class="field-input @error('password') is-invalid @enderror"
                            placeholder="Dejar en blanco si no desea cambiarla">
                        <small class="field-hint">Mínimo 8 caracteres</small>
                        @error('password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="field-label">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="field-input" placeholder="Repite la contraseña">
                    </div>
                </div>

                {{-- Líneas asociadas --}}
                <div class="row mb-3">
                    <div class="col-md-12">
                        <x-multi-select
                            name="lines"
                            label="Líneas Asociadas"
                            placeholder="Ninguna línea seleccionada"
                            search-placeholder="Buscar línea..."
                            empty-message="No hay líneas disponibles para asociar."
                            :options="$lines->map(fn ($line) => [
                                'value' => $line->id,
                                'label' => $line->name,
                                'selected' => in_array($line->id, old('lines', $user->lines->pluck('id')->toArray())),
                            ])"
                        />
                        <small class="field-hint d-block mt-1">Las estaciones de cada línea se asignan automáticamente.</small>
                        @error('lines')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('users.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Actualizar Usuario</span>
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
