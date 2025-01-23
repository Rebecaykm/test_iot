@extends('adminlte::page')

@section('title', 'Números de Parte')

@section('content_header')
<div class="card-header d-flex justify-content-between">
    <h3 class="card-title text-uppercase">{{ __('Números de Partes') }}</h3>
</div>
@stop

@section('content')
<div class="card">
    <!-- <div class="card-header d-flex justify-content-between">
        <div class="d-flex w-100">
            <form action="{{ route('part-numbers.index') }}" method="GET" class="d-flex w-100">
                <input type="text" name="search" class="form-control" id="search" placeholder="Buscar..." style="flex-grow: 3;">
                <button type="submit" class="btn btn-outline-secondary ms-2" style="flex-shrink: 0;">
                    <i class="fas fa-search"></i> {{ __('Buscar') }}
                </button>
            </form>

            <form action="{{ route('part-numbers.create') }}" method="GET" class="ms-2" style="flex-shrink: 0;">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-plus"></i> {{ __('Agregar Nuevo Registro') }}
                </button>
            </form>
        </div>
    </div> -->
    <div class="card-body p-0">
        <div>
            <table class="table table-hover table-md">
                <thead>
                    <tr>
                        <th>{{ __('Número') }}</th>
                        <th>{{ __('Nombre') }}</th>
                        <th>{{ __('Estación') }}</th>
                        <th>{{ __('Fecha de Creación') }}</th>
                        <!-- <th>{{ __('Acciones') }}</th> -->
                    </tr>
                </thead>
                <tbody class="">
                    @foreach ($partNumbers as $partNumber)
                    <tr>
                        <td>{{ $partNumber->number }}</td>
                        <td>{{ $partNumber->name }}</td>
                        <td>{{ $partNumber->workCenter->name ?? 'No Asignado' }}</td>
                        <td>{{ $partNumber->created_at->format('d/m/Y H:i') }}</td>
                        <!-- <td>
                            <div class="d-flex justify-content-start">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded mr-2" title="Editar">
                                    <i class="fas fa-edit"></i> {{ __('Editar') }}
                                </button>
                                <form action="{{ route('part-numbers.destroy', $partNumber->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i> {{ __('Eliminar') }}
                                    </button>
                                </form>
                            </div>
                        </td> -->
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-end">
            {{ $partNumbers->links() }}
        </div>
    </div>
</div>

@stop
