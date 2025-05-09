@extends('adminlte::page')

@section('title', 'Editar Rol')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Editar Rol</h1>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">
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
                            Información del Rol
                        </h3>
                    </div>

                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="name" class="font-weight-bold">Nombre del Rol <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name" value="{{ old('name', $role->name) }}"
                                               placeholder="Ej: Editor" required>
                                        @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Permisos</label>
                                        <div class="permissions-container border rounded p-3">
                                            <div class="row">
                                                @foreach($permissions->groupBy(function($item) {
                                                    return explode(' ', $item->name)[0];
                                                }) as $group => $groupPermissions)
                                                    <div class="col-md-4 mb-3">
                                                        <h6 class="font-weight-bold text-uppercase">{{ $group }}</h6>
                                                        @foreach($groupPermissions as $permission)
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="permissions[]"
                                                                       value="{{ $permission->id }}"
                                                                       id="permission_{{ $permission->id }}"
                                                                    {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                    {{ ucwords(str_replace($group.' ', '', $permission->name)) }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @error('permissions')
                                        <span class="text-danger" style="font-size: 0.875rem;">
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
                                <i class="fas fa-save mr-1"></i> Actualizar Rol
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

        .permissions-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .form-check-label {
            margin-left: 5px;
        }
    </style>
@stop
