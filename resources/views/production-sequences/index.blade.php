@extends('adminlte::page')

@section('title', 'Secuencias de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Secuencias de Producción</h1>
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

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('production-sequences.index') }}">
                <div class="d-flex flex-wrap align-items-end justify-content-end" style="gap: 0.65rem;">

                    <div class="filter-field" style="min-width: 220px;">
                        <label class="filter-label-text">N° de Parte</label>
                        <div class="filter-group">
                            <i class="fas fa-barcode filter-icon"></i>
                            <input type="text" name="search" class="filter-input"
                                placeholder="Buscar parte..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-label-text">Fecha</label>
                        <div class="filter-group">
                            <i class="fas fa-calendar-alt filter-icon"></i>
                            <input type="date" name="date" class="filter-input"
                                value="{{ request('date') }}" max="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-label-text">Turno</label>
                        <div class="filter-group">
                            <i class="fas fa-clock filter-icon"></i>
                            <select name="shift" class="filter-input" style="cursor: pointer;">
                                <option value="">Todos</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->abbreviation }}"
                                        {{ request('shift') === $shift->abbreviation ? 'selected' : '' }}>
                                        {{ $shift->abbreviation }} – {{ $shift->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="d-flex" style="gap: 0.4rem; padding-bottom: 1px;">
                        <button type="submit" class="btn-filter-submit">
                            <i class="fas fa-search mr-1"></i>Buscar
                        </button>
                        @if (request()->filled('search') || request()->filled('date') || request()->filled('shift'))
                            <a href="{{ route('production-sequences.index') }}" class="btn-filter-clear">
                                <i class="fas fa-times mr-1"></i>Limpiar
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell">N° de Orden</th>
                            <th class="th-cell">N° de Parte</th>
                            <th class="th-cell">Fecha</th>
                            <th class="th-cell">Turno</th>
                            <th class="th-cell text-center">Secuencia</th>
                            <th class="th-cell text-center">Cantidad</th>
                            <th class="th-cell text-center">Procesado</th>
                            <th class="th-cell">Actualizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sequences as $sequence)
                            <tr class="td-row">
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $sequence->order_number }}</div>
                                </td>
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $sequence->part_number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $sequence->part_name }}</div>
                                </td>
                                <td class="td-cell">
                                    <div style="font-size: 0.82rem; color: #334155;">
                                        {{ \Carbon\Carbon::parse($sequence->planned_date)->format('d-m-Y') }}
                                    </div>
                                </td>
                                <td class="td-cell">
                                    @if ($sequence->shift_name)
                                        <span class="badge-soft badge-primary">{{ $sequence->shift_name }}</span>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 0.82rem;">—</span>
                                    @endif
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $sequence->sequence_number }}
                                    </span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary">{{ number_format($sequence->quantity) }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    @if ($sequence->is_processed)
                                        <span class="badge-soft badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>Procesado
                                        </span>
                                    @else
                                        <span class="badge-soft badge-danger">
                                            <i class="fas fa-clock mr-1"></i>Pendiente
                                        </span>
                                    @endif
                                </td>
                                <td class="td-cell">
                                    <span style="font-size: 0.82rem; color: #475569; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ \Carbon\Carbon::parse($sequence->updated_at)->format('d-m-Y H:i') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">No se encontraron secuencias</p>
                                        <small>Intenta ajustar los filtros de búsqueda</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación --}}
        @if ($sequences->hasPages())
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                    <small class="text-muted">
                        Mostrando <strong>{{ $sequences->firstItem() }}</strong> –
                        <strong>{{ $sequences->lastItem() }}</strong> de
                        <strong>{{ $sequences->total() }}</strong> secuencias
                    </small>
                    {{ $sequences->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif

    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .card, .btn, .form-control, .table, .content-header h1 {
            font-family: 'Inter', sans-serif !important;
        }

        /* ── Filtros ── */
        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .filter-label-text {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 0;
        }
        .filter-group {
            display: inline-flex;
            align-items: center;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 0 0.6rem;
            height: 34px;
            transition: border-color 0.15s;
        }
        .filter-group:focus-within {
            border-color: #93c5fd;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
        }
        .filter-icon {
            color: #94a3b8;
            font-size: 0.75rem;
            margin-right: 0.45rem;
        }
        .filter-input {
            border: none;
            background: transparent;
            font-size: 0.82rem;
            color: #334155;
            outline: none;
            height: 100%;
            font-family: 'Inter', sans-serif;
        }
        .filter-input::placeholder { color: #94a3b8; }
        .filter-input option { color: #334155; }
        .btn-filter-submit {
            display: inline-flex;
            align-items: center;
            height: 34px;
            padding: 0 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 7px;
            background: #1d4ed8;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-filter-submit:hover { background: #1e40af; }
        .btn-filter-clear {
            display: inline-flex;
            align-items: center;
            height: 34px;
            padding: 0 0.75rem;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 7px;
            background: transparent;
            color: #64748b;
            border: 1.5px solid #e2e8f0;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-filter-clear:hover {
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
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
        .td-row:hover { background-color: #f8fafc !important; }
        .td-cell {
            padding: 0.5rem 0.85rem !important;
            vertical-align: middle !important;
            border-top: none !important;
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
        .badge-soft.badge-primary   { background: #eff6ff; color: #1d4ed8; }
        .badge-soft.badge-success   { background: #f0fdf4; color: #15803d; }
        .badge-soft.badge-danger    { background: #fef2f2; color: #b91c1c; }
        .badge-soft.badge-warning   { background: #fefce8; color: #92400e; }
        .badge-soft.badge-secondary { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

        /* ── Paginación ── */
        .pagination { margin-bottom: 0; }
        .pagination .page-link {
            border-radius: 6px !important;
            margin: 0 2px;
            border-color: #e2e8f0;
            color: #475569;
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        .pagination .page-item.active .page-link {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
        }
        .pagination .page-item.disabled .page-link { color: #cbd5e1; }

        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }

        @media (max-width: 767px) {
            .filter-field { width: 100%; }
            .card-header form > div { flex-direction: column; }
            .filter-group { width: 100%; }
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(el => $(el).alert('close'));
            }, 5000);
        });
    </script>
@stop
