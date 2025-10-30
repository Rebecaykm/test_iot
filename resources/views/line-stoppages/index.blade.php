@extends('adminlte::page')

@section('title', 'Paro de Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Paro de Línea') }}</h1>
        <div class="col-md-4">
            <form action="{{ route('line-stoppages.index') }}" method="GET">
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
                        <h3 class="card-title m-0">{{ __('Lista de Paro de Línea') }}</h3>
                        @can('create line stoppages')
                            <div class="ml-auto">
                                <a href="{{ route('line-stoppages.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i> {{ __('Agregar Paro de Línea') }}
                                </a>
                            </div>
                        @endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-normal">{{ __('Código') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Nombre') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Tipo') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Descripción') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha de Creación') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($lineStoppages as $stoppage)
                                    <tr class="border-top">
                                        <td class="ps-4 py-3 fw-medium">{{ $stoppage->code }}</td>
                                        <td class="py-3">{{ $stoppage->name }}</td>
                                        <td class="py-3">
                                            @if ($stoppage->typeLineStoppage)
                                                <span class="badge rounded-pill bg-primary text-white px-3 py-2">
                                                    {{ $stoppage->typeLineStoppage->name }}
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-secondary px-3 py-2">
                                                    {{ __('Sin Tipo') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3">{{ $stoppage->description ?? 'N/A' }}</td>
                                        <td class="py-3 text-muted">{{ optional($stoppage->created_at)->format('d-m-Y H:i') }}</td>
                                        <td class="py-3 text-center">
                                            <div class="btn-group" role="group">
                                                @can('edit line stoppages')
                                                    <a href="{{ route('line-stoppages.edit', $stoppage->id) }}" class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan

                                                @can('delete line stoppages')
                                                    <form action="{{ route('line-stoppages.destroy', $stoppage->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta parada de línea?')" style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
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
                                        <td colspan="6" class="text-center py-4">
                                            @if(request('search'))
                                                {{ __('No se encontraron paro de línea que coincidan con') }} "{{ request('search') }}"
                                            @else
                                                {{ __('No hay paro de línea registradas') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            {{ __('Mostrando') }} {{ $lineStoppages->firstItem() ?? 0 }} - {{ $lineStoppages->lastItem() ?? 0 }} {{ __('de') }} {{ $lineStoppages->total() }}
                        </div>
                        <div>
                            {{ $lineStoppages->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
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
    </style>
@stop
