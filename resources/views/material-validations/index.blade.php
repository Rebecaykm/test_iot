@extends('adminlte::page')

@section('title', 'Historial de Escaneos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Historial de Escaneos</h1>
        </div>

        <a href="{{ route('material-validations.statistics') }}" class="btn-action btn-action-primary">
            <i class="fas fa-chart-pie"></i>
            <span class="d-none d-md-inline">Ver Estadísticas</span>
        </a>
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
            <form method="GET" action="{{ route('material-validations.index') }}">
                <div class="d-flex flex-wrap align-items-end justify-content-end" style="gap: 0.65rem;">

                    <div class="filter-field" style="flex: 1 1 280px; min-width: 220px; max-width: 360px;">
                        <label class="filter-label-text">Buscar</label>
                        <div class="filter-group">
                            <i class="fas fa-search filter-icon"></i>
                            <input type="text" name="search" class="filter-input" style="width: 100%;"
                                placeholder="Código, usuario o N° de parte..."
                                value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-label-text">Estado</label>
                        <div class="filter-group">
                            <i class="fas fa-clipboard-check filter-icon"></i>
                            <select name="status" class="filter-input" style="cursor: pointer;">
                                <option value="">Todos</option>
                                <option value="OK" {{ request('status') === 'OK' ? 'selected' : '' }}>Solo OK</option>
                                <option value="NG" {{ request('status') === 'NG' ? 'selected' : '' }}>Solo NG</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-label-text">Fecha</label>
                        <div class="filter-group">
                            <i class="fas fa-calendar-alt filter-icon"></i>
                            <input type="date" name="date" class="filter-input"
                                value="{{ request('date') }}" max="{{ now()->toDateString() }}">
                        </div>
                    </div>

                    <div class="d-flex" style="gap: 0.4rem; padding-bottom: 1px;">
                        <button type="submit" class="btn-filter-submit">
                            <i class="fas fa-search mr-1"></i>Buscar
                        </button>
                        @if (request()->filled('search') || request()->filled('date') || request()->filled('status'))
                            <a href="{{ route('material-validations.index') }}" class="btn-filter-clear">
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
                            <th class="th-cell">Usuario</th>
                            <th class="th-cell">Líneas</th>
                            <th class="th-cell">N° de Parte</th>
                            <th class="th-cell text-center">Estado</th>
                            <th class="th-cell">Fecha</th>
                            <th class="th-cell text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materialValidations as $validation)
                            <tr class="td-row">
                                {{-- Usuario --}}
                                <td class="td-cell">
                                    @if ($validation->user)
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $validation->user->profile_photo_url }}"
                                                alt="{{ $validation->user->name }}"
                                                class="rounded-circle mr-2"
                                                style="width: 34px; height: 34px; object-fit: cover; border: 1px solid #e2e8f0;">
                                            <div>
                                                <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $validation->user->name }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $validation->user->nickname ?? '—' }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size: 0.82rem;">Usuario eliminado</span>
                                    @endif
                                </td>

                                {{-- Líneas --}}
                                <td class="td-cell">
                                    @if ($validation->user && $validation->user->lines->count() > 0)
                                        <div class="d-flex flex-wrap" style="gap: 4px;">
                                            @foreach ($validation->user->lines as $line)
                                                <span class="badge-soft"
                                                    style="background-color: {{ $line->color }}1a; color: {{ $line->color }}; border: 1px solid {{ $line->color }}40;">
                                                    {{ $line->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size: 0.78rem;">Sin líneas</span>
                                    @endif
                                </td>

                                {{-- Número de Parte --}}
                                <td class="td-cell">
                                    <span class="badge-soft badge-secondary" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $validation->part_number ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Estado --}}
                                <td class="td-cell text-center">
                                    @if ($validation->validation_status == 'OK')
                                        <span class="badge-soft badge-success">
                                            <i class="fas fa-check-circle mr-1"></i>OK
                                        </span>
                                    @else
                                        <span class="badge-soft badge-danger">
                                            <i class="fas fa-times-circle mr-1"></i>{{ $validation->validation_status }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Fecha --}}
                                <td class="td-cell">
                                    <div style="font-size: 0.82rem; color: #334155;">{{ $validation->created_at->format('d-m-Y') }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $validation->created_at->format('H:i:s') }}
                                    </div>
                                </td>

                                {{-- Acciones --}}
                                <td class="td-cell text-center">
                                    <button type="button" class="btn-detail"
                                        onclick="showDetails({{ json_encode($validation) }})">
                                        <i class="fas fa-eye mr-1"></i>Detalles
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">
                                            @if (request()->filled('search') || request()->filled('date') || request()->filled('status'))
                                                No se encontraron resultados
                                            @else
                                                No hay validaciones registradas
                                            @endif
                                        </p>
                                        <small>
                                            @if (request()->filled('search') || request()->filled('date') || request()->filled('status'))
                                                Intenta ajustar los filtros de búsqueda
                                            @else
                                                Aún no se han realizado escaneos de material
                                            @endif
                                        </small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación --}}
        @if ($materialValidations->hasPages() || $materialValidations->total() > 0)
            <div class="card-footer bg-white py-3" style="border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                    <small class="text-muted">
                        Mostrando <strong>{{ $materialValidations->firstItem() ?? 0 }}</strong> –
                        <strong>{{ $materialValidations->lastItem() ?? 0 }}</strong> de
                        <strong>{{ $materialValidations->total() }}</strong> resultados
                    </small>
                    @if ($materialValidations->hasPages())
                        {{ $materialValidations->appends(request()->query())->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @endif

    </div>

    {{-- Modal de Detalles --}}
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-white" style="border-bottom: 1px solid #e9ecef;">
                    <h5 class="modal-title fw-600 text-dark" style="font-size: 1.05rem;">
                        <i class="fas fa-clipboard-list mr-2" style="color: #1d4ed8;"></i>Detalles de Validación
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="background: #f8fafc;">
                    {{-- Información General --}}
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="detail-box h-100">
                                <h6 class="detail-box-title">Información General</h6>
                                <div class="mb-2">
                                    <span class="text-muted" style="font-size: 0.75rem;">Fecha:</span>
                                    <div class="fw-500" id="detail-date"></div>
                                </div>
                                <div>
                                    <span class="text-muted" style="font-size: 0.75rem;">Estado:</span>
                                    <div id="detail-status"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box h-100">
                                <h6 class="detail-box-title">Usuario</h6>
                                <div id="detail-user"></div>
                                <div id="detail-user-lines" class="mt-2"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Códigos Escaneados --}}
                    <div class="detail-box mb-3">
                        <h6 class="detail-box-title">Códigos Escaneados</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="code-chip">
                                    <div class="code-chip-label"><i class="fas fa-tag mr-1"></i>Etiqueta Final</div>
                                    <div class="fw-500 text-break" id="detail-label"></div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="code-chip">
                                    <div class="code-chip-label"><i class="fas fa-image mr-1"></i>Ayuda Visual</div>
                                    <div class="fw-500 text-break" id="detail-visual"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="code-chip">
                                    <div class="code-chip-label"><i class="fas fa-box mr-1"></i>Contenedor</div>
                                    <div class="fw-500 text-break" id="detail-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Comentarios --}}
                    <div class="detail-box">
                        <h6 class="detail-box-title">Comentarios</h6>
                        <div class="text-break" id="detail-comment" style="font-size: 0.85rem; color: #334155;"></div>
                    </div>
                </div>
                <div class="modal-footer bg-white" style="border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn-filter-clear" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .card, .btn, .form-control, .table, .content-header h1, .modal-content {
            font-family: 'Inter', sans-serif !important;
        }

        /* ── Botones de acción (header) ── */
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
            cursor: pointer;
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

        /* ── Botón detalle ── */
        .btn-detail {
            display: inline-flex;
            align-items: center;
            height: 30px;
            padding: 0 0.7rem;
            font-size: 0.76rem;
            font-weight: 600;
            border-radius: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1.5px solid #bfdbfe;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-detail:hover { background: #dbeafe; color: #1e40af; }

        /* ── Modal ── */
        .detail-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 0.9rem 1rem;
        }
        .detail-box-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }
        .code-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.65rem 0.75rem;
            height: 100%;
        }
        .code-chip-label {
            font-size: 0.72rem;
            color: #94a3b8;
            margin-bottom: 0.3rem;
            font-weight: 500;
        }

        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }

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

        @media (max-width: 767px) {
            .filter-field { width: 100%; }
            .card-header form > div { flex-direction: column; align-items: stretch !important; }
            .filter-group { width: 100%; }
            .btn-filter-submit, .btn-filter-clear { flex: 1; justify-content: center; }
        }
    </style>
