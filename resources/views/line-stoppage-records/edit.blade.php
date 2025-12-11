@extends('adminlte::page')

@section('title', 'Editar Registro de Paro de Línea')

@section('content_header')
    <h1>{{ __('Editar Registro de Paro de Línea') }}</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body">
            <form action="{{ route('line-stoppage-records.update', $lineStoppageRecord->id) }}" method="POST" id="stoppageForm">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="line_stoppage_id" class="form-label fw-bold text-secondary">{{ __('Paro de Línea') }} *</label>
                        <select
                            class="form-control select2 border-light-subtle @error('line_stoppage_id') is-invalid @enderror"
                            id="line_stoppage_id" name="line_stoppage_id" required>
                            <option value="">Seleccione un paro de línea...</option>
                            @foreach ($lineStoppages as $stoppage)
                                <option value="{{ $stoppage->id }}"
                                    {{ old('line_stoppage_id', $lineStoppageRecord->line_stoppage_id) == $stoppage->id ? 'selected' : '' }}>
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

                    <div class="col-md-6">
                        <label for="work_center_id" class="form-label fw-bold text-secondary">{{ __('Centro de Trabajo') }} *</label>
                        <select
                            class="form-control select2 border-light-subtle @error('work_center_id') is-invalid @enderror"
                            id="work_center_id" name="work_center_id" required>
                            <option value="">Seleccione un centro de trabajo...</option>
                            @foreach ($workCenters as $workCenter)
                                <option value="{{ $workCenter->id }}"
                                    {{ old('work_center_id', $lineStoppageRecord->work_center_id) == $workCenter->id ? 'selected' : '' }}>
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

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="start_time" class="form-label fw-bold text-secondary">{{ __('Hora de Inicio') }} *</label>
                        <input type="datetime-local"
                            class="form-control border-light-subtle @error('start_time') is-invalid @enderror"
                            id="start_time" name="start_time"
                            value="{{ old('start_time', $lineStoppageRecord->start_time ? \Carbon\Carbon::parse($lineStoppageRecord->start_time)->format('Y-m-d\TH:i') : '') }}" required>
                        @error('start_time')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="end_time" class="form-label fw-bold text-secondary">{{ __('Hora de Fin') }} *</label>
                        <input type="datetime-local"
                            class="form-control border-light-subtle @error('end_time') is-invalid @enderror" id="end_time"
                            name="end_time"
                            value="{{ old('end_time', $lineStoppageRecord->end_time ? \Carbon\Carbon::parse($lineStoppageRecord->end_time)->format('Y-m-d\TH:i') : '') }}" required>
                        @error('end_time')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="minutes_stoppage" class="form-label fw-bold text-secondary">{{ __('Minutos de Paro') }} *</label>
                        <input type="number"
                            class="form-control border-light-subtle @error('minutes_stoppage') is-invalid @enderror"
                            id="minutes_stoppage" name="minutes_stoppage"
                            value="{{ old('minutes_stoppage', $lineStoppageRecord->minutes_stoppage) }}"
                            placeholder="Se calculará automáticamente" min="1" readonly>
                        <small class="text-muted">Se calculará automáticamente</small>
                        @error('minutes_stoppage')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('line-stoppage-records.index') }}"
                        class="btn btn-outline-secondary rounded-3">
                        <i class="fas fa-times"></i>
                        <span class="ms-2">{{ __('Cancelar') }}</span>
                    </a>
                    <button type="submit" class="btn btn-primary rounded-3">
                        <i class="fas fa-save"></i>
                        <span class="ms-2">{{ __('Actualizar Registro') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <!-- Fuente Google Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        body,
        .card,
        .btn,
        .form-control,
        .table,
        h1 {
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
        }

        .form-control:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        .form-label {
            font-size: 0.9rem !important;
            margin-bottom: 0.5rem !important;
            font-weight: 600 !important;
        }

        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            font-size: 0.95rem !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }

        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            transition: all 0.2s ease;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .rounded-3 {
            border-radius: 12px !important;
        }

        .invalid-feedback {
            font-size: 0.85rem !important;
        }

        /* Alertas */
        .alert {
            border-radius: 8px;
        }

        .btn-close {
            background-size: 0.75rem;
            padding: 0.5rem;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('#line_stoppage_id, #work_center_id').select2({
                placeholder: 'Seleccione una opción',
                allowClear: false,
                width: '100%'
            });

            // Calcular minutos
            function calculateMinutes() {
                const startTime = $('#start_time').val();
                const endTime = $('#end_time').val();

                if (startTime && endTime) {
                    const start = new Date(startTime);
                    const end = new Date(endTime);

                    if (end <= start) {
                        $('#minutes_stoppage').val('');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'La hora de fin debe ser posterior a la hora de inicio.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        return;
                    }

                    const diffMs = end - start;
                    const diffMinutes = Math.floor(diffMs / (1000 * 60));

                    if (diffMinutes < 1) {
                        $('#minutes_stoppage').val('');
                        return;
                    }

                    $('#minutes_stoppage').val(diffMinutes);
                } else {
                    $('#minutes_stoppage').val('');
                }
            }

            // Calcular minutos cuando cambien las fechas
            $('#start_time, #end_time').on('change', calculateMinutes);

            // Calcular al cargar si hay valores
            if ($('#start_time').val() && $('#end_time').val()) {
                calculateMinutes();
            }

            // Cerrar alertas automáticamente después de 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
@stop
