@extends('adminlte::page')

@section('title', 'Reportes Generales')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Reportes Generales</h1>
        </div>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body p-4">
            @if ($hasPressAccess)
            <div class="report-grid">

                {{-- Reporte MDI --}}
                <a href="{{ route('reports.mdi.export') }}" class="report-tile">
                    <span class="report-tile-icon">
                        {{-- Heroicon: code-bracket-square (outline) --}}
                        <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.25 9.75 16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                        </svg>
                    </span>
                    <span class="report-tile-text">Reporte MDI</span>
                    <span class="report-tile-badge">
                        <i class="fas fa-file-excel"></i> Excel
                    </span>
                </a>

                {{-- Rechazos de IoT: pide el rango de fechas en un modal antes de descargar --}}
                <button type="button" class="report-tile" data-toggle="modal" data-target="#iotRejectionsModal">
                    <span class="report-tile-icon">
                        {{-- Heroicon: exclamation-triangle (outline) --}}
                        <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </span>
                    <span class="report-tile-text">Rechazos de Producción</span>
                    <span class="report-tile-badge">
                        <i class="fas fa-file-excel"></i> Excel
                    </span>
                </button>

            </div>
            @else
                {{-- Los reportes de prensas requieren al menos una línea o estación de prensas --}}
                <div class="text-center text-muted py-5">
                    <i class="fas fa-folder-open mb-3 report-empty-icon" aria-hidden="true"></i>
                    <p class="mb-1 fw-600 text-dark">No tienes reportes disponibles</p>
                    <p class="mb-0 report-empty-text">
                        Estos reportes son de prensas. Pide que te asignen una línea o estación de prensas para verlos.
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if ($hasPressAccess)
    {{-- Modal: rango de fechas del reporte Rechazos de IoT --}}
    <div class="modal fade" id="iotRejectionsModal" tabindex="-1" aria-labelledby="iotRejectionsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('reports.iot-rejections.export') }}" method="GET" id="iotRejectionsForm"
                class="modal-content border-0 shadow rounded-4 overflow-hidden">
                <div class="modal-header bg-white report-modal-divider-bottom">
                    <h5 class="modal-title fw-600 text-dark report-modal-title" id="iotRejectionsModalTitle">
                        <i class="fas fa-exclamation-triangle mr-2 report-modal-title-icon" aria-hidden="true"></i>Rechazos de IoT
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted report-modal-intro">
                        Producción que el recolector leyó del PLC y no se pudo registrar. Elige el rango de fechas a descargar.
                    </p>

                    <div class="row">
                        <div class="col-sm-6 mb-3 mb-sm-0">
                            <label for="iot-start-date" class="field-label">Fecha de inicio *</label>
                            <div class="filter-group report-date-group @error('start_date') is-invalid @enderror">
                                <i class="fas fa-calendar filter-icon" aria-hidden="true"></i>
                                <input type="text" name="start_date" id="iot-start-date" class="filter-input flatpickr-date"
                                    placeholder="Seleccionar fecha"
                                    value="{{ old('start_date', request('start_date', now()->subDays(6)->toDateString())) }}"
                                    max="{{ now()->toDateString() }}" required>
                            </div>
                            @error('start_date')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="iot-end-date" class="field-label">Fecha de fin *</label>
                            <div class="filter-group report-date-group @error('end_date') is-invalid @enderror">
                                <i class="fas fa-calendar-check filter-icon" aria-hidden="true"></i>
                                <input type="text" name="end_date" id="iot-end-date" class="filter-input flatpickr-date"
                                    placeholder="Seleccionar fecha"
                                    value="{{ old('end_date', request('end_date', now()->toDateString())) }}"
                                    max="{{ now()->toDateString() }}" required>
                            </div>
                            @error('end_date')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white report-modal-divider-top">
                    <button type="button" class="btn-action btn-action-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1" aria-hidden="true"></i>Cancelar
                    </button>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-file-excel mr-1" aria-hidden="true"></i>Descargar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.theme-styles')
    @include('partials.theme-datepicker-styles')
    <style>
        body, .card, .btn, .form-control, .content-header h1 {
            font-family: 'Inter', sans-serif !important;
        }

        /* ── Cuadrícula de mosaicos ── */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        /* ── Mosaico (botón) ── */
        .report-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.75rem;
            padding: 2rem 1rem;
            min-height: 200px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #334155;
            position: relative;
        }
        .report-tile:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
            text-decoration: none;
        }
        /* Mosaico como botón (abre un modal en lugar de descargar directo) */
        button.report-tile {
            width: 100%;
            font-family: inherit;
            cursor: pointer;
        }
        button.report-tile:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.45);
        }

        /* ── Modal de rango de fechas ── */
        .report-modal-title { font-size: 1.05rem; }
        .report-modal-title-icon { color: #1d4ed8; }
        .report-modal-intro { font-size: 0.85rem; margin-bottom: 1.25rem; }
        .report-modal-divider-bottom { border-bottom: 1px solid #e9ecef; }
        .report-modal-divider-top { border-top: 1px solid #e9ecef; }
        .report-date-group { width: 100%; height: 38px; }

        /* ── Sin reportes disponibles ── */
        .report-empty-icon { font-size: 2.25rem; color: #cbd5e1; }
        .report-empty-text { font-size: 0.85rem; }
        .report-tile-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 88px;
            height: 88px;
            border-radius: 18px;
            background: #eff6ff;
            color: #1d4ed8;
        }
        .report-tile-icon .hi {
            width: 52px;
            height: 52px;
        }
        .report-tile-text {
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.25;
        }
        .report-tile-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #15803d;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 5px;
            padding: 0.12rem 0.5rem;
        }

        @media (max-width: 1199px) {
            .report-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 767px) {
            .report-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 479px) {
            .report-grid { grid-template-columns: 1fr; }
        }
    </style>
@stop

@section('js')
    @include('partials.theme-datepicker-scripts')
    @if ($hasPressAccess)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = $('#iotRejectionsModal');

            // Si la validación del rango falló, se reabre el modal con sus errores
            @if ($errors->has('start_date') || $errors->has('end_date'))
                modal.modal('show');
            @endif

            // La descarga no recarga la página: se cierra el modal al enviarla
            document.getElementById('iotRejectionsForm').addEventListener('submit', () => {
                setTimeout(() => modal.modal('hide'), 300);
            });
        });
    </script>
    @endif
@stop
