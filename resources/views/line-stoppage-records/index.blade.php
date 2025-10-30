@extends('adminlte::page')

@section('title', 'Registros de Paros de Línea')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">{{ __('Registros de Paros de Línea') }}</h1>
        <div class="d-flex align-items-center gap-3">
            <div class="search-box">
                <form method="GET" action="{{ route('line-stoppage-records.index') }}" id="searchForm">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control border-end-0" placeholder="Buscar..."
                            value="{{ request('search') }}">
                        <button type="submit" class="input-group-text bg-white border-start-0">
                            <i class="fas fa-search text-secondary"></i>
                        </button>
                        @if (request()->has('search'))
                            <a href="{{ route('line-stoppage-records.index') }}"
                                class="input-group-text bg-white border-start-0 text-danger">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
            @can('create line stoppage records')
                <a href="{{ route('line-stoppage-records.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> {{ __('Agregar Registro') }}
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Tabla integrada sin bordes -->
            <div class="table-container-integrated">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Paro de Línea</th>
                            <th>Centro de Trabajo</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th class="text-center">Minutos</th>
                            <th>Fecha de Creación</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lineStoppageRecords as $record)
                            <tr>
                                <!-- Paro de Línea -->
                                <td>
                                    <div class="fw-medium text-dark">{{ $record->lineStoppage->name }}</div>
                                </td>

                                <!-- Centro de Trabajo -->
                                <td>
                                    @if ($record->workCenter)
                                        <div class="fw-medium text-dark">{{ $record->workCenter->name }}</div>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>

                                <!-- Hora de Inicio -->
                                <td>
                                    @if ($record->start_time)
                                        @if ($record->start_time instanceof \Carbon\Carbon)
                                            {{ $record->start_time->format('d-m-Y H:i') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($record->start_time)->format('d-m-Y H:i') }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Hora de Fin -->
                                <td>
                                    @if ($record->end_time)
                                        @if ($record->end_time instanceof \Carbon\Carbon)
                                            {{ $record->end_time->format('d-m-Y H:i') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($record->end_time)->format('d-m-Y H:i') }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Minutos -->
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">{{ $record->minutes_stoppage }} min</span>
                                </td>

                                <!-- Fecha de Creación -->
                                <td class="text-muted">
                                    {{ optional($record->created_at)->format('d-m-Y H:i') }}
                                </td>

                                <!-- Acciones -->
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @can('edit line stoppage records')
                                            <a href="{{ route('line-stoppage-records.edit', $record->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can('delete line stoppage records')
                                            <form action="{{ route('line-stoppage-records.destroy', $record->id) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    @if (request('search'))
                                        {{ __('No se encontraron registros de paros de línea que coincidan con') }}
                                        "{{ request('search') }}"
                                    @else
                                        {{ __('No hay registros de paros de línea registrados') }}
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if ($lineStoppageRecords->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $lineStoppageRecords->firstItem() ?? 0 }} a
                            {{ $lineStoppageRecords->lastItem() ?? 0 }} de
                            {{ $lineStoppageRecords->total() }} registros
                        </div>
                        {{ $lineStoppageRecords->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Fuente moderna */
        body,
        .card,
        .btn,
        .form-control,
        .table,
        h1,
        .main-header,
        .main-sidebar,
        .content-wrapper {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        }

        /* Card mejorada sin bordes visibles */
        .card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* Tabla completamente integrada sin bordes */
        .table-container-integrated {
            max-height: 650px;
            overflow-y: auto;
            overflow-x: hidden;
            border-radius: 12px;
            position: relative;
        }

        .table-container-integrated:hover::-webkit-scrollbar {
            height: 8px;
        }

        /* Encabezados sticky sin bordes */
        .table thead th {
            position: sticky;
            top: 0;
            background: linear-gradient(to bottom, #f9fafb 0%, #f3f4f6 100%);
            font-weight: 600;
            font-size: 0.813rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0.75rem;
            z-index: 10;
        }

        /* Filas de la tabla sin bordes */
        .table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.15s ease;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
        }

        .table tbody td {
            padding: 0.875rem 0.75rem;
            vertical-align: middle;
            border: none;
        }

        /* Eliminar bordes de la tabla */
        .table {
            border: none;
            margin-bottom: 0;
        }

        .table th,
        .table td {
            border: none;
        }

        /* Badges mejorados */
        .badge {
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            font-size: 0.813rem;
            border-radius: 6px;
            min-width: 60px;
            display: inline-block;
        }

        /* Botones */
        .btn-outline-primary {
            border: 1px solid #3b82f6;
            color: #3b82f6;
            border-radius: 8px;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.15s ease;
        }

        .btn-outline-primary:hover {
            background: #3b82f6;
            color: white;
        }

        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            border-radius: 8px;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.15s ease;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }

        /* Scrollbar personalizada */
        .table-container-integrated::-webkit-scrollbar {
            width: 8px;
            height: 0px;
        }

        .table-container-integrated::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 0 12px 12px 0;
        }

        .table-container-integrated::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .table-container-integrated::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Estilos para el filtro */
        .search-box .input-group {
            width: 350px;
        }

        .search-box .form-control {
            border-radius: 0 !important;
            border-right: none;
            padding: 0.5rem 1rem;
            height: 42px;
            font-size: 0.95rem;
            border: 1px solid #e5e7eb;
        }

        .search-box .form-control:first-child {
            border-radius: 20px 0 0 20px !important;
        }

        .search-box .input-group-text:last-child {
            border-radius: 0 20px 20px 0 !important;
        }

        .search-box .form-control:focus {
            border-color: #e5e7eb !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .search-box .input-group-text {
            border: 1px solid #e5e7eb;
            background: white;
        }

        /* Estilos para el botón de limpiar */
        .search-box .input-group-text.text-danger:hover {
            background-color: #f8f9fa;
            color: #dc3545 !important;
        }

        /* Estado vacío centrado */
        .table tbody tr td.text-center {
            border: none !important;
        }
    </style>
@stop
