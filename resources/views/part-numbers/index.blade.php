@extends('adminlte::page')

@section('title', 'Números de Parte')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Números de Parte</h1>
        </div>

        <div class="d-flex gap-2">
            @can('edit part numbers')
                <form id="import-production-orders-form" method="POST"
                    action="{{ route('part-numbers.import-production-orders') }}" enctype="multipart/form-data"
                    class="d-none">
                    @csrf
                    <input type="file" name="file" id="import-production-orders-file"
                        accept=".xlsx,.xls,.csv,.ods">
                </form>
                <button type="button" id="btn-import-production-orders" class="btn-action btn-action-success"
                    aria-label="Importar orden de producción desde archivo">
                    <i class="fas fa-file-excel" aria-hidden="true"></i>
                    <span class="d-none d-md-inline">Orden de Producción</span>
                </button>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('import_warnings') && count(session('import_warnings')) > 0)
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
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
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ $errors->first() }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('part-numbers.index') }}">
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">

                    <div class="filter-group" style="width: 280px;">
                        <label for="search-part-number" class="sr-only">Buscar parte, estación o línea</label>
                        <i class="fas fa-search filter-icon" aria-hidden="true"></i>
                        <input type="text" id="search-part-number" name="search" class="filter-input"
                            placeholder="Buscar parte, estación, línea..."
                            value="{{ request('search') }}">
                    </div>

                    <button type="submit" class="btn-action btn-action-solid">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>

                    @if (request()->filled('search'))
                        <a href="{{ route('part-numbers.index') }}" class="btn-action btn-action-secondary">
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
                            <th class="th-cell" scope="col">Línea</th>
                            <th class="th-cell" scope="col">Centro de Trabajo</th>
                            <th class="th-cell" scope="col">Número de Parte</th>
                            <th class="th-cell text-center" scope="col">Clase</th>
                            <th class="th-cell text-center" scope="col">Tasa</th>
                            <th class="th-cell text-center" scope="col">Orden Prod.</th>
                            <th class="th-cell text-center" scope="col">Estado</th>
                            <th class="th-cell text-center" scope="col">Acciones</th>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
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
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
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
