@extends('adminlte::page')

@section('title', 'Reporte de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Reporte de Producción</h1>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-file-pdf mr-2" style="color: #94a3b8;"></i>Filtros del Reporte
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('production.export-pdf-filtered') }}" method="POST" target="_blank" id="exportForm">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="start_date" class="field-label">Fecha de Inicio *</label>
                        <div class="filter-group @error('start_date') is-invalid @enderror" style="width: 100%; height: 38px;">
                            <i class="fas fa-calendar filter-icon" aria-hidden="true"></i>
                            <input type="text" name="start_date" id="start_date" class="filter-input flatpickr-date"
                                placeholder="Seleccionar fecha" value="{{ old('start_date') }}"
                                max="{{ date('Y-m-d') }}" required>
                        </div>
                        @error('start_date')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="field-label">Fecha de Fin *</label>
                        <div class="filter-group @error('end_date') is-invalid @enderror" style="width: 100%; height: 38px;">
                            <i class="fas fa-calendar-check filter-icon" aria-hidden="true"></i>
                            <input type="text" name="end_date" id="end_date" class="filter-input flatpickr-date"
                                placeholder="Seleccionar fecha" value="{{ old('end_date') }}"
                                max="{{ date('Y-m-d') }}" required>
                        </div>
                        @error('end_date')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <x-multi-select
                            name="lines"
                            label="Líneas"
                            placeholder="Todas las líneas"
                            search-placeholder="Buscar línea..."
                            empty-message="No hay líneas disponibles."
                            :options="$lines->map(fn ($line) => [
                                'value' => $line->id,
                                'label' => $line->name,
                                'selected' => in_array($line->id, old('lines', [])),
                            ])"
                        />
                        @error('lines')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <x-multi-select
                            name="work_centers"
                            label="Estaciones"
                            placeholder="Todas las estaciones"
                            search-placeholder="Buscar estación..."
                            empty-message="No hay estaciones disponibles."
                            :options="$workCenters->map(fn ($wc) => [
                                'value' => $wc->id,
                                'label' => $wc->number . ' – ' . $wc->name,
                                'selected' => in_array($wc->id, old('work_centers', [])),
                            ])"
                        />
                        @error('work_centers')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('production-records.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-arrow-left"></i>
                        <span>Regresar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-file-pdf"></i>
                        <span>Descargar PDF</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
    @include('partials.theme-datepicker-styles')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.theme-scripts')
    @include('partials.theme-datepicker-scripts')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('exportForm').addEventListener('submit', function (e) {
                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;

                if (!start || !end) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Complete las fechas de inicio y fin.', confirmButtonColor: '#1d4ed8' });
                    return;
                }
                if (end < start) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Fecha incorrecta', text: 'La fecha de fin no puede ser anterior a la de inicio.', confirmButtonColor: '#1d4ed8' })
                        .then(() => { document.getElementById('end_date').value = start; document.getElementById('end_date').focus(); });
                    return;
                }

                const linesChecked = document.querySelectorAll('input[name="lines[]"]:checked').length;
                const wcChecked = document.querySelectorAll('input[name="work_centers[]"]:checked').length;
                if (!linesChecked && !wcChecked) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Selección requerida', text: 'Seleccione al menos una línea o una estación.', confirmButtonColor: '#1d4ed8' });
                }
            });
        });
    </script>
@stop
