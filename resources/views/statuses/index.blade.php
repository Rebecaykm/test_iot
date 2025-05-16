@extends('adminlte::page')

@section('title', 'Estados')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Estados') }}</h1>
        <div class="col-md-4">
            <form action="{{ route('statuses.index') }}" method="GET">
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
                        <h3 class="card-title m-0">Lista de Estados</h3>
                        @can('create statuses')
                            <div class="ml-auto">
                                <a href="{{ route('statuses.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i> Nuevo Estado
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
                                    <th class="py-3 text-secondary fw-normal">{{ __('Creado') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Actualizado') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($statuses as $status)
                                    <tr class="border-top">
                                        <td class="ps-4 py-3 fw-medium">{{ $status->name }}</td>
                                        <td class="py-3">{{ $status->description ?? '-' }}</td>
                                        <td class="py-3 text-muted">
                                            {{ $status->created_at->format('d-m-Y H:i') }}
                                        </td>
                                        <td class="py-3 text-muted">
                                            {{ $status->updated_at->format('d-m-Y H:i') }}
                                        </td>
                                        <td class="py-3 text-center">
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                @can('edit statuses')
                                                    <a href="{{ route('statuses.edit', $status->id) }}"
                                                       class="btn btn-sm btn-primary d-flex align-items-center"
                                                       title="Editar">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="d-none d-sm-inline">{{ __('Editar') }}</span>
                                                    </a>
                                                @endcan
                                                @can('delete statuses')
                                                    <form action="{{ route('statuses.destroy', $status->id) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('¿Eliminar este estado?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-danger d-flex align-items-center">
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
                                        <td colspan="5" class="text-center py-4">
                                            @if(request()->has('search'))
                                                No se encontraron resultados para "{{ request('search') }}"
                                            @else
                                                No hay estados registrados
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 d-flex justify-content-end">
                        {{ $statuses->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
