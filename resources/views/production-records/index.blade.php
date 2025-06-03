@extends('adminlte::page')

@section('title', 'Registro de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="m-0 text-dark h5 font-weight-bold text-uppercase">
            {{ __('Registro de Producción') }}
        </h1>
        <div class="col-md-5">
            <form action="{{ route('production-records.index') }}" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control border-primary shadow-sm"
                           placeholder="Buscar" value="{{ request('search') }}">
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
                <div class="card card-primary card-outline">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                <tr class="text-uppercase">
                                    <th class="py-2 px-3">{{ __('Estación') }}</th>
                                    <th class="py-2 px-3">{{ __('Número de Parte') }}</th>
                                    <th class="py-2 px-3">{{ __('Número de Orden') }}</th>
                                    <th class="py-2 px-3">{{ __('Fecha') }}</th>
                                    <th class="py-2 px-3">{{ __('Turno') }}</th>
                                    <th class="py-2 px-3 text-center">{{ __('Planeada') }}</th>
                                    <th class="py-2 px-3 text-center">{{ __('Producida') }}</th>
                                    <th class="py-2 px-3 text-center">{{ __('Scrap') }}</th>
                                    <th class="py-2 px-3 text-center">{{ __('Acciones') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($productionRecords as $productionRecord)
                                    <tr>
                                        <form action="{{ route('production-records.update', $productionRecord->production_id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <td class="py-2 px-3 align-middle">
                                                <span class="badge badge-pill px-3 py-1" style="background-color: {{ $productionRecord->line_color }}; color: white;">
                                                    {{ $productionRecord->work_name }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-3 align-middle">
                                                <span class="badge badge-pill px-3 py-1" style="background-color: {{ $productionRecord->line_color }}; color: white;">
                                                    {{ $productionRecord->part_number }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-3 align-middle">
                                                {{ $productionRecord->shop_order_number }}
                                            </td>
                                            <td class="py-2 px-3 align-middle">
                                                {{ \Carbon\Carbon::parse($productionRecord->planned_date)->format('d-m-Y') }}
                                            </td>
                                            <td class="py-2 px-3 align-middle">
                                                {{ $productionRecord->shift_name}}
                                            </td>
                                            <td class="py-2 px-3 text-center align-middle">
                                                <span class="badge badge-primary badge-pill px-3 py-1">
                                                    {{ number_format($productionRecord->planned_quantity, 0) }}
                                                </span>
                                                <input type="hidden" name="planned_quantity" value="{{ $productionRecord->planned_quantity }}">
                                            </td>
                                            <td class="py-2 px-3 text-center align-middle">
                                                <span class="badge badge-pill px-3 py-1
                                                    @if($productionRecord->produced_quantity < $productionRecord->planned_quantity) badge-danger
                                                    @elseif($productionRecord->produced_quantity == $productionRecord->planned_quantity) badge-success
                                                    @else badge-warning
                                                    @endif">
                                                    {{ number_format($productionRecord->produced_quantity, 0) }}
                                                </span>
                                                <input type="hidden" name="produced_quantity" value="{{ $productionRecord->produced_quantity }}">
                                            </td>
                                            <td class="py-2 px-3 text-center align-middle">
                                                <input type="number" name="scrap_quantity" value="{{ $productionRecord->scrap_quantity }}"
                                                       class="form-control form-control-sm border-primary text-center shadow-sm" min="0" style="width: 80px;">
                                            </td>
                                            <td class="py-2 px-3 text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="confirmSubmit(this)">
                                                    <i class="fas fa-save mr-1"></i>
                                                    <span class="d-none d-sm-inline">Guardar</span>
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-database fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">
                                                @if(request()->has('search'))
                                                    No se encontraron resultados para tu búsqueda
                                                @else
                                                    No hay registros disponibles
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                            <div class="text-muted small mb-2 mb-md-0">
                                Mostrando {{ $productionRecords->firstItem() ?? 0 }} a {{ $productionRecords->lastItem() ?? 0 }} de {{ $productionRecords->total() }} registros
                            </div>
                            <div>
                                {{ $productionRecords->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('js')
    <script>
        function confirmSubmit(button) {
            Swal.fire({
                title: "¿Confirmar acción?",
                text: "¿Estás seguro de guardar los cambios?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, guardar",
                cancelButtonText: "Cancelar",
                buttonsStyling: true
            }).then((result) => {
                if (result.isConfirmed) {
                    button.closest('form').submit();
                }
            });
        }
    </script>
@endpush
