@extends('adminlte::page')

@section('title', 'Estaciones de Trabajo')

@section('content_header')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="m-0 text-dark">{{ __('Estaciones de Trabajo') }}</h1>
    <div class="col-md-4">
        <form action="{{ route('work-centers.index') }}" method="GET">
            <div class="input-group">
                <input type="text" name="search" class="form-control"
                    placeholder="Buscar..."
                    value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request()->has('search'))
                    <a href="{{ route('work-centers.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
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
                        <h3 class="card-title m-0">Lista de Estaciones</h3>
                        <div>
                            <!-- Botones de acción si los necesitas -->
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">{{ __('Línea') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Número') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Nombre') }}</th>
                                    <th class="py-3 text-secondary fw-normal d-none-mobile">{{ __('Fecha de Creación') }}</th>
                                    <th class="py-3 text-secondary fw-normal d-none-mobile">{{ __('Fecha de Actualización') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Acciones') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($workCenters as $workCenter)
                                    <tr class="border-top">
                                        <td class="ps-4 px-3">
                                            @if ($workCenter->line)
                                                <span class="badge rounded-pill bg-primary text-white px-3 py-2">
                                            {{ $workCenter->line->name }}
                                        </span>
                                            @else
                                                <span class="badge rounded-pill bg-secondary px-3 py-2">
                                            {{ __('Sin Línea') }}
                                        </span>
                                            @endif
                                        </td>
                                        <td class="py-3 fw-medium">{{ $workCenter->number }}</td>
                                        <td class="py-3">{{ $workCenter->name }}</td>
                                        <td class="py-3 text-muted d-none-mobile">{{ $workCenter->created_at->format('d-m-Y H:i') }}</td>
                                        <td class="py-3 text-muted d-none-mobile">{{ $workCenter->updated_at->format('d-m-Y H:i') }}</td>
                                        <td class="py-3">
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                @can('edit work centers')
                                                    <a href="{{ route('work-centers.edit', $workCenter->id) }}"
                                                       class="btn btn-sm btn-primary d-flex align-items-center"
                                                       title="Editar"
                                                       aria-label="Editar estación de trabajo">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            @if(request()->has('search'))
                                                No se encontraron estaciones que coincidan con "{{ request('search') }}"
                                            @else
                                                No hay estaciones de trabajo registradas
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
                            <div class="text-muted">
                                MOSTRANDO {{ $workCenters->firstItem() ?? 0 }} -
                                {{ $workCenters->lastItem() ?? 0 }} DE {{ $workCenters->total() }}
                            </div>
                            <div>
                                {{ $workCenters->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
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
