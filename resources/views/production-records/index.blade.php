@extends('adminlte::page')

@section('title', 'Registro de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0 text-dark">{{ __('Registro de Producción') }}</h1>
        <div class="col-md-4">
            <form action="{{ route('production-records.index') }}" method="GET">
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
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Estación') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Número de Parte') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Fecha') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Turno') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Cantidad Planeada') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Cantidad Producida') }}</th>
                                    <th class="py-3 text-secondary fw-normal">{{ __('Cantidad de Scrap') }}</th>
                                    <th class="py-3 text-secondary fw-normal text-center">{{ __('Acciones') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($productionRecords as $productionRecord)
                                    <tr class="border-top">
                                        <td class="py-3">
                                            <span class="badge rounded-pill px-3 py-2 text-black"
                                                  style="background-color: {{ $productionRecord->line_color ?? '#6c757d' }}">
                                                {{ $productionRecord->work_name }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill bg-primary px-3 py-2">
                                                {{ $productionRecord->part_number }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-muted">
                                            <span class="badge rounded-pill bg-secondary px-3 py-2">
                                                {{ \Carbon\Carbon::parse($productionRecord->planned_date)->format('d-m-Y') }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill px-3 py-2 text-white" style="background-color:
                                                {{ $productionRecord->shift_name === 'D' ? '#FF7043' :
                                                ($productionRecord->shift_name === 'N' ? '#6f42c1' : '#6c757d') }}">
                                                {{ $productionRecord->shift_name }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <input type="number" name="planned_quantity"
                                                   value="{{ $productionRecord->planned_quantity }}"
                                                   class="form-control form-control-sm text-center" readonly />
                                        </td>
                                        <td class="py-3">
                                            <input type="number" name="produced_quantity"
                                                   value="{{ $productionRecord->produced_quantity }}"
                                                   class="form-control form-control-sm text-center" readonly />
                                        </td>
                                        <td class="py-3">
                                            <input type="number" name="scrap_quantity"
                                                   value="{{ $productionRecord->scrap_quantity }}"
                                                   class="form-control form-control-sm text-center" />
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group" role="group" aria-label="Acciones">
{{--                                                @can('create production records')--}}
                                                        <a href="{{ route('production-records.store', $productionRecord->id) }}"
                                                           class="btn btn-sm btn-primary d-flex align-items-center rounded-pill"
                                                           title="Guardar">
                                                        <div class="py-1 px-4">
                                                            <i class="fas fa-save mr-1"></i>
                                                            <span class="d-none d-sm-inline">{{ __('Guardar') }}</span>
                                                        </div>
                                                    </a>
{{--                                                @endcan--}}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            @if(request()->has('search'))
                                                No se encontraron resultados para "{{ request('search') }}"
                                            @else
                                                No hay registros de producción
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 d-flex justify-content-end">
                        {{ $productionRecords->links() }}
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
