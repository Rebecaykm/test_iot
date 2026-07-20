@extends('adminlte::page')

@section('title', 'Editar Número de Parte')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Editar Número de Parte</h1>
        </div>

        <div class="d-flex" style="gap: 0.5rem;">
            <a href="{{ route('part-numbers.index') }}" class="btn-action btn-action-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="d-none d-md-inline">Volver</span>
            </a>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert"
            style="border-radius: 8px;">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert"
            style="border-radius: 8px;">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Formulario Principal --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="mb-0 section-title">
                <i class="fas fa-cog mr-2" style="color: #94a3b8;"></i>Información General
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('part-numbers.update', $partNumber->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Información de solo lectura --}}
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <label class="field-label">Estación</label>
                        <div class="field-readonly">
                            {{ $partNumber->workCenter->name ?? 'No asignada' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <label class="field-label">Número</label>
                        <div class="field-readonly" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                            {{ $partNumber->number }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="field-label">Nombre</label>
                        <div class="field-readonly">
                            {{ $partNumber->name }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="field-label">Estado</label>
                        <div class="field-readonly">
                            @if ($partNumber->is_obsolete)
                                <span class="badge-soft badge-danger">
                                    <i class="fas fa-times-circle mr-1"></i>Obsoleto
                                </span>
                            @else
                                <span class="badge-soft badge-success">
                                    <i class="fas fa-check-circle mr-1"></i>Activo
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <label class="field-label">Clase</label>
                        <div class="field-readonly">
                            <span
                                class="badge-soft badge-secondary">{{ $partNumber->itemClass->abbreviation ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="field-label">Standard Pack</label>
                        <div class="field-readonly">
                            {{ $partNumber->standardPack->name ?? '-' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="field-label">Cantidad Standard Pack</label>
                        <div class="field-readonly">
                            {{ $partNumber->standard_pack_quantity ?? '-' }}
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <label class="field-label">Tasa de Producción</label>
                        <div class="field-readonly">
                            <span class="badge-soft badge-primary">
                                @if ($partNumber->production_rate != 0)
                                    {{ number_format(60 / $partNumber->production_rate, 2) }}
                                @else
                                    0.0
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Campos editables --}}
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <label for="production_order" class="field-label">Orden de Producción</label>
                        <input type="number" name="production_order" id="production_order" min="0" step="1"
                            class="field-input @error('production_order') is-invalid @enderror"
                            value="{{ old('production_order', $partNumber->production_order) }}"
                            placeholder="Dejar vacío si no tiene orden específica">
                        @error('production_order')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                        <small class="field-hint">Orden en que se debe producir este número de parte en la estación</small>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="efficiency" class="field-label">Eficiencia (%)</label>
                        <input type="number" name="efficiency" id="efficiency" step="0.01" min="0" max="100"
                            class="field-input @error('efficiency') is-invalid @enderror"
                            value="{{ old('efficiency', $partNumber->efficiency) }}">
                        @error('efficiency')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end" style="gap: 0.5rem; margin-top: 1.5rem;">
                    <a href="{{ route('part-numbers.index') }}" class="btn-action btn-action-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-save"></i>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Orden de Producción en la Estación --}}
    @if ($partNumbersInSameWorkCenter->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
                <h5 class="mb-0 section-title">
                    <i class="fas fa-list-ol mr-2" style="color: #94a3b8;"></i>
                    Orden de Producción en {{ $partNumber->workCenter->name ?? 'Esta Estación' }}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="table-head-row">
                                <th class="th-cell">Orden</th>
                                <th class="th-cell">Número de Parte</th>
                                <th class="th-cell">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $allPartNumbers = $partNumbersInSameWorkCenter
                                    ->concat(collect([$partNumber]))
                                    ->sortBy(fn($item) => $item->production_order ?? 9999);
                            @endphp
                            @foreach ($allPartNumbers as $pn)
                                <tr class="td-row {{ $pn->id === $partNumber->id ? 'row-current' : '' }}">
                                    <td class="td-cell">
                                        @if ($pn->production_order)
                                            <span class="badge-soft badge-primary"
                                                style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                                {{ $pn->production_order }}
                                            </span>
                                        @else
                                            <span class="badge-soft badge-secondary">Sin orden</span>
                                        @endif
                                    </td>
                                    <td class="td-cell">
                                        <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                            <div>
                                                <div class="fw-600 text-dark" style="font-size: 0.85rem;">
                                                    {{ $pn->number }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $pn->name }}
                                                </div>
                                            </div>
                                            @if ($pn->id === $partNumber->id)
                                                <span class="badge-soft badge-warning">
                                                    <i class="fas fa-star mr-1"></i>Actual
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="td-cell">
                                        @if ($pn->is_obsolete)
                                            <span class="badge-soft badge-danger">
                                                <i class="fas fa-times-circle mr-1"></i>Obsoleto
                                            </span>
                                        @else
                                            <span class="badge-soft badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>Activo
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Imágenes Asociadas --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                <h5 class="mb-0 section-title">
                    <i class="fas fa-images mr-2" style="color: #94a3b8;"></i>Imágenes Asociadas
                </h5>
                @can('create visual aids')
                    <a href="{{ route('visual-aids.create', ['part_number' => $partNumber->id]) }}"
                        class="btn-action btn-action-primary">
                        <i class="fas fa-plus"></i>
                        <span>Agregar Imagen</span>
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell">Imagen</th>
                            <th class="th-cell">Descripción</th>
                            <th class="th-cell">Estado</th>
                            <th class="th-cell text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visualAids as $visualAid)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <img src="{{ asset('storage/' . $visualAid->path) }}"
                                        alt="{{ $visualAid->alt_text }}"
                                        style="width: 56px; height: 56px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                                </td>
                                <td class="td-cell">
                                    <div class="fw-500" style="font-size: 0.85rem; color: #334155;">
                                        {{ $visualAid->alt_text ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="td-cell">
                                    @if ($visualAid->is_active)
                                        <span class="badge-soft badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>Activa
                                        </span>
                                    @else
                                        <span class="badge-soft badge-secondary">
                                            <i class="fas fa-times-circle mr-1"></i>Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="td-cell text-center">
                                    @can('delete visual aids')
                                        <form action="{{ route('visual-aids.destroy', [$visualAid->id, $partNumber->id]) }}"
                                            method="POST" style="display:inline;" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action btn-action-danger btn-action-sm">
                                                <i class="fas fa-trash"></i>
                                                <span>Eliminar</span>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-image fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-0 fw-500" style="color: #475569;">No hay imágenes asociadas</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body,
        .card,
        .btn,
        .form-control,
        .table,
        .content-header h1 {
            font-family: 'Inter', sans-serif !important;
        }

        /* ── Títulos de sección ── */
        .section-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #334155;
        }

        /* ── Botones de acción ── */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.42rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 7px;
            border: 1.5px solid transparent;
            text-decoration: none;
            transition: all 0.15s ease;
            white-space: nowrap;
            cursor: pointer;
        }

        .btn-action-sm {
            padding: 0.3rem 0.7rem;
            font-size: 0.75rem;
        }

        .btn-action-primary {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #93c5fd;
        }

        .btn-action-primary:hover {
            background: #dbeafe;
            color: #1e40af;
            text-decoration: none;
        }

        .btn-action-secondary {
            background: transparent;
            color: #64748b;
            border-color: #e2e8f0;
        }

        .btn-action-secondary:hover {
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
        }

        .btn-action-danger {
            background: #fff1f2;
            color: #b91c1c;
            border-color: #fca5a5;
        }

        .btn-action-danger:hover {
            background: #fee2e2;
            color: #991b1b;
            text-decoration: none;
        }

        .btn-action-solid {
            background: #1d4ed8;
            color: #fff;
            border-color: #1d4ed8;
        }

        .btn-action-solid:hover {
            background: #1e40af;
            border-color: #1e40af;
            color: #fff;
        }

        /* ── Campos del formulario ── */
        .field-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #64748b;
            margin-bottom: 0.4rem;
        }

        .field-readonly {
            display: flex;
            align-items: center;
            min-height: 38px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            color: #334155;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
        }

        .field-input {
            display: block;
            width: 100%;
            height: 38px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            color: #334155;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .field-input:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
        }

        .field-input::placeholder {
            color: #94a3b8;
        }

        .field-input.is-invalid {
            border-color: #fca5a5;
        }

        .field-input.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(252, 165, 165, 0.25);
        }

        .field-error {
            font-size: 0.78rem;
            color: #b91c1c;
            margin-top: 0.3rem;
        }

        .field-hint {
            display: block;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.3rem;
        }

        /* ── Tabla ── */
        .table-head-row {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .th-cell {
            font-size: 0.7rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #64748b !important;
            border: none !important;
            padding: 0.65rem 0.85rem !important;
            white-space: nowrap;
        }

        .td-row {
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background 0.1s ease;
        }

        .td-row:hover {
            background-color: #f8fafc !important;
        }

        .td-cell {
            padding: 0.5rem 0.85rem !important;
            vertical-align: middle !important;
            border-top: none !important;
        }

        .row-current {
            background-color: #fefce8 !important;
        }

        .row-current:hover {
            background-color: #fef9c3 !important;
        }

        /* ── Badges ── */
        .badge-soft {
            display: inline-flex;
            align-items: center;
            padding: 0.28em 0.65em;
            border-radius: 5px;
            font-size: 0.73rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-soft.badge-primary {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .badge-soft.badge-success {
            background: #f0fdf4;
            color: #15803d;
        }

        .badge-soft.badge-danger {
            background: #fef2f2;
            color: #b91c1c;
        }

        .badge-soft.badge-warning {
            background: #fefce8;
            color: #92400e;
        }

        .badge-soft.badge-secondary {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .fw-500 {
            font-weight: 500;
        }

        .fw-600 {
            font-weight: 600;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Confirmación antes de eliminar imágenes
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: '¡No podrás revertir esta acción!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#1d4ed8',
                            cancelButtonColor: '#b91c1c',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.submit();
                            }
                        });
                    } else if (confirm(
                            '¿Estás seguro de que deseas eliminar esta imagen? Esta acción no se puede deshacer.'
                            )) {
                        this.submit();
                    }
                });
            });

            // Cerrar alertas automáticamente después de 5 segundos
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(el => $(el).alert('close'));
            }, 5000);
        });
    </script>
@stop
