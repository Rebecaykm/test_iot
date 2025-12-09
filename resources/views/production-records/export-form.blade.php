@extends('adminlte::page')

@section('title', 'Generar Reporte FOR-MA-75')

@section('content_header')
    <h1 class="m-0">{{ __('Reporte de Producción') }}</h1>
@stop

@section('content')
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body">
            <form action="{{ route('production.export-pdf-filtered') }}" method="POST" target="_blank" id="exportForm">
                @csrf

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label fw-bold text-secondary">{{ __('Fecha de Inicio') }} *</label>
                        <input type="date" name="start_date" id="start_date"
                               class="form-control border-light-subtle @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date', now()->startOfWeek()->format('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-bold text-secondary">{{ __('Fecha de Fin') }} *</label>
                        <input type="date" name="end_date" id="end_date"
                               class="form-control border-light-subtle @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date', now()->endOfWeek()->format('Y-m-d')) }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="lines" class="form-label fw-bold text-secondary">{{ __('Líneas') }}</label>
                        <select name="lines[]" id="lines" class="form-control select2 border-light-subtle @error('lines') is-invalid @enderror" multiple>
                            @foreach($lines as $line)
                                <option value="{{ $line->id }}" {{ in_array($line->id, old('lines', [])) ? 'selected' : '' }}>
                                    {{ $line->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('lines')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="work_centers" class="form-label fw-bold text-secondary">{{ __('Estaciones') }}</label>
                        <select name="work_centers[]" id="work_centers" class="form-control select2 border-light-subtle @error('work_centers') is-invalid @enderror" multiple>
                            @foreach($workCenters as $workCenter)
                                <option value="{{ $workCenter->id }}" {{ in_array($workCenter->id, old('work_centers', [])) ? 'selected' : '' }}>
                                    {{ $workCenter->number }} - {{ $workCenter->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('work_centers')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="shifts" class="form-label fw-bold text-secondary">{{ __('Turnos') }}</label>
                        <select name="shifts[]" id="shifts" class="form-control select2 border-light-subtle @error('shifts') is-invalid @enderror" multiple>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ in_array($shift->id, old('shifts', [])) ? 'selected' : '' }}>
                                    {{ $shift->name }} ({{ $shift->abbreviation }})
                                </option>
                            @endforeach
                        </select>
                        @error('shifts')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('production-records.index') }}" class="btn btn-outline-secondary rounded-3 btn-with-icon">
                        <span class="btn-icon"><i class="fas fa-arrow-left"></i></span>
                        <span class="btn-text">{{ __('Regresar') }}</span>
                    </a>
                    <button type="submit" class="btn btn-danger rounded-3">
                        {{ __('Descargar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body,
        .card,
        .btn,
        .form-control,
        .form-select,
        .form-label {
            font-family: 'Roboto', sans-serif !important;
        }

        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        .form-control {
            border-radius: 8px !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.95rem !important;
            border: 1px solid #e0e0e0 !important;
            transition: all 0.2s ease;
            background-color: #fff;
        }

        .form-control:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.18rem rgba(13, 110, 253, 0.12) !important;
            outline: none !important;
        }

        .form-label {
            font-size: 0.9rem !important;
            margin-bottom: 0.5rem !important;
            font-weight: 600 !important;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 38px !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            font-family: 'Roboto', sans-serif !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            font-size: 0.85rem !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white !important;
            margin-right: 4px !important;
        }

        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1.25rem !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            display: inline-flex;
            align-items: center;
            transition: all 0.14s ease;
        }

        /* separar icono del texto de forma consistente */
        .btn .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.6rem;
            font-size: 0.95rem;
        }

        .btn-with-icon .btn-text {
            display: inline-block;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.18);
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .swal2-popup {
            font-family: 'Roboto', sans-serif !important;
            border-radius: 12px !important;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#lines, #work_centers, #shifts').select2({
                placeholder: 'Seleccione una o más opciones',
                allowClear: true,
                width: '100%'
            });

            // Configurar fechas
            const today = new Date().toISOString().split('T')[0];
            $('#start_date').attr('max', today);
            $('#end_date').attr('max', today);

            // Validar formulario antes de enviar
            $('#exportForm').submit(function(e) {
                const lines = $('#lines').val();
                const workCenters = $('#work_centers').val();
                const shifts = $('#shifts').val();
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();

                // Validar fechas requeridas
                if (!startDate || !endDate) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos Requeridos',
                        text: 'Por favor, complete las fechas de inicio y fin.',
                        confirmButtonColor: '#007bff',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'rounded-3'
                        }
                    });
                    return false;
                }

                // Validar que end_date no sea menor que start_date
                if (startDate && endDate && endDate < startDate) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Fecha Incorrecta',
                        text: 'La fecha de fin no puede ser anterior a la fecha de inicio.',
                        confirmButtonColor: '#007bff',
                        confirmButtonText: 'Corregir',
                        customClass: {
                            popup: 'rounded-3'
                        }
                    }).then(() => {
                        $('#end_date').val(startDate);
                        $('#end_date').focus();
                    });
                    return false;
                }

                // Validar que se seleccione al menos una línea o estación o turno
                if ((!lines || lines.length === 0) && (!workCenters || workCenters.length === 0) && (!shifts || shifts.length === 0)) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selección Requerida',
                        text: 'Debe seleccionar al menos una línea, una estación o un turno.',
                        confirmButtonColor: '#007bff',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'rounded-3'
                        }
                    });
                    return false;
                }

                // Si todo está bien, permitir el envío del formulario
                return true;
            });

            // Foco en el primer campo
            setTimeout(() => {
                $('#start_date').focus();
            }, 300);
        });
    </script>
@stop
