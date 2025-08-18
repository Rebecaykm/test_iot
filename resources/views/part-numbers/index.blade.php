@extends('adminlte::page')

@section('title', 'Números de Parte')

@section('content_header')
    <h1 class="fw-bold">{{ __('Números de Parte') }}</h1>
@stop

@section('content')
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <!-- Header con buscador -->
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="search-box">
                    <form method="GET" action="{{ route('part-numbers.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control border-end-0"
                                   placeholder="Buscar..." value="{{ request('search') }}">
                            <button type="submit" class="input-group-text bg-white border-start-0">
                                <i class="fas fa-search text-secondary"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Línea') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Centro de Trabajo') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Número de Parte') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Clase') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle">{{ __('Estado') }}</th>
                            <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($partNumbers as $partNumber)
                            <tr class="border-light-subtle">
                                <!-- Línea -->
                                <td class="py-3 small">
                                    @if ($partNumber->workCenter && $partNumber->workCenter->line)
                                        <span class="badge-status bg-primary bg-opacity-10 text-primary">
                                            {{ $partNumber->workCenter->line->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Centro de Trabajo -->
                                <td class="py-3 small">
                                    @if ($partNumber->workCenter)
                                        <div class="fw-500">{{ $partNumber->workCenter->number }}</div>
                                        <div class="text-muted">{{ $partNumber->workCenter->name }}</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Número de Parte -->
                                <td class="py-3 small">
                                    <div class="fw-500">{{ $partNumber->number }}</div>
                                    <div class="text-muted">{{ $partNumber->name }}</div>
                                </td>

                                <!-- Clase -->
                                <td class="py-3 small">
                                    {{ $partNumber->itemClass->abbreviation ?? '-' }}
                                </td>

                                <!-- Estado -->
                                <td class="py-3 small">
                                    @if ($partNumber->is_obsolete)
                                        <span class="badge-status bg-danger bg-opacity-10 text-danger">
                                            <i class="fas fa-times-circle"></i> Obsoleto
                                        </span>
                                    @else
                                        <span class="badge-status bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    @endif
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 text-center">
                                    <div class="btn-group" role="group">
                                        @can('edit part numbers')
                                            <a href="{{ route('part-numbers.edit', $partNumber->id) }}"
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <span class="text-secondary">No se encontraron resultados</span>
                                        @if (!empty(request('search')))
                                            <a href="{{ route('part-numbers.index') }}" class="btn btn-sm btn-link mt-2">
                                                Limpiar búsqueda
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

        <!-- Footer -->
        @if ($partNumbers->hasPages() || $partNumbers->total() > 0)
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Mostrando {{ $partNumbers->firstItem() ?? 0 }} a {{ $partNumbers->lastItem() ?? 0 }}
                        de {{ $partNumbers->total() }} resultados
                    </div>
                    <div>
                        {{ $partNumbers->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
@stop

@section('css')
    <style>
        body { font-family: 'Roboto', sans-serif !important; }
        .border-light-subtle { border-color: #f0f0f0 !important; }
        .table-hover tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        .rounded-3 { border-radius: 12px !important; }
        .fw-500 { font-weight: 500 !important; }

        /* Buscador */
        .search-box .input-group { width: 320px; }
        .search-box .form-control {
            border-radius: 20px 0 0 20px !important;
            border-right: none;
            padding: 0.5rem 1rem;
            height: 42px;
        }
        .search-box .input-group-text {
            border-radius: 0 20px 20px 0 !important;
            border-left: none;
            background-color: white;
            padding: 0 1rem;
        }
        .search-box .form-control:focus {
            border-color: #dee2e6 !important;
            box-shadow: none !important;
        }

        /* Badges */
        .badge-status {
            display: inline-block;
            min-width: 90px;
            padding: 0.4em 0.75em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Paginación */
        .pagination { margin-bottom: 0; }
        .page-item .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            color: #6c757d;
            font-size: 0.9rem;
            min-width: 32px;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            padding: 6px 12px;
        }
        .page-item.active .page-link { background-color: #1a73e8; color: white; }
        .page-item:not(.active) .page-link:hover {
            background-color: #f8f9fa;
            color: #1a73e8;
        }
    </style>
@stop
