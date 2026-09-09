@extends('adminlte::page')

@section('title', 'Editar Rol')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Rol</h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('roles.index') }}" class="btn-action btn-action-secondary"
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
                <i class="fas fa-user-shield mr-2" style="color: #94a3b8;"></i>Información del Rol
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="name" class="field-label">Nombre del Rol *</label>
                        <input type="text" name="name" id="name"
                            class="field-input @error('name') is-invalid @enderror"
                            value="{{ old('name', $role->name) }}" required placeholder="Ej: Editor">
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="field-label">Permisos</label>
                        <div class="permissions-container" style="max-height: 400px; overflow-y: auto;">
                            @if ($permissions->count() > 0)
                                <div class="row">
                                    @foreach ($permissions->groupBy(fn($item) => explode(' ', $item->name)[0]) as $group => $groupPermissions)
                                        <div class="col-md-4 mb-4">
                                            <div class="permission-group">
                                                <div class="permission-group-header">
                                                    <span class="section-title" style="font-size: 0.8rem;">
                                                        <i class="fas fa-folder mr-2" style="color: #94a3b8;"></i>{{ $group }}
                                                    </span>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input select-all-group"
                                                            id="select-all-{{ $group }}">
                                                        <label class="custom-control-label" for="select-all-{{ $group }}"
                                                            style="font-size: 0.72rem; color: #64748b;">Todos</label>
                                                    </div>
                                                </div>
                                                <div class="permission-group-body">
                                                    @foreach ($groupPermissions as $permission)
                                                        <div class="custom-control custom-checkbox mb-2">
                                                            <input class="custom-control-input" type="checkbox"
                                                                name="permissions[]" value="{{ $permission->id }}"
                                                                id="permission_{{ $permission->id }}"
                                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                                            <label class="custom-control-label" for="permission_{{ $permission->id }}"
                                                                style="font-size: 0.82rem; color: #334155;">
                                                                {{ ucwords(str_replace($group . ' ', '', $permission->name)) }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">No hay permisos disponibles.</p>
                            @endif
                        </div>
                        @error('permissions')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('roles.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="reset" class="btn-action btn-action-secondary">
                        <i class="fas fa-undo"></i>
                        <span>Restablecer</span>
                    </button>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Actualizar Rol</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
    <style>
        .permissions-container {
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 1rem;
            background: #f8fafc;
        }
        .permission-group {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            height: 100%;
        }
        .permission-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .permission-group-body {
            padding: 0.75rem 0.85rem;
        }
    </style>
@stop

@section('js')
    @include('partials.theme-scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.permission-group').forEach(group => {
                const selectAll = group.querySelector('.select-all-group');
                const checkboxes = group.querySelectorAll('.permission-group-body .custom-control-input');

                // Estado inicial: marcado si todos los permisos del grupo ya están seleccionados
                selectAll.checked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);

                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            });
        });
    </script>
@stop
