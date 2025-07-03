@extends('adminlte::page')

@section('title', 'Validaciones de Material')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Historial de Escaneos</h1>
        <form action="{{ route('material-validations.index') }}" method="GET" class="form-inline">
            <div class="input-group input-group-sm mr-2">
                <input type="date"
                       name="date"
                       class="form-control"
                       value="{{ request('date') }}"
                       max="{{ now()->toDateString() }}">
            </div>
            <div class="input-group input-group-sm">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Buscar..."
                       value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="w-35">Usuario</th>
                            <th class="w-15">Estado</th>
                            <th class="w-25">Fecha</th>
                            <th class="w-25 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materialValidations as $validation)
                            <tr class="border-bottom">
                                <td>
                                    @if($validation->user)
                                        <div class="d-flex align-items-center">
                                            <div class="mr-2">
                                                <img src="{{ $validation->user->profile_photo_url }}"
                                                     alt="{{ $validation->user->name }}"
                                                     class="rounded-circle img-size-32">
                                            </div>
                                            <div>
                                                <div class="text-sm font-weight-600">{{ $validation->user->name }}</div>
                                                <div class="text-xs text-muted">{{ $validation->user->nickname ?? 'Sin alias' }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">Usuario eliminado</span>
                                    @endif
                                </td>
                                <td>
                                    @if($validation->validation_status == 'OK')
                                        <span class="badge badge-success-light">
                                            <i class="fas fa-check-circle text-success mr-1"></i> {{ $validation->validation_status }}
                                        </span>
                                    @else
                                        <span class="badge badge-danger-light">
                                            <i class="fas fa-times-circle text-danger mr-1"></i> {{ $validation->validation_status }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-sm">{{ $validation->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-muted">{{ $validation->created_at->format('H:i') }}</div>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-outline-secondary"
                                            data-toggle="tooltip"
                                            title="Ver detalles"
                                            onclick="showDetails({{ json_encode($validation) }})">
                                        <i class="fas fa-eye"></i> Detalles
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    @if(request('search'))
                                        <div class="text-muted">
                                            <i class="fas fa-search fa-2x mb-3"></i>
                                            <h5>No se encontraron resultados</h5>
                                            <p class="mb-0">No hay registros para "{{ request('search') }}"</p>
                                        </div>
                                    @else
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-2x mb-3"></i>
                                            <h5>No hay validaciones registradas</h5>
                                            <p class="mb-0">Aún no se han realizado escaneos de material</p>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($materialValidations->hasPages())
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-sm text-muted">
                        Mostrando <strong>{{ $materialValidations->firstItem() }}</strong> a
                        <strong>{{ $materialValidations->lastItem() }}</strong> de
                        <strong>{{ $materialValidations->total() }}</strong>
                    </div>
                    <div>
                        {{ $materialValidations->appends([
                            'search' => request('search'),
                            'date' => request('date') // Mantener en paginación
                        ])->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal para detalles con mejoras visuales -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Detalles de Validación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Sección superior con cuadros del mismo tamaño -->
                    <div class="row mb-4 align-items-stretch">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-uppercase small text-muted mb-3">Información</h6>
                                <div class="d-flex mb-2">
                                    <div class="w-40 text-muted">Fecha:</div>
                                    <div class="w-60 font-weight-600" id="detail-date"></div>
                                </div>
                                <div class="d-flex">
                                    <div class="w-40 text-muted">Estado:</div>
                                    <div class="w-60" id="detail-status"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-uppercase small text-muted mb-3">Usuario</h6>
                                <div id="detail-user" class="d-flex align-items-center"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de códigos escaneados -->
                    <div class="border-top pt-4 mb-4">
                        <h6 class="text-uppercase small text-muted mb-3">Códigos escaneados</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted mb-2">
                                        <i class="fas fa-tag mr-1"></i> Etiqueta Final
                                    </div>
                                    <div class="font-weight-600 text-break" id="detail-label"></div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted mb-2">
                                        <i class="fas fa-image mr-1"></i> Ayuda Visual
                                    </div>
                                    <div class="font-weight-600 text-break" id="detail-visual"></div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted mb-2">
                                        <i class="fas fa-box mr-1"></i> Contenedor
                                    </div>
                                    <div class="font-weight-600 text-break" id="detail-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de comentarios -->
                    <div class="border-top pt-4">
                        <h6 class="text-uppercase small text-muted mb-3">Comentarios</h6>
                        <div class="border rounded p-3 bg-light">
                            <div id="detail-comment" class="text-break" style="max-height: 150px; overflow-y: auto;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .table thead th {
            border-top: none;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 500;
            color: #6c757d;
        }
        .table tbody tr {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
        }
        .table tbody tr:last-child {
            border-bottom: none;
        }
        .badge-success-light {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        .badge-danger-light {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .img-size-32 {
            width: 32px;
            height: 32px;
            object-fit: cover;
        }
        .img-size-28 {
            width: 28px;
            height: 28px;
            object-fit: cover;
        }
        .font-weight-600 {
            font-weight: 600;
        }
        .bg-gray-100 {
            background-color: #f8f9fa;
        }
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.03) !important;
        }
        .text-break {
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .align-items-stretch > [class*="col-"] {
            display: flex;
            flex-direction: column;
        }
        .align-items-stretch > [class*="col-"] > div {
            flex: 1;
        }
        .input-group-sm {
            max-width: 300px;
        }

        /* Ajustes para el campo de fecha */
        .input-group-sm input[type="date"] {
            height: calc(1.8125rem + 2px);
            padding: 0.25rem 0.5rem;
        }

        /* Separación entre controles */
        .form-inline .input-group {
            margin-left: 5px;
        }
    </style>
@stop

@section('js')
    <script>
        function showDetails(validation) {
            // Formatear fecha
            const date = new Date(validation.created_at);
            const formattedDate = date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Actualizar datos en el modal
            $('#detail-date').text(formattedDate);
            $('#detail-status').html(
                validation.validation_status === 'OK'
                    ? '<span class="badge badge-success-light p-2"> OK </span>'
                    : '<span class="badge badge-danger-light p-2"> NG </span>'
            );

            // Información de usuario con círculo más pequeño
            let userHtml = '';
            if (validation.user) {
                userHtml = `
                    <div class="mr-2">
                        <img src="${validation.user.profile_photo_url}"
                             alt="${validation.user.name}"
                             class="rounded-circle img-size-40">
                    </div>
                    <div>
                        <span class="d-block font-weight-600">${validation.user.name}</span>
                        <small class="d-block text-muted">${validation.user.nickname || 'N/A'}</small>
                    </div>
                `;
            } else {
                userHtml = '<span class="text-muted">Usuario no disponible</span>';
            }
            $('#detail-user').html(userHtml);

            // Códigos con manejo de texto largo
            $('#detail-container').text(validation.container_code || 'No escaneado');
            $('#detail-visual').text(validation.visual_aid_code || 'No escaneado');
            $('#detail-label').text(validation.final_label_code || 'No escaneado');

            // Comentario
            $('#detail-comment').text(
                validation.validation_comment || 'No se registraron comentarios adicionales'
            );

            // Mostrar modal
            $('#detailsModal').modal('show');
        }

        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();

            // Foco automático en el buscador
            $('input[name="search"]').focus();
        });
    </script>
@stop
