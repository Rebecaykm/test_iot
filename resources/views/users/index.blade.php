@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Usuarios</h1>
        </div>

        <div class="d-flex gap-2">
            @can('create users')
                <a href="{{ route('users.create') }}" class="btn-action btn-action-primary"
                    aria-label="Agregar nuevo usuario">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span class="d-none d-md-inline">Agregar Usuario</span>
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('users.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">

                    <div class="filter-group" style="width: 280px;">
                        <label for="user-search" class="sr-only">Buscar usuario</label>
                        <i class="fas fa-search filter-icon" aria-hidden="true"></i>
                        <input type="text" id="user-search" name="search" class="filter-input"
                            placeholder="Nombre, usuario, rol, línea..." value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('users.index') }}" class="btn-action btn-action-secondary">
                            <i class="fas fa-times mr-1"></i>Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell" scope="col">Nombre</th>
                            <th class="th-cell" scope="col">Usuario</th>
                            <th class="th-cell" scope="col">Rol</th>
                            <th class="th-cell" scope="col">Líneas</th>
                            <th class="th-cell" scope="col">Creado</th>
                            <th class="th-cell text-center" scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <span class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $user->name }}</span>
                                </td>

                                <td class="td-cell">
                                    <div style="font-size: 0.85rem; color: #334155;">{{ $user->nickname }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $user->email }}</div>
                                </td>

                                <td class="td-cell">
                                    @if ($user->roles->first())
                                        <span class="badge-soft badge-success">{{ $user->roles->first()->name }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">Sin Rol</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    @if ($user->lines->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($user->lines as $line)
                                                <span class="badge-soft badge-primary">{{ $line->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="badge-soft badge-secondary">Sin Líneas</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    <div class="fw-600" style="font-size: 0.82rem; color: #334155;">
                                        {{ optional($user->created_at)->format('Y-m-d') }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ optional($user->created_at)->format('H:i') }}
                                    </div>
                                </td>

                                <td class="td-cell text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit users')
                                            <a href="{{ route('users.edit', $user->id) }}"
                                                class="btn-action btn-action-primary btn-action-sm">
                                                <i class="fas fa-edit"></i>
                                                <span>Editar</span>
                                            </a>
                                        @endcan

                                        @can('delete users')
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                style="display:inline;" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action btn-action-danger btn-action-sm">
                                                    <i class="fas fa-trash"></i>
                                                    <span>Eliminar</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">
                                            @if (request()->filled('search'))
                                                No se encontraron usuarios que coincidan con "{{ request('search') }}"
                                            @else
                                                No hay usuarios registrados
                                            @endif
                                        </p>
                                        @if (request()->filled('search'))
                                            <small>Intenta ajustar la búsqueda</small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación --}}
        @if ($users->hasPages() || $users->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando <strong>{{ $users->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $users->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $users->total() }}</strong> registros
                    </small>
                    @if ($users->hasPages())
                        {{ $users->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @endif
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.theme-scripts', ['deleteMessage' => '¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.'])
@stop
