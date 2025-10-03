@extends('adminlte::page')

@section('title', 'Registros de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">{{ __('Registros de Producción') }}</h1>
        <div class="d-flex align-items-center gap-3">
            <div class="search-box">
                <form method="GET" action="{{ route('production-records.index') }}" id="searchForm">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control border-end-0" placeholder="Buscar..."
                            value="{{ request('search') }}">

                        <input type="date" name="date" class="form-control border-start-0 border-end-0"
                            value="{{ request('date') }}"
                            style="width: 150px; border-left: 1px solid #dee2e6 !important;">

                        <button type="submit" class="input-group-text bg-white border-start-0">
                            <i class="fas fa-search text-secondary"></i>
                        </button>

                        @if(request()->has('search') || request()->has('date'))
                        <a href="{{ route('production-records.index') }}" class="input-group-text bg-white border-start-0 text-danger">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </form>
            </div>
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
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">
                                {{ __('Número de Parte') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Orden') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Fecha') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Turno') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                {{ __('Planeada') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                {{ __('Producida') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                {{ __('Scrap') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                {{ __('Estado') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                {{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $record)
                            @php
                                $prod = $record->produced_quantity;
                                $plan = $record->planned_quantity;
                                $scrap = $record->scrap_quantity;
                                $color = $prod < $plan ? 'bg-danger' : ($prod == $plan ? 'bg-success' : 'bg-warning');
                                $formId = 'form-' . $record->production_id;
                            @endphp
                            <tr class="border-light-subtle">
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

                                <!-- SCRAP -->
                                <td class="py-3 text-center">
                                    <form action="{{ route('production-records.update', $record->production_id) }}"
                                        method="POST" class="confirm-save d-inline-block" id="{{ $formId }}"
                                        data-planned="{{ $plan }}" data-produced="{{ $prod }}"
                                        data-status="{{ $record->status_name }}">
                                        @csrf
                                        @method('PUT')

                                        <input type="number" name="scrap_quantity" value="{{ $scrap }}"
                                            class="form-control form-control-sm border-primary text-center shadow-sm scrap-input"
                                            style="width: 80px; display:inline-block;" min="0"
                                            max="{{ $prod }}" data-form-id="{{ $formId }}" required>
                                    </form>
                                </td>

                                <!-- Estado -->
                                <td class="py-3 text-center">
                                    <span
                                        class="badge-status
                                            @if ($record->status_name == 'Completado') bg-success text-success
                                            @elseif($record->status_name == 'En progreso') bg-primary text-primary
                                            @elseif($record->status_name == 'No planeado') bg-warning text-warning
                                            @else bg-secondary text-secondary @endif">
                                        {{ $record->status_name }}
                                    </span>
                                </td>

                                <td class="py-3 text-center">
                                    <button type="submit" form="{{ $formId }}"
                                        class="btn btn-sm btn-outline-primary rounded-3 save-btn"
                                        data-form-id="{{ $formId }}"
                                        {{ $prod == 0 || $scrap == 0 || $record->status_name == 'En progreso' ? 'disabled' : '' }}>
                                        <i class="fas fa-check me-2"></i>
                                        Guardar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">
                                            No se encontraron registros de producción
                                        </span>
                                        @if (request()->has('search') || request()->has('date'))
                                            <a href="{{ route('production-records.index') }}"
                                                class="btn btn-sm btn-link mt-2">
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

        @if ($productionRecords->hasPages())
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
        body,
        .main-header,
        .main-sidebar,
        .content-wrapper,
        .card,
        .btn,
        .form-control,
        .table,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Roboto', sans-serif !important;
        }

        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        .table thead th {
            font-weight: 700 !important;
            font-size: 0.85rem;
        }

        .table tbody td {
            font-size: 0.875rem;
        }

        .search-box .input-group {
            width: 480px; /* Aumentado para acomodar el calendario */
        }

        .search-box .form-control {
            border-radius: 0 !important;
            border-right: none;
            padding: 0.5rem 1rem;
            height: 42px;
            font-size: 0.95rem;
        }

        .search-box .form-control:first-child {
            border-radius: 20px 0 0 20px !important;
        }

        .search-box .input-group-text:last-child {
            border-radius: 0 20px 20px 0 !important;
        }

        .search-box .form-control:focus {
            border-color: #dee2e6 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* Estilos específicos para el input de fecha */
        input[type="date"] {
            position: relative;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            background: transparent;
            bottom: 0;
            color: transparent;
            cursor: pointer;
            height: auto;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            width: auto;
        }

        .badge-status {
            display: inline-block;
            min-width: 60px;
            padding: 0.35em 0.65em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

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

        .fw-500 {
            font-weight: 500 !important;
        }

        .badge {
            min-width: 60px;
            font-weight: 500;
        }

        /* Estilos para el botón de limpiar */
        .search-box .input-group-text.text-danger:hover {
            background-color: #f8f9fa;
            color: #dc3545 !important;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Función para actualizar estado del botón
            function updateButtonState(input, button) {
                const scrapValue = Number(input.value);
                const form = input.closest('form');
                const produced = Number(form.dataset.produced);
                const status = (form.dataset.status || '').trim();

                if (status === 'En progreso') {
                    button.disabled = true;
                    return;
                }

                // Deshabilitar si scrap es 0 o mayor que lo producido o si no hay producción
                button.disabled = (produced === 0) || (scrapValue > produced);
            }

            // Inicializar estado de todos los botones
            document.querySelectorAll('.scrap-input').forEach(input => {
                const formId = input.dataset.formId;
                const button = document.querySelector(`.save-btn[data-form-id="${formId}"]`);
                if (button) {
                    updateButtonState(input, button);
                }
            });

            // Event listeners para inputs de scrap
            document.querySelectorAll('.scrap-input').forEach(input => {
                input.addEventListener('input', function() {
                    const formId = this.dataset.formId;
                    const button = document.querySelector(`.save-btn[data-form-id="${formId}"]`);
                    if (button) {
                        updateButtonState(this, button);
                    }
                });
            });

            // Confirmación de envío de formularios
            document.querySelectorAll('form.confirm-save').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const planned = Number(form.dataset.planned);
                    const produced = Number(form.dataset.produced);
                    const scrapInput = form.querySelector('input[name="scrap_quantity"]');
                    const scrap = Number(scrapInput.value);
                    const status = (form.dataset.status || '').trim();

                    // Nueva validación cliente: si status == 'En progreso' bloquear y mostrar alerta
                    if (status === 'En progreso') {
                        Swal.fire({
                            title: 'No permitido',
                            text: 'No puede guardarse porque el registro está "En progreso".',
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }

                    // Validación adicional
                    if (scrap > produced) {
                        Swal.fire({
                            title: 'Error',
                            text: 'El scrap no puede ser mayor a la cantidad producida',
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }

                    // Si son iguales, enviar directo
                    if (produced === planned) {
                        form.submit();
                        return;
                    }

                    let title = produced < planned ? 'Cantidad menor a la planeada' :
                        'Cantidad mayor a la planeada';
                    let icon = produced < planned ? 'warning' : 'question';

                    Swal.fire({
                        title,
                        text: '¿Deseas continuar?',
                        icon,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, confirmar',
                        cancelButtonText: 'Cancelar'
                    }).then(result => {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });

            // Opcional: JavaScript para mejorar la experiencia del calendario
            const dateInput = document.querySelector('input[name="date"]');
            if (dateInput) {
                // Establecer fecha máxima como hoy
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('max', today);
            }
        });
    </script>
@stop
