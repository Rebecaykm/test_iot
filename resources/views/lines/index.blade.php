@extends('adminlte::page')

@section('title', 'Líneas')

@section('content_header')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="m-0 text-dark">{{ __('Líneas') }}</h1>
    <div class="col-md-4">
        <form action="{{ route('lines.index') }}" method="GET">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Buscar..."
                    value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h3 class="card-title m-0">Lista de Líneas</h3>
                    <div class="ml-auto"> <!-- Clase ml-auto añadida aquí -->
                        <a href="{{ route('lines.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Nueva Línea
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">{{ __('Línea') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Descripción') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Área') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Creación') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Actualización') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $line)
                                <tr class="border-top">
                                    <td class="ps-4 py-3 fw-medium">{{ $line->name }}</td>
                                    <td class="py-3">{{ $line->description ?? '' }}</td>
                                    <td class="py-3">
                                        @if ($line->area_id && isset($line->area->name))
                                        <span class="badge rounded-pill bg-primary text-white px-3 py-2">
                                            {{ $line->area->name }}
                                        </span>
                                        @else
                                        <span class="badge rounded-pill bg-secondary px-3 py-2">
                                            {{ __('Sin Área') }}
                                        </span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-muted">
                                        {{ optional($line->created_at)->format('d-m-Y H:i') ?? '' }}
                                    </td>
                                    <td class="py-3 text-muted">
                                        {{ optional($line->updated_at)->format('d-m-Y H:i') ?? '' }}
                                    </td>
                                    <td class="py-3 text-center">
                                        <div class="btn-group" role="group" aria-label="Acciones">
                                            <!-- Botón Editar -->
                                            <a href="{{ route('lines.edit', $line->id) }}"
                                                class="btn btn-sm btn-primary d-flex align-items-center"
                                                title="Editar">
                                                <i class="fas fa-edit mr-1"></i>
                                                <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                            </a>
                                            <!-- Botón Eliminar -->
                                            <form action="{{ route('lines.destroy', $line->id) }}"
                                                method="POST"
                                                style="display: inline-block;"
                                                onsubmit="return confirm('¿Estás seguro de eliminar esta línea?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger d-flex align-items-center">
                                                    <i class="fas fa-trash mr-1"></i>
                                                    <span class="d-none d-sm-inline">{{ __('Eliminar') }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 d-flex justify-content-end">
                    {{ $lines->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    /* Estilos para la paginación */
    .pagination {
        margin-bottom: 0;
    }

    .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
    }

    /* Estilos para badges */
    .badge {
        font-weight: 500;
        font-size: 0.85rem;
    }

    /* Estilos para botones de acción */
    .btn-group {
        white-space: nowrap;
    }

    .btn-group .btn {
        margin-right: 0.3rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .btn-sm i {
        font-size: 0.8rem;
    }

    /* Responsive para móviles */
    @media (max-width: 576px) {
        .btn-group .btn span {
            display: none;
        }

        .btn-sm i {
            margin-right: 0 !important;
        }
    }

    /* Estilos para el buscador */
    .input-group {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .form-control {
        border-radius: 0.25rem 0 0 0.25rem;
    }

    .input-group-append .btn {
        border-radius: 0 0.25rem 0.25rem 0;
    }
</style>
@stop
