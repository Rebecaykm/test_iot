@extends('adminlte::page')

@section('title', 'Historial de Escaneos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1>{{ __('Historial de Escaneos') }}</h1>
    </div>
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
        <!-- Header con filtros alineados a la derecha -->
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-end">
                <div class="search-box">
                    <form method="GET" action="{{ route('material-validations.index') }}" id="searchForm">
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <!-- Filtro de fecha -->
                            <div class="input-group" style="width: 200px;">
                                <input type="date" name="date" class="form-control"
                                       value="{{ request('date') }}"
                                       max="{{ now()->toDateString() }}">
                            </div>

                            <!-- Buscador de texto -->
                            <div class="input-group" style="width: 300px;">
                                <input type="text" name="search" class="form-control border-end-0"
                                       placeholder="Buscar por código, usuario o número de parte..."
                                       value="{{ request('search') }}">
                                <button type="submit" class="input-group-text bg-white border-start-0">
                                    <i class="fas fa-search text-secondary"></i>
                                </button>
                                @if (request()->filled('search') || request()->filled('date'))
                                    <a href="{{ route('material-validations.index') }}"
                                       class="input-group-text bg-white border-start-0 text-danger">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Usuario</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Líneas</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">N° de Parte</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Estado</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">Fecha</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materialValidations as $validation)
                            <tr class="border-light-subtle">
                                <!-- Usuario -->
                                <td class="py-3">
                                    @if ($validation->user)
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $validation->user->profile_photo_url }}"
                                                 alt="{{ $validation->user->name }}"
                                                 class="rounded-circle mr-2"
                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                            <div>
                                                <div>{{ $validation->user->name }}</div>
                                                <div class="text-muted small">{{ $validation->user->nickname ?? '-' }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">Usuario eliminado</span>
                                    @endif
                                </td>

                                <!-- Líneas -->
                                <td class="py-3">
                                    @if ($validation->user && $validation->user->lines->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($validation->user->lines as $line)
                                                <span class="badge-status" style="background-color: {{ $line->color }}20; color: {{ $line->color }}; border: 1px solid {{ $line->color }}40;">
                                                    {{ $line->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">Sin líneas</span>
                                    @endif
                                </td>

                                <!-- Número de Parte -->
                                <td class="py-3">
                                    <div>{{ $validation->part_number ?? 'N/A' }}</div>
                                </td>

                                <!-- Estado -->
                                <td class="py-3 text-center">
                                    @if ($validation->validation_status == 'OK')
                                        <span class="badge-status badge-success">
                                            <i class="fas fa-check-circle ml-1"></i>
                                            {{ $validation->validation_status }}
                                        </span>
                                    @else
                                        <span class="badge-status badge-danger">
                                            <i class="fas fa-times-circle ml-1"></i>
                                            {{ $validation->validation_status }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Fecha -->
                                <td class="py-3">
                                    <div>{{ $validation->created_at->format('d/m/Y') }}</div>
                                    <div class="text-muted small">{{ $validation->created_at->format('H:i:s') }}</div>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-3 shadow-sm"
                                            onclick="showDetails({{ json_encode($validation) }})">
                                        <i class="fas fa-eye ml-1"></i>
                                        <span>Detalles</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <h5 class="text-secondary">
                                            @if (request()->filled('search') || request()->filled('date'))
                                                No se encontraron resultados
                                            @else
                                                No hay validaciones registradas
                                            @endif
                                        </h5>
                                        <p class="text-muted mb-3">
                                            @if (request()->filled('search') || request()->filled('date'))
                                                Intenta con otros criterios de búsqueda
                                            @else
                                                Aún no se han realizado escaneos de material
                                            @endif
                                        </p>
                                        @if (request()->filled('search') || request()->filled('date'))
                                            <a href="{{ route('material-validations.index') }}" class="btn btn-sm btn-link">
                                                Limpiar filtros
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        @if ($materialValidations->hasPages() || $materialValidations->total() > 0)
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando {{ $materialValidations->firstItem() ?? 0 }} a
                        {{ $materialValidations->lastItem() ?? 0 }} de
                        {{ $materialValidations->total() }} resultados
                    </div>
                    @if ($materialValidations->hasPages())
                        {{ $materialValidations->appends(request()->query())->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Modal de Detalles -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Detalles de Validación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Información General -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="border rounded-3 p-3 h-100">
                                <h6 class="text-uppercase small text-muted mb-3 fw-bold">Información General</h6>
                                <div class="mb-2">
                                    <span class="text-muted small">Fecha:</span>
                                    <div class="fw-500" id="detail-date"></div>
                                </div>
                                <div>
                                    <span class="text-muted small">Estado:</span>
                                    <div id="detail-status"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <h6 class="text-uppercase small text-muted fw-bold">Usuario</h6>
                                <div id="detail-user"></div>
                                <div id="detail-user-lines"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Códigos Escaneados -->
                    <div class="border-top pt-4 mb-4">
                        <h6 class="text-uppercase small text-muted mb-3 fw-bold">Códigos Escaneados</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="border rounded-3 p-3">
                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-tag mr-1"></i> Etiqueta Final
                                    </div>
                                    <div class="fw-500 text-break" id="detail-label"></div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded-3 p-3">
                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-image mr-1"></i> Ayuda Visual
                                    </div>
                                    <div class="fw-500 text-break" id="detail-visual"></div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded-3 p-3">
                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-box mr-1"></i> Contenedor
                                    </div>
                                    <div class="fw-500 text-break" id="detail-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comentarios -->
                    <div class="border-top pt-4">
                        <h6 class="text-uppercase small text-muted mb-3 fw-bold">Comentarios</h6>
                        <div class="border rounded-3 p-3 bg-light">
                            <div id="detail-comment" class="text-break"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .card, .btn, .form-control, .table, .content-header h1, .modal-content {
            font-family: 'Roboto', sans-serif !important;
        }

        .border-light-subtle { border-color: #f0f0f0 !important; }
        .rounded-3 { border-radius: 12px !important; }

        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        .table thead th {
            font-weight: 700 !important;
            font-size: 0.85rem;
        }

        .search-box .input-group {
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .search-box .form-control {
            border: 1px solid #e0e0e0;
            height: 42px;
        }

        .badge-status {
            display: inline-block;
            min-width: 95px;
            padding: 0.5em 0.8em;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid;
            text-align: center;
        }

        .badge-status.badge-success {
            background: rgba(25,135,84,0.1);
            color: #198754;
            border-color: rgba(25,135,84,0.2);
        }

        .badge-status.badge-danger {
            background: rgba(220,53,69,0.1);
            color: #dc3545;
            border-color: rgba(220,53,69,0.2);
        }

        .fw-500 { font-weight: 500; }

        @media (max-width: 768px) {
            .search-box .input-group { width: 100% !important; }
            .d-flex.justify-content-end { justify-content: center !important; }
        }
    </style>
@stop

@section('js')
    <script>
        function showDetails(validation) {
            const date = new Date(validation.created_at);
            const formattedDate = date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            $('#detail-date').text(formattedDate);

            // Agregar el número de parte al modal
            $('#detail-part-number').text(validation.part_number || 'N/A');

            $('#detail-status').html(
                validation.validation_status === 'OK' ?
                '<span class="badge-status badge-success"><i class="fas fa-check-circle me-1"></i> OK</span>' :
                '<span class="badge-status badge-danger"><i class="fas fa-times-circle me-1"></i> NG</span>'
            );

            let userHtml = '';
            let linesHtml = '';

            if (validation.user) {
                userHtml = `
                    <div class="d-flex align-items-center">
                        <img src="${validation.user.profile_photo_url}"
                             alt="${validation.user.name}"
                             class="rounded-circle mr-2"
                             style="width: 40px; height: 40px; object-fit: cover;">
                        <div>
                            <div>${validation.user.name}</div>
                            <small class="text-muted">${validation.user.nickname || 'N/A'}</small>
                        </div>
                    </div>
                `;

                if (validation.user.lines && validation.user.lines.length > 0) {
                    linesHtml = '<div><strong class="small text-muted">Líneas:</strong><div class="d-flex flex-wrap gap-1 mt-2">';
                    validation.user.lines.forEach(line => {
                        linesHtml += `<span class="badge-status" style="background-color: ${line.color}20; color: ${line.color}; border: 1px solid ${line.color}40;">${line.name}</span>`;
                    });
                    linesHtml += '</div></div>';
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

            // Usar el nuevo modal de Bootstrap 5
            var detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
            detailsModal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-cerrar alertas después de 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (window.bootstrap && bootstrap.Alert) {
                        new bootstrap.Alert(alert).close();
                    }
                });
            }, 5000);
        });
    </script>
@stop
