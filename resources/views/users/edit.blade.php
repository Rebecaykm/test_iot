@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Usuario</h1>
        </div>

        <div class="d-flex" style="gap: 0.5rem;">
            <a href="{{ route('users.index') }}" class="btn-action btn-action-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="d-none d-md-inline">Volver</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
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
                        <label for="role" class="field-label">Rol</label>
                        <select name="role" id="role"
                            class="field-input select2 @error('role') is-invalid @enderror" style="width: 100%;">
                            <option value="">Seleccione un rol</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}"
                                    {{ (old('role', $user->roles->first()?->name) == $role->name) ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
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
                        <label class="field-label">Líneas Asociadas</label>
                        <small class="field-hint d-block mb-2">Las estaciones de cada línea se asignan automáticamente.</small>
                        <div class="check-container">
                            @if ($lines->count() > 0)
                                <div class="row">
                                    @foreach ($lines as $line)
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="lines[]" id="line_{{ $line->id }}" value="{{ $line->id }}"
                                                    {{ in_array($line->id, old('lines', $user->lines->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="line_{{ $line->id }}">
                                                    {{ $line->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted small mb-0">No hay líneas disponibles para asociar.</div>
                            @endif
                        </div>
                        @error('lines')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end" style="gap: 0.5rem; margin-top: 1.5rem;">
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
@stop

@section('js')
    @include('partials.theme-scripts')

    <script>
        $(document).ready(function() {
            $('#role').select2({
                placeholder: 'Seleccione un rol',
                allowClear: false,
                width: '100%'
            });
        });
    </script>
@stop
