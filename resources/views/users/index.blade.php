@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Usuarios</h1>
        </div>

        <div class="d-flex" style="gap: 0.5rem;">
            @can('create users')
                <a href="{{ route('users.create') }}" class="btn-action btn-action-primary">
                    <i class="fas fa-plus"></i>
                    <span>Agregar Usuario</span>
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('users.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: 0.5rem;">

                    <div class="filter-group" style="width: 280px;">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" name="search" class="filter-input" placeholder="Buscar..."
                            value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-filter-submit">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('users.index') }}" class="btn-filter-clear">
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
                            <th class="th-cell">Nombre</th>
                            <th class="th-cell">Usuario</th>
                            <th class="th-cell">Rol</th>
                            <th class="th-cell">Líneas</th>
                            <th class="th-cell">Creado</th>
                            <th class="th-cell text-center">Acciones</th>
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
                                        <div class="d-flex flex-wrap" style="gap: 0.35rem;">
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
                                    <div class="d-flex justify-content-center" style="gap: 0.5rem;">
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
                                            @if (request('search'))
                                                No se encontraron usuarios que coincidan con "{{ request('search') }}"
                                            @else
                                                No hay usuarios registrados
                                            @endif
                                        </p>
                                        @if (request('search'))
                                            <a href="{{ route('users.index') }}" class="btn-filter-clear mt-2">
                                                Limpiar búsqueda
                                            </a>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                    <small class="text-muted">
                        Mostrando <strong>{{ $users->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $users->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $users->total() }}</strong> resultados
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
@stop

@section('js')
    @include('partials.theme-scripts', ['deleteMessage' => '¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.'])
@stop
