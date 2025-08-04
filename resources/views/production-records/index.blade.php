@extends('adminlte::page')

@section('title', 'Registros de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">{{ __('Registros de Producción') }}</h1>
        <div class="search-box">
            <form method="GET" action="{{ route('production-records.index') }}">
                <div class="input-group">
                    <input type="text" name="search" class="form-control border-end-0" placeholder="Buscar..."
                        value="{{ request('search') }}">
                    <button type="submit" class="input-group-text bg-white border-start-0">
                        <i class="fas fa-search text-secondary"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Estación') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Número de Parte') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Orden') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Fecha') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Turno') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Planeada') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Producida') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Scrap') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Estado') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $record)
                        @php
                        $prod = $record->produced_quantity;
                        $plan = $record->planned_quantity;
                        $color = $prod < $plan ? 'bg-danger'
                            : ($prod==$plan ? 'bg-success' : 'bg-warning' );
                            @endphp
                            <tr class="border-light-subtle">
                            <form
                                action="{{ route('production-records.update', $record->production_id) }}"
                                method="POST"
                                class="confirm-save"
                                data-planned="{{ $plan }}"
                                data-produced="{{ $prod }}">
                                @csrf
                                @method('PUT')

                                <!-- Estación/Centro de Trabajo -->
                                <td class="py-3 text-start">
                                    <div class="fw-500">{{ $record->work_number }}</div>
                                    <div class="text-muted small">{{ $record->work_name }}</div>
                                </td>

                                <!-- Número de Parte -->
                                <td class="py-3 text-start">
                                    <div class="fw-500">{{ $record->part_number }}</div>
                                    <div class="text-muted small">{{ $record->part_name }}</div>
                                </td>

                                <!-- Número de Orden -->
                                <td class="py-3 text-start">
                                    {{ $record->shop_order_number }}
                                </td>

                                <!-- Fecha -->
                                <td class="py-3 text-start">
                                    {{ \Carbon\Carbon::parse($record->planned_date)->format('d-m-Y') }}
                                </td>

                                <!-- Turno -->
                                <td class="py-3 text-start">
                                    <span class="badge-status bg-primary text-primary">
                                        {{ $record->shift_name }}
                                    </span>
                                </td>

                                <!-- Cantidad Planeada -->
                                <td class="py-3 text-center">
                                    <span class="badge-status bg-primary">
                                        {{ number_format($plan) }}
                                    </span>
                                </td>

                                <!-- Cantidad Producida -->
                                <td class="py-3 text-center">
                                    <span class="badge-status {{ $color }}">
                                        {{ number_format($prod) }}
                                    </span>
                                </td>

                                <!-- Scrap -->
                                <td class="py-3 text-center">
                                    <input
                                        type="number"
                                        name="scrap_quantity"
                                        value="{{ $record->scrap_quantity }}"
                                        class="form-control form-control-sm border-primary text-center shadow-sm d-inline-block"
                                        style="width: 80px;"
                                        min="0"
                                        required
                                        oninput="this.form.querySelector('button[type=submit]').disabled = this.value == 0">
                                </td>

                                <!-- Estado -->
                                <td class="py-3 text-center">
                                    <span class="badge-status
                                            @if($record->status_name == 'Completado') bg-success text-success
                                            @elseif($record->status_name == 'En progreso') bg-primary text-primary
                                            @elseif($record->status_name == 'No planeado') bg-warning text-warning
                                            @else bg-secondary text-secondary
                                            @endif">
                                        {{ $record->status_name }}
                                    </span>
                                </td>

                                <!-- Botón Guardar -->
                                <td class="py-3 text-center">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-primary rounded-3"
                                        {{ $prod == 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-save me-2"></i>
                                        Guardar
                                    </button>
                                </td>

                            </form>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">
                                            No se encontraron registros de producción
                                        </span>
                                        @if(request()->has('search'))
                                            <a href="{{ route('production-records.index') }}" class="btn btn-sm btn-link mt-2">
                                                Limpiar búsqueda
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($productionRecords->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando {{ $productionRecords->firstItem() }} a {{ $productionRecords->lastItem() }} de
                    {{ $productionRecords->total() }} registros
                </div>
                {{ $productionRecords->links() }}
            </div>
        </div>
        @endif
    </div>
@stop

@section('css')
    <!-- Fuente Google Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Aplicar fuente a todo el sistema */
        body, .main-header, .main-sidebar, .content-wrapper,
        .card, .btn, .form-control, .table, h1, h2, h3, h4, h5, h6 {
            font-family: 'Roboto', sans-serif !important;
        }

        /* Estilos adicionales para la tabla */
        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        /* Mejoras en jerarquía tipográfica */
        .table thead th {
            font-weight: 700 !important;
            font-size: 0.85rem;
        }

        .table tbody td {
            font-size: 0.875rem;
        }

        /* Buscador sin contorno azul */
        .search-box .input-group {
            width: 380px;
        }

        .search-box .form-control {
            border-radius: 20px 0 0 20px !important;
            border-right: none;
            padding: 0.5rem 1.5rem;
            height: 42px;
            font-size: 0.95rem;
        }

        .search-box .input-group-text {
            border-radius: 0 20px 20px 0 !important;
            border-left: none;
            background-color: white;
            padding: 0 1.25rem;
            font-size: 1rem;
        }

        /* Quitar contorno azul al enfocar */
        .search-box .form-control:focus {
            border-color: #dee2e6 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* Badges simétricos */
        .badge-status {
            display: inline-block;
            min-width: 60px;
            padding: 0.35em 0.65em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Botones de acción */
        .btn {
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        /* Texto con peso medio */
        .fw-500 {
            font-weight: 500 !important;
        }

        /* Badges de cantidad */
        .badge {
            min-width: 60px;
            font-weight: 500;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form.confirm-save').forEach(form => {
                form.addEventListener('submit', e => {
                    e.preventDefault();

                    const planned = Number(form.dataset.planned);
                    const produced = Number(form.dataset.produced);

                    // Enviar directamente si igual
                    if (produced === planned) {
                        form.submit();
                        return;
                    }

                    let title, text, icon;
                    if (produced < planned) {
                        title = 'Cantidad menor a la planeada';
                        text = `¿Deseas continuar?`;
                        icon = 'warning';
                    } else {
                        title = 'Cantidad mayor a la planeada';
                        text = `¿Deseas continuar?`;
                        icon = 'question';
                    }

                    Swal.fire({
                        title,
                        text,
                        icon,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, confirmar',
                        cancelButtonText: 'Cancelar'
                    }).then(result => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Mantener deshabilitado botón cuando scrap = 0
            document.querySelectorAll('input[name="scrap_quantity"]').forEach(input => {
                const btn = input.form.querySelector('button[type=submit]');
                input.addEventListener('input', () => btn.disabled = input.value == 0);
                btn.disabled = input.value == 0;
            });
        });
    </script>
@stop
