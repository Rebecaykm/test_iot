@extends('adminlte::page')

@section('title', 'Registro de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="m-0 text-dark"></i>{{ __('REGISTRO DE PRODUCCIÓN') }}</h1>
        <div class="col-md-4">
            <form action="{{ route('production-records.index') }}" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control border-primary" placeholder="BUSCAR..."
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
                <div class="card card-primary card-outline">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                <tr>
                                    <th class="py-3 text-uppercase">{{ __('ESTACIÓN') }}</th>
                                    <th class="py-3 text-uppercase">{{ __('NÚMERO DE PARTE') }}</th>
                                    <th class="py-3 text-uppercase">{{ __('FECHA') }}</th>
                                    <th class="py-3 text-uppercase">{{ __('TURNO') }}</th>
                                    <th class="py-3 text-uppercase text-center">{{ __('PLANEADA') }}</th>
                                    <th class="py-3 text-uppercase text-center">{{ __('PRODUCIDA') }}</th>
                                    <th class="py-3 text-uppercase text-center">{{ __('SCRAP') }}</th>
                                    <th class="py-3 text-uppercase text-center">{{ __('ACCIONES') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($productionRecords as $productionRecord)
                                    <tr class="border-top">
                                        <form
                                            action="{{ route('production-records.update', $productionRecord->production_id) }}"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <td class="py-3 align-middle">
                                                <span
                                                    class="badge rounded-pill px-3 py-2 {{ \App\Helpers\ColorHelper::getContrastColor($productionRecord->line_color) }}"
                                                    style="background-color: {{ \App\Helpers\ColorHelper::hexToRgba($productionRecord->line_color, 1.0) }};">
                                                    {{ strtoupper($productionRecord->work_name) }}
                                                </span>
                                            </td>
                                            <td class="py-3 align-middle" >
                                                <span
                                                    class="badge rounded-pill px-3 py-2 {{ \App\Helpers\ColorHelper::getContrastColor($productionRecord->line_color) }}"
                                                    style="background-color: {{ \App\Helpers\ColorHelper::hexToRgba($productionRecord->line_color, 1.0) }};">
                                                    {{ strtoupper($productionRecord->part_number) }}
                                                </span>
                                            </td>
                                            <td class="py-3 align-middle" >
                                                {{ \Carbon\Carbon::parse($productionRecord->planned_date)->format('d-m-Y') }}
                                            </td>
                                            <td class="py-3 align-middle" >
                                                {{ $productionRecord->shift_name}}
                                            </td>
                                            <td class="py-3 text-center align-middle" >
                                                <span class="rounded-pill px-3 py-2 bg-primary text-white">
                                                    {{ number_format($productionRecord->planned_quantity, 0) }}
                                                </span>
                                                <input type="number" name="planned_quantity"
                                                       value="{{ $productionRecord->planned_quantity }}" hidden>
                                            </td>
                                            <td class="py-3 text-center align-middle" >
                                                <span class="rounded-pill px-3 py-2 text-white
                                                    @if ($productionRecord->produced_quantity < $productionRecord->planned_quantity)
                                                        bg-warning
                                                    @elseif ($productionRecord->produced_quantity == $productionRecord->planned_quantity)
                                                        bg-success
                                                    @elseif ($productionRecord->produced_quantity > $productionRecord->planned_quantity)
                                                        bg-danger
                                                    @else
                                                        bg-danger
                                                    @endif">
                                                    {{ number_format($productionRecord->produced_quantity, 0) }}
                                                </span>
                                                <input type="number" name="produced_quantity"
                                                       value="{{ $productionRecord->produced_quantity }}" hidden>
                                            </td>

                                            <td class="py-3 text-center align-middle" >
                                                <input type="number" name="scrap_quantity"
                                                       value="{{ $productionRecord->scrap_quantity }}"
                                                       class="form-control form-control-sm text-center border-primary shadow-sm"
                                                       min="0" style="width: 80px;">
                                            </td>
                                            <td class="text-center align-middle">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary rounded-pill px-3"
                                                    onclick="confirmSubmit(this)"
                                                >
                                                    <i class="fas fa-paper-plane mr-2"></i>
                                                    <span class="d-none d-md-inline">GUARDAR</span>
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="fas fa-database fa-2x text-muted mb-3"></i>
                                            <h5 class="text-muted">
                                                @if(request()->has('search'))
                                                    NO SE ENCONTRARON RESULTADOS
                                                @else
                                                    NO HAY REGISTROS
                                                @endif
                                            </h5>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div class="text-muted" >
                                MOSTRANDO {{ $productionRecords->firstItem() ?? 0 }} -
                                {{ $productionRecords->lastItem() ?? 0 }} DE {{ $productionRecords->total() }}
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

@section('css')
    <style>
        .bg-day-shift {
            background-color: #fff3e0;
            color: #e65100;
            border: 1px solid #ffe0b2;
        }

        .bg-night-shift {
            background-color: #f3e5f5;
            color: #4a148c;
            border: 1px solid #e1bee7;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .form-control-sm {
            max-width: 100px;
            margin: 0 auto;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .form-control-sm:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            border-color: #80bdff;
        }

        .card-primary.card-outline {
            border-top: 3px solid #007bff;
        }

        .thead-light {
            background-color: #f8f9fa;
        }

        .rounded-pill {
            display: inline-block;
            min-width: 50px;
        }

        .badge {
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        h1, .input-group input, .input-group button {
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .pagination .page-link {
            font-size: 0.9rem;
        }
    </style>
@stop

@push('js')

    <script>
        function confirmSubmit(button) {
            Swal.fire({
                title: "¿Estás seguro?",
                text: "¿Deseas guardar los cambios?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#dc3545",
                confirmButtonText: "Sí, guardar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    button.closest('form').submit();
                }
            });
        }
    </script>
@endpush
