@extends('adminlte::page')

@section('title', 'Crear Registro de Paro de Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Crear Registro de Paro de Línea</h1>
        <a href="{{ route('line-stoppage-records.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm">

        <form action="{{ route('line-stoppage-records.store') }}" method="POST">
            @csrf

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="line_stoppage_id" class="fw-medium text-dark">Paro de Línea <span
                                    class="text-danger">*</span></label>
                            <select class="form-control @error('line_stoppage_id') is-invalid @enderror"
                                id="line_stoppage_id" name="line_stoppage_id" required>
                                <option value="">Seleccione un paro de línea...</option>
                                @foreach ($lineStoppages as $stoppage)
                                    <option value="{{ $stoppage->id }}"
                                        {{ old('line_stoppage_id') == $stoppage->id ? 'selected' : '' }}>
                                        {{ $stoppage->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('line_stoppage_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="work_center_id" class="fw-medium text-dark">Centro de Trabajo <span
                                    class="text-danger">*</span></label>
                            <select class="form-control @error('work_center_id') is-invalid @enderror"
                                id="work_center_id" name="work_center_id" required>
                                <option value="">Seleccione un centro de trabajo...</option>
                                @foreach ($workCenters as $workCenter)
                                    <option value="{{ $workCenter->id }}"
                                        {{ old('work_center_id') == $workCenter->id ? 'selected' : '' }}>
                                        {{ $workCenter->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('work_center_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_time" class="fw-medium text-dark">Hora de Inicio <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local"
                                class="form-control @error('start_time') is-invalid @enderror" id="start_time"
                                name="start_time" value="{{ old('start_time') }}" required>
                            @error('start_time')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_time" class="fw-medium text-dark">Hora de Fin <span
                                    class="text-danger">*</span></label>
                            <input type="datetime-local"
                                class="form-control @error('end_time') is-invalid @enderror" id="end_time"
                                name="end_time" value="{{ old('end_time') }}" required>
                            @error('end_time')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="minutes_stoppage" class="fw-medium text-dark">Minutos de Paro <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                    class="form-control @error('minutes_stoppage') is-invalid @enderror"
                                    id="minutes_stoppage" name="minutes_stoppage"
                                    value="{{ old('minutes_stoppage') }}"
                                    placeholder="Se calculará automáticamente" min="1" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">minutos</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Los minutos se calculan automáticamente al ingresar las fechas de inicio y fin.
                            </small>
                            @error('minutes_stoppage')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- Campo oculto para validación en caso de que el cálculo falle -->
                    <input type="hidden" id="calculated_minutes" name="calculated_minutes" value="">
                </div>
            </div>

            <div class="card-footer bg-white d-flex justify-content-end py-3">
                <button type="reset" class="btn btn-outline-secondary mr-2" id="resetButton">
                    <i class="fas fa-undo mr-1"></i> Limpiar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Guardar Registro
                </button>
            </div>
        </form>
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

        /* Card mejorada */
        .card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(to bottom, #f9fafb 0%, #f3f4f6 100%);
        }

        /* Formularios */
        .form-control {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 0.5rem 0.75rem;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* Botones */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .btn-outline-secondary {
            border: 1px solid #d1d5db;
            color: #6b7280;
        }

        .btn-outline-secondary:hover {
            background-color: #f9fafb;
            border-color: #9ca3af;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            const minutesStoppageInput = document.getElementById('minutes_stoppage');
            const calculatedMinutesInput = document.getElementById('calculated_minutes');
            const resetButton = document.getElementById('resetButton');

            function calculateMinutes() {
                const startTime = startTimeInput.value;
                const endTime = endTimeInput.value;

                if (startTime && endTime) {
                    const start = new Date(startTime);
                    const end = new Date(endTime);

                    // Validar que la fecha de fin sea mayor que la de inicio
                    if (end <= start) {
                        minutesStoppageInput.value = '';
                        calculatedMinutesInput.value = '';
                        showAlert('error', 'La hora de fin debe ser posterior a la hora de inicio.');
                        return;
                    }

                    // Calcular diferencia en minutos
                    const diffMs = end - start;
                    const diffMinutes = Math.floor(diffMs / (1000 * 60));

                    if (diffMinutes < 1) {
                        minutesStoppageInput.value = '';
                        calculatedMinutesInput.value = '';
                        showAlert('warning', 'La diferencia debe ser de al menos 1 minuto.');
                        return;
                    }

                    // Actualizar los campos
                    minutesStoppageInput.value = diffMinutes;
                    calculatedMinutesInput.value = diffMinutes;

                    // Mostrar confirmación visual
                    showAlert('success', `Paro calculado: ${diffMinutes} minutos`);
                } else {
                    minutesStoppageInput.value = '';
                    calculatedMinutesInput.value = '';
                }
            }

            function showAlert(type, message) {
                // Remover alertas anteriores
                const existingAlert = document.querySelector('.calculation-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }

                const alertClass = type === 'success' ? 'alert-success' :
                    type === 'error' ? 'alert-danger' : 'alert-warning';

                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${alertClass} calculation-alert mt-2`;
                alertDiv.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle'} mr-2"></i>
                ${message}
            `;

                // Insertar después del campo de minutos
                const minutesGroup = minutesStoppageInput.closest('.form-group');
                minutesGroup.appendChild(alertDiv);

                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }

            // Event listeners para calcular automáticamente
            startTimeInput.addEventListener('change', calculateMinutes);
            endTimeInput.addEventListener('change', calculateMinutes);

            // También calcular cuando se escribe manualmente (por si acaso)
            startTimeInput.addEventListener('input', calculateMinutes);
            endTimeInput.addEventListener('input', calculateMinutes);

            // Resetear el formulario
            resetButton.addEventListener('click', function() {
                setTimeout(() => {
                    minutesStoppageInput.value = '';
                    calculatedMinutesInput.value = '';
                    const existingAlert = document.querySelector('.calculation-alert');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                }, 100);
            });

            // Calcular automáticamente si hay valores en los campos al cargar la página (para errores de validación)
            if (startTimeInput.value && endTimeInput.value) {
                calculateMinutes();
            }
        });
    </script>
@stop
