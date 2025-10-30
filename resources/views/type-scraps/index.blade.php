@extends('adminlte::page')

@section('title', 'Tipos de Scrap')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Tipos de Scrap') }}</h1>
        <div class="col-md-4">
            <form action="{{ route('type-scraps.index') }}" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar..." value="{{ request('search') }}">
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
                        <h3 class="card-title m-0">{{ __('Lista de Tipos de Scrap') }}</h3>
                        @can('create type scraps')
                            <div class="ml-auto">
                                <a href="{{ route('type-scraps.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i> {{ __('Crear Tipo de Scrap') }}
                                </a>
                            </div>
                        @endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3 text-secondary fw-normal">{{ __('Nombre') }}</th>
                                        <th class="py-3 text-secondary fw-normal">{{ __('Descripción') }}</th>
                                        <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($typeScraps as $typeScrap)
                                        <tr class="border-top">
                                            <td class="ps-4 py-3 fw-medium">{{ $typeScrap->name }}</td>
                                            <td class="py-3">{{ $typeScrap->description ?? '-' }}</td>
                                            <td class="py-3 text-center">
                                                <div class="btn-group" role="group">
                                                    @can('edit type scraps')
                                                        <a href="{{ route('type-scraps.edit', $typeScrap->id) }}"
                                                           class="btn btn-sm btn-primary" title="Editar">
                                                            <i class="fas fa-edit mr-1"></i>
                                                            <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                        </a>
                                                    @endcan
                                                    @can('delete type scraps')
                                                        <form action="{{ route('type-scraps.destroy', $typeScrap->id) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('¿Estás seguro de eliminar este tipo de scrap?')"
                                                              style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                                <i class="fas fa-trash mr-1"></i>
                                                                <span class="d-none d-sm-inline">{{ __('Eliminar') }}</span>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                @if(request('search'))
                                                    {{ __('No se encontraron tipos de scrap que coincidan con') }} "{{ request('search') }}"
                                                @else
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                        <span class="text-secondary">{{ __('No hay tipos de scrap registrados') }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($typeScraps->hasPages() || $typeScraps->total() > 0)
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                {{ __('Mostrando') }} {{ $typeScraps->firstItem() ?? 0 }} - {{ $typeScraps->lastItem() ?? 0 }} {{ __('de') }} {{ $typeScraps->total() }}
                            </div>
                            <div>
                                {{ $typeScraps->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .pagination {
            margin-bottom: 0;
        }

        .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        .badge {
            font-weight: 500;
            font-size: 0.85rem;
        }

        .btn-group {
            white-space: nowrap;
        }

        .btn-group .btn {
            margin-right: 0.3rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-flex;
            align-items: center;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .btn-sm i {
            font-size: 0.8rem;
        }

        @media (max-width: 576px) {
            .btn-group .btn span {
                display: none;
            }

            .btn-sm i {
                margin-right: 0 !important;
            }
        }

        .input-group {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .form-control {
            border-radius: 0.25rem 0 0 0.25rem;
        }

        .input-group-append .btn {
            border-radius: 0 0.25rem 0.25rem 0;
        }

        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
    </style>
@stop
