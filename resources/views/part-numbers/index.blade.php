@extends('adminlte::page')

@section('title', 'Números de Parte')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Números de Parte</h1>
        </div>

        <div class="d-flex" style="gap: 0.5rem;">
            @can('edit part numbers')
                <form id="import-production-orders-form" method="POST"
                    action="{{ route('part-numbers.import-production-orders') }}" enctype="multipart/form-data"
                    class="d-none">
                    @csrf
                    <input type="file" name="file" id="import-production-orders-file"
                        accept=".xlsx,.xls,.csv,.ods">
                </form>
                <button type="button" id="btn-import-production-orders" class="btn-action btn-action-success">
                    <i class="fas fa-file-excel"></i>
                    <span class="d-none d-md-inline">Orden de Producción</span>
                </button>
            @endcan
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

    @if (session('import_warnings') && count(session('import_warnings')) > 0)
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert"
            style="border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-2"></i><strong>Advertencias de la importación:</strong>
            <ul class="mb-0 mt-1 pl-4">
                @foreach (session('import_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert"
            style="border-radius: 8px;">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ $errors->first() }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('part-numbers.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end" style="gap: 0.5rem;">

                    <div class="filter-group" style="width: 280px;">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" name="search" class="filter-input"
                            placeholder="Buscar parte, estación, línea..."
                            value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-filter-submit">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('part-numbers.index') }}" class="btn-filter-clear">
                            <i class="fas fa-times mr-1"></i>Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell">Línea</th>
                            <th class="th-cell">Centro de Trabajo</th>
                            <th class="th-cell">Número de Parte</th>
                            <th class="th-cell text-center">Clase</th>
                            <th class="th-cell text-center">Tasa</th>
                            <th class="th-cell text-center">Orden Prod.</th>
                            <th class="th-cell text-center">Estado</th>
                            <th class="th-cell text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($partNumbers as $partNumber)
                            <tr class="td-row">
                                <td class="td-cell">
                                    @if ($partNumber->workCenter && $partNumber->workCenter->line)
                                        <span class="badge-soft badge-primary">
                                            {{ $partNumber->workCenter->line->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    @if ($partNumber->workCenter)
                                        <div class="fw-600 text-dark" style="font-size: 0.85rem;">
                                            {{ $partNumber->workCenter->number }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            {{ $partNumber->workCenter->name }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $partNumber->number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $partNumber->name }}</div>
                                </td>

                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary">
                                        {{ $partNumber->itemClass->abbreviation ?? '-' }}
                                    </span>
                                </td>

                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-primary">
                                        @if ($partNumber->production_rate != 0)
                                            {{ number_format(60 / $partNumber->production_rate, 2) }}
                                        @else
                                            0.0
                                        @endif
                                    </span>
                                </td>

                                <td class="td-cell text-center">
                                    @if ($partNumber->production_order !== null)
                                        <span class="badge-soft badge-secondary"
                                            style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                            {{ $partNumber->production_order }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="td-cell text-center">
                                    @if ($partNumber->is_obsolete)
                                        <span class="badge-soft badge-danger">
                                            <i class="fas fa-times-circle mr-1"></i>Obsoleto
                                        </span>
                                    @else
                                        <span class="badge-soft badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>Activo
                                        </span>
                                    @endif
                                </td>

                                <td class="td-cell text-center">
                                    @can('edit part numbers')
                                        <a href="{{ route('part-numbers.edit', $partNumber) }}" class="btn-action btn-action-primary btn-action-sm">
                                            <i class="fas fa-edit"></i>
                                            <span>Editar</span>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">
                                            @if (request()->filled('search'))
                                                No se encontraron resultados para "{{ request('search') }}"
                                            @else
                                                No hay números de parte registrados
                                            @endif
                                        </p>
                                        @if (request()->filled('search'))
                                            <small>Intenta ajustar la búsqueda</small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación --}}
        @if ($partNumbers->hasPages() || $partNumbers->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                    <small class="text-muted">
                        Mostrando <strong>{{ $partNumbers->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $partNumbers->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $partNumbers->total() }}</strong> registros
                    </small>
                    @if ($partNumbers->hasPages())
                        {{ $partNumbers->links('pagination::bootstrap-4') }}
                    @endif
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
        .btn-action-success {
            background: #f0fdf4;
            color: #15803d;
            border-color: #86efac;
        }
        .btn-action-success:hover {
            background: #dcfce7;
            color: #166534;
            text-decoration: none;
        }
        .btn-action:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        /* ── Filtros ── */
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
            width: 100%;
            font-family: 'Inter', sans-serif;
        }
        .filter-input::placeholder {
            color: #94a3b8;
        }
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
        .btn-filter-submit:hover {
            background: #1e40af;
        }
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
        .td-row:hover {
            background-color: #f8fafc !important;
        }
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
            .filter-group, .btn-filter-submit, .btn-filter-clear {
                width: 100%;
            }
            .card-header form > div {
                flex-direction: column;
            }
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cerrar alertas automáticamente después de 8 segundos
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(el => $(el).alert('close'));
            }, 8000);

            // Importación de órdenes de producción
            const importBtn = document.getElementById('btn-import-production-orders');
            const importForm = document.getElementById('import-production-orders-form');
            const importFile = document.getElementById('import-production-orders-file');

            if (importBtn && importForm && importFile) {
                importBtn.addEventListener('click', () => importFile.click());

                importFile.addEventListener('change', () => {
                    if (!importFile.files.length) return;

                    importBtn.disabled = true;
                    importBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>' +
                        '<span class="d-none d-md-inline">Procesando...</span>';
                    importForm.submit();
                });
            }
        });
    </script>
@stop