@stop

@section('js')
    <script>
        function showDetails(validation) {
            const date = new Date(validation.created_at);
            const formattedDate = date.toLocaleDateString('es-ES', {
                day: '2-digit', month: 'long', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });

            $('#detail-date').text(formattedDate);

            $('#detail-status').html(
                validation.validation_status === 'OK' ?
                '<span class="badge-soft badge-success mt-1"><i class="fas fa-check-circle mr-1"></i>OK</span>' :
                '<span class="badge-soft badge-danger mt-1"><i class="fas fa-times-circle mr-1"></i>' + validation.validation_status + '</span>'
            );

            let userHtml = '';
            let linesHtml = '';

            if (validation.user) {
                userHtml = `
                    <div class="d-flex align-items-center">
                        <img src="${validation.user.profile_photo_url}"
                             alt="${validation.user.name}"
                             class="rounded-circle mr-2"
                             style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #e2e8f0;">
                        <div>
                            <div class="fw-600" style="font-size: 0.9rem;">${validation.user.name}</div>
                            <small class="text-muted">${validation.user.nickname || 'N/A'}</small>
                        </div>
                    </div>
                `;

                if (validation.user.lines && validation.user.lines.length > 0) {
                    linesHtml = '<div class="d-flex flex-wrap" style="gap: 4px;">';
                    validation.user.lines.forEach(line => {
                        linesHtml += `<span class="badge-soft" style="background-color: ${line.color}1a; color: ${line.color}; border: 1px solid ${line.color}40;">${line.name}</span>`;
                    });
                    linesHtml += '</div>';
                }
            } else {
                userHtml = '<span class="text-muted">Usuario no disponible</span>';
            }

            $('#detail-user').html(userHtml);
            $('#detail-user-lines').html(linesHtml);

            $('#detail-container').text(validation.container_code || 'No escaneado');
            $('#detail-visual').text(validation.visual_aid_code || 'No escaneado');
            $('#detail-label').text(validation.final_label_code || 'No escaneado');
            $('#detail-comment').text(validation.validation_comment || 'No se registraron comentarios adicionales');

            $('#detailsModal').modal('show');
        }

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(el => $(el).alert('close'));
            }, 5000);
        });
    </script>
@stop
