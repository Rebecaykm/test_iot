@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Usuarios Registrados</h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </a>
    </div>
@stop

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="thead-dark">
                    <tr>
                        <th style="width: 40%">Usuario</th>
                        <th>Email</th>
                        <th style="width: 15%">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-circle symbol-50 mr-3">
                                        <div class="symbol-label bg-primary text-white">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-weight-600">{{ $user->name }}</div>
                                        <div class="text-muted">
                                                <span class="badge badge-light" style="background-color: #e8f4ff; color: #2196f3;">
                                                    {{ $user->roles->first()->name ?? 'Sin rol' }}
                                                </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <a href="{{ route('users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('users.destroy', $user) }}"
                                      method="POST"
                                      style="display: inline-block;"
                                      onsubmit="return confirm('¿Estás seguro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No hay usuarios registrados</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@stop

@section('css')
    <style>
        .symbol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .symbol.symbol-50 {
            width: 40px;
            height: 40px;
        }

        .symbol-label {
            border-radius: 50%;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .badge-light {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            border-radius: 12px;
            font-weight: 500;
        }

        .table-hover tbody tr:hover {
            background-color: #f5f6fa;
            transform: translateX(2px);
            transition: all 0.3s ease;
        }
    </style>
@stop
