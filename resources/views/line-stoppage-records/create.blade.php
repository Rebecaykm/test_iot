@extends('adminlte::page')

@section('title', 'Registro de Paro de Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Registro de Paro de Línea</h1>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-ban mr-2" style="color: #94a3b8;"></i>Información del Registro
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('line-stoppage-records.store') }}" method="POST" id="stoppageForm">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="line_stoppage_id" class="field-label">Paro de Línea *</label>
                        <select name="line_stoppage_id" id="line_stoppage_id"
                            class="field-input select2 @error('line_stoppage_id') is-invalid @enderror"
                            style="width: 100%;" required>
                            <option value="">Seleccione un paro de línea</option>
                            @foreach ($lineStoppages as $stoppage)
                                <option value="{{ $stoppage->id }}"
                                    {{ old('line_stoppage_id') == $stoppage->id ? 'selected' : '' }}>
                                    {{ $stoppage->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('line_stoppage_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="work_center_id" class="field-label">Centro de Trabajo *</label>
                        <select name="work_center_id" id="work_center_id"
                            class="field-input select2 @error('work_center_id') is-invalid @enderror"
                            style="width: 100%;" required>
                            <option value="">Seleccione un centro de trabajo</option>
                            @foreach ($workCenters as $workCenter)
                                <option value="{{ $workCenter->id }}"
                                    {{ old('work_center_id') == $workCenter->id ? 'selected' : '' }}>
                                    {{ $workCenter->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('work_center_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="start_time" class="field-label">Hora de Inicio *</label>
                        <input type="datetime-local" name="start_time" id="start_time"
                            class="field-input @error('start_time') is-invalid @enderror"
                            value="{{ old('start_time') }}" required>
                        @error('start_time')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="end_time" class="field-label">Hora de Fin *</label>
                        <input type="datetime-local" name="end_time" id="end_time"
                            class="field-input @error('end_time') is-invalid @enderror"
                            value="{{ old('end_time') }}" required>
                        @error('end_time')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="minutes_stoppage" class="field-label">Minutos de Paro</label>
                        <input type="number" name="minutes_stoppage" id="minutes_stoppage"
                            class="field-input" style="background: #f1f5f9; color: #64748b;"
                            value="{{ old('minutes_stoppage') }}" placeholder="Calculado automáticamente"
                            min="1" readonly>
                        @error('minutes_stoppage')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ $source == 'production-records.index' ? route('production-records.index') : route('line-stoppage-records.index') }}"
                        class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Guardar Registro</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.theme-scripts')

    <script>
        $(document).ready(function () {
            $('#line_stoppage_id, #work_center_id').select2({
                placeholder: 'Seleccione una opción',
                allowClear: false,
                width: '100%'
            });

            @if ($errors->has('line_stoppage_id'))
                $('#line_stoppage_id').next('.select2-container').find('.select2-selection').addClass('is-invalid');
            @endif
            @if ($errors->has('work_center_id'))
                $('#work_center_id').next('.select2-container').find('.select2-selection').addClass('is-invalid');
            @endif

            function calculateMinutes() {
                const start = document.getElementById('start_time').value;
                const end = document.getElementById('end_time').value;
                if (!start || !end) { document.getElementById('minutes_stoppage').value = ''; return; }
                const diff = (new Date(end) - new Date(start)) / 60000;
                if (diff <= 0) {
                    document.getElementById('minutes_stoppage').value = '';
                    Swal.fire({ icon: 'error', title: 'Error', text: 'La hora de fin debe ser posterior a la de inicio.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    return;
                }
                document.getElementById('minutes_stoppage').value = Math.floor(diff);
            }

            document.getElementById('start_time').addEventListener('change', calculateMinutes);
            document.getElementById('end_time').addEventListener('change', calculateMinutes);
        });
    </script>
@stop
