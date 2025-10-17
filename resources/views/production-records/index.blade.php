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
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Tabla integrada sin bordes -->
            <div class="table-container-integrated">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Estación</th>
                            <th>N° de Parte</th>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th class="text-end">Planeada</th>
                            <th class="text-end">Producida</th>
                            <th class="text-end">Scrap</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $record)
                            @php
                                $prod = $record->produced_quantity;
                                $plan = $record->planned_quantity;
                                $scrap = $record->scrap_quantity;
                                $color = $prod < $plan ? 'danger' : ($prod == $plan ? 'success' : 'warning');
                                $formId = 'form-' . $record->production_id;
                            @endphp
                            <tr>
                                <!-- Estación/Centro de Trabajo -->
                                <td>
                                    <div class="fw-medium text-dark">{{ $record->work_number }}</div>
                                    <small class="text-muted">{{ $record->work_name }}</small>
                                </td>

                                <!-- Número de Parte -->
                                <td>
                                    <div class="fw-medium text-dark">{{ $record->part_number }}</div>
                                    <small class="text-muted">{{ $record->part_name }}</small>
                                </td>

                                <!-- Número de Orden -->
                                <td>
                                    {{ $record->shop_order_number }}
                                </td>

                                <!-- Fecha -->
                                <td>
                                    {{ \Carbon\Carbon::parse($record->planned_date)->format('d/m/Y') }}
                                </td>

                                <!-- Turno -->
                                <td>
                                    <span class="badge bg-primary">{{ $record->shift_name }}</span>
                                </td>

                                <!-- Cantidad Planeada -->
                                <td class="text-end">
                                    <span class="badge bg-secondary">{{ number_format($plan) }}</span>
                                </td>

                                <!-- Cantidad Producida -->
                                <td class="text-end">
                                    <span class="badge bg-{{ $color }}">{{ number_format($prod) }}</span>
                                </td>

                                <!-- SCRAP -->
                                <td class="text-end">
                                    <form action="{{ route('production-records.update', $record->production_id) }}"
                                        method="POST" class="confirm-save d-inline-block" id="{{ $formId }}"
                                        data-planned="{{ $plan }}" data-produced="{{ $prod }}"
                                        data-status="{{ $record->status_name }}">
                                        @csrf
                                        @method('PUT')

                                        <input type="number" name="scrap_quantity" value="{{ $scrap }}"
                                            class="form-control form-control-sm border-0 text-center shadow-sm scrap-input"
                                            style="width: 80px; display:inline-block;" min="0"
                                            max="{{ $prod }}" data-form-id="{{ $formId }}" required>
                                    </form>
                                </td>

                                <!-- Estado -->
                                <td>
                                    <span class="badge
                                        @if ($record->status_name == 'Completado') bg-success
                                        @elseif($record->status_name == 'En progreso') bg-primary
                                        @elseif($record->status_name == 'No planeado') bg-warning
                                        @else bg-secondary @endif">
                                        {{ $record->status_name }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <button type="submit" form="{{ $formId }}"
                                        class="btn btn-sm btn-outline-primary save-btn"
                                        data-form-id="{{ $formId }}"
                                        {{ $prod == 0 || $scrap == 0 || $record->status_name == 'En progreso' ? 'disabled' : '' }}>
                                        <i class="fas fa-check me-1"></i>
                                        Guardar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No se encontraron registros
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if ($productionRecords->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
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
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Fuente moderna */
        body,
        .card,
        .btn,
        .form-control,
        .table,
        h1,
        .main-header,
        .main-sidebar,
        .content-wrapper {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        }

        /* Card mejorada sin bordes visibles */
        .card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* Tabla completamente integrada sin bordes */
        .table-container-integrated {
            max-height: 650px;
            overflow-y: auto;
            overflow-x: hidden;
            border-radius: 12px;
            position: relative;
        }

        .table-container-integrated:hover::-webkit-scrollbar {
            height: 8px;
        }

        /* Encabezados sticky sin bordes */
        .table thead th {
            position: sticky;
            top: 0;
            background: linear-gradient(to bottom, #f9fafb 0%, #f3f4f6 100%);
            font-weight: 600;
            font-size: 0.813rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0.75rem;
            z-index: 10;
        }

        /* Filas de la tabla sin bordes */
        .table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.15s ease;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
        }

        .table tbody td {
            padding: 0.875rem 0.75rem;
            vertical-align: middle;
            border: none;
        }

        /* Eliminar bordes de la tabla */
        .table {
            border: none;
            margin-bottom: 0;
        }

        .table th,
        .table td {
            border: none;
        }

        /* Badges mejorados */
        .badge {
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            font-size: 0.813rem;
            border-radius: 6px;
            min-width: 60px;
            display: inline-block;
        }

        /* Botón */
        .btn-outline-primary {
            border: 1px solid #3b82f6;
            color: #3b82f6;
            border-radius: 8px;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.15s ease;
        }

        .btn-outline-primary:hover {
            background: #3b82f6;
            color: white;
        }

        /* Scrollbar personalizada */
        .table-container-integrated::-webkit-scrollbar {
            width: 8px;
            height: 0px;
        }

        .table-container-integrated::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 0 12px 12px 0;
        }

        .table-container-integrated::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .table-container-integrated::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Ajustes para el input de scrap */
        .scrap-input {
            border: 1px solid #e5e7eb !important;
            border-radius: 6px;
            padding: 0.25rem 0.5rem;
            font-size: 0.813rem;
            text-align: center;
        }

        .scrap-input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
        }

        /* Estilos para el filtro original */
        .search-box .input-group {
            width: 480px;
        }

        .search-box .form-control {
            border-radius: 0 !important;
            border-right: none;
            padding: 0.5rem 1rem;
            height: 42px;
            font-size: 0.95rem;
            border: 1px solid #e5e7eb;
        }

        .search-box .form-control:first-child {
            border-radius: 20px 0 0 20px !important;
        }

        .search-box .input-group-text:last-child {
            border-radius: 0 20px 20px 0 !important;
        }

        .search-box .form-control:focus {
            border-color: #e5e7eb !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .search-box .input-group-text {
            border: 1px solid #e5e7eb;
            background: white;
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

        /* Estilos para el botón de limpiar */
        .search-box .input-group-text.text-danger:hover {
            background-color: #f8f9fa;
            color: #dc3545 !important;
        }

        /* Estado vacío centrado */
        .table tbody tr td.text-center {
            border: none !important;
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

            // Confirmación de envío de formularios y bloqueo del botón
            document.querySelectorAll('form.confirm-save').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const planned = Number(form.dataset.planned);
                    const produced = Number(form.dataset.produced);
                    const scrapInput = form.querySelector('input[name="scrap_quantity"]');
                    const scrap = Number(scrapInput.value);
                    const status = (form.dataset.status || '').trim();
                    const button = document.querySelector(`.save-btn[data-form-id="${form.id}"]`);

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

                    // Bloquear el botón
                    if (button) {
                        button.disabled = true;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
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
                        if (result.isConfirmed) {
                            form.submit();
                        } else {
                            // Rehabilitar el botón si se cancela
                            if (button) {
                                button.disabled = false;
                                button.innerHTML = '<i class="fas fa-check me-1"></i> Guardar';
                            }
                        }
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
