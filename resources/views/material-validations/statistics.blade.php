@extends('adminlte::page')

@section('title', 'Estadísticas de Escaneos')

@section('content_header')
    <h1>{{ __('Estadísticas de Escaneos') }}</h1>
@stop

@section('content')
    <!-- Filtro de fecha -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-body py-3">
            <form action="{{ route('material-validations.statistics') }}" method="GET"
                  class="d-flex align-items-center gap-2 flex-wrap">
                <label class="form-label fw-bold text-secondary mb-0 mr-2">Fecha:</label>
                <div class="input-group" style="width: 200px;">
                    <input type="date" name="date" class="form-control border-end-0"
                           value="{{ request('date') ?? now()->format('Y-m-d') }}"
                           max="{{ date('Y-m-d') }}">
                    @if(request('date'))
                        <a href="{{ route('material-validations.statistics') }}"
                           class="input-group-text bg-white border-start-0 text-danger">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
                <button class="btn btn-primary rounded-3 d-flex align-items-center" type="submit">
                    <i class="fas fa-search mr-2"></i>
                    <span>Buscar</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Resumen del día -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-3 mr-3">
                        <i class="fas fa-chart-bar fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-secondary small fw-500">Total Escaneos</div>
                        <div class="fs-4 fw-bold text-dark mb-1">{{ $dailyStats['total'] ?? 0 }}</div>
                        <div class="text-muted small">Del día seleccionado</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="icon-box bg-success bg-opacity-10 text-success rounded-3 mr-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-secondary small fw-500">Escaneos OK</div>
                        <div class="fs-4 fw-bold text-dark mb-1">{{ $dailyStats['ok'] ?? 0 }}</div>
                        <div class="text-muted small">{{ $dailyStats['ok_percentage'] ?? 0 }}% del total</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger rounded-3 mr-3">
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-secondary small fw-500">Escaneos NG</div>
                        <div class="fs-4 fw-bold text-dark mb-1">{{ $dailyStats['ng'] ?? 0 }}</div>
                        <div class="text-muted small">{{ $dailyStats['ng_percentage'] ?? 0 }}% del total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfica por hora -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-secondary">
                Escaneos por Hora - {{ request('date') ? \Carbon\Carbon::parse(request('date'))->format('d M Y') : now()->format('d M Y') }}
            </h5>
            <div class="text-muted small">
                <i class="fas fa-chart-line mr-1"></i>
                Distribución horaria
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Estadísticas por turno -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-bold text-secondary">
                <i class="fas fa-clock mr-2"></i>
                Estadísticas por Turno
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                @if (isset($shifts) && count($shifts) > 0)
                    @foreach ($shifts as $index => $shift)
                        @php
                            $shiftInfo = $shiftInfos[$index] ?? null;
                            $shiftStat = $shiftStats[$shift->abbreviation] ?? [
                                'total' => 0,
                                'ok' => 0,
                                'ng' => 0,
                                'ok_percentage' => 0,
                                'ng_percentage' => 0,
                            ];

                            $shiftTitle = $shiftInfo->label ?? ($shift->name ?? $shift->abbreviation);
                            $badgeClass = 'secondary';
                            if (isset($isToday) && $isToday) {
                                $badgeClass = $index === 0 ? 'warning' : 'primary';
                            } else {
                                $badgeClass = $shift->abbreviation === 'D' ? 'primary' : 'secondary';
                            }
                        @endphp

                        <div class="col-lg-6 col-md-12 mb-3">
                            <div class="card border-light shadow-sm rounded-3 h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge-status bg-{{ $badgeClass }} mr-2">
                                            {{ $shift->abbreviation }}
                                        </span>
                                        <span class="fw-bold text-secondary">{{ $shiftTitle }}</span>
                                        @if (isset($isToday) && $isToday)
                                            <span class="badge-status bg-{{ $index === 0 ? 'warning' : 'primary' }} ms-2">
                                                {{ $index === 0 ? 'Anterior' : 'Actual' }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($shiftInfo && $shiftInfo->timeRange)
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="text-muted small">
                                                <i class="far fa-clock mr-1"></i>
                                                {{ $shiftInfo->timeRange->startDateTime->format('H:i') }} -
                                                {{ $shiftInfo->timeRange->endDateTime->format('H:i') }}
                                                @if ($shiftInfo->timeRange->startDateTime->format('d/m') !== $shiftInfo->timeRange->endDateTime->format('d/m'))
                                                    <br><small class="text-muted">
                                                        ({{ $shiftInfo->timeRange->startDateTime->format('d/m') }} -
                                                        {{ $shiftInfo->timeRange->endDateTime->format('d/m') }})
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="d-flex gap-1">
                                                <span class="badge-status bg-success">
                                                    {{ $shiftStat['ok'] }} OK
                                                </span>
                                                <span class="badge-status bg-danger">
                                                    {{ $shiftStat['ng'] }} NG
                                                </span>
                                            </div>
                                        </div>

                                        <div class="progress mb-3" style="height: 12px;">
                                            <div class="progress-bar bg-success"
                                                 style="width: {{ $shiftStat['ok_percentage'] }}%"
                                                 title="OK: {{ $shiftStat['ok_percentage'] }}%">
                                            </div>
                                            <div class="progress-bar bg-danger"
                                                 style="width: {{ $shiftStat['ng_percentage'] }}%"
                                                 title="NG: {{ $shiftStat['ng_percentage'] }}%">
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between pt-3 border-top border-light-subtle">
                                            <div>
                                                <div class="text-secondary small">Total:</div>
                                                <div class="fw-bold fs-5">{{ $shiftStat['total'] }}</div>
                                            </div>
                                            <div>
                                                <div class="text-secondary small">Eficiencia:</div>
                                                <div class="fw-bold fs-5 text-success">{{ $shiftStat['ok_percentage'] }}%</div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-info mb-0 p-3">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            No hay datos para {{ $shiftTitle }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="alert alert-warning p-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No hay turnos configurados
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tipos de NG -->
    @if (isset($ngTypes) && count($ngTypes) > 0)
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-secondary">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Tipos NG
                </h5>
                <div class="badge-status bg-danger">
                    Total NG: {{ $dailyStats['ng'] ?? 0 }}
                </div>
            </div>
            <div class="card-body">
                <div class="row align-items-stretch">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="chart-container h-100" style="min-height: 300px;">
                            <canvas id="ngTypesChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive h-100">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="fw-bold text-secondary text-uppercase border-light-subtle">
                                            <i class="fas fa-tag mr-1"></i>
                                            Tipo de NG
                                        </th>
                                        <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                            <i class="fas fa-hashtag mr-1"></i>
                                            Cantidad
                                        </th>
                                        <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">
                                            <i class="fas fa-chart-pie mr-1"></i>
                                            Porcentaje
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ngTypes as $ngType)
                                        <tr class="border-light-subtle">
                                            <td class="py-3">
                                                <span class="badge-status bg-danger mr-2">NG</span>
                                                <span class="fw-500">{{ $ngType['comment'] }}</span>
                                            </td>
                                            <td class="text-center fw-bold">{{ $ngType['count'] }}</td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <div class="progress mr-2" style="height: 8px; width: 80px;">
                                                        <div class="progress-bar bg-danger"
                                                             style="width: {{ $ngType['percentage'] }}%">
                                                        </div>
                                                    </div>
                                                    <span class="fw-bold">{{ $ngType['percentage'] }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Estadísticas por usuario -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-secondary">
                <i class="fas fa-users mr-2"></i>
                Estadísticas por Usuario
            </h5>
            <div class="text-muted small">
                {{ count($userStats) }} operadores
            </div>
        </div>
        <div class="card-body p-0">
            @if (count($userStats) > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle">Operador</th>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Total</th>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">OK</th>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">NG</th>
                                <th class="fw-bold text-secondary text-uppercase border-light-subtle text-center">Eficiencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($userStats as $stat)
                                @if (isset($stat['user']))
                                    <tr class="border-light-subtle">
                                        <td class="py-3">
                                            <div class="d-flex align-items-center">
                                                @if ($stat['user']->profile_photo_url)
                                                    <img src="{{ $stat['user']->profile_photo_url }}"
                                                         alt="{{ $stat['user']->name }}"
                                                         class="rounded-circle img-size-40 mr-3 border border-light">
                                                @else
                                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center img-size-40 mr-3">
                                                        <i class="fas fa-user text-primary fa-lg"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $stat['user']->name }}</div>
                                                    <small class="text-muted">{{ $stat['user']->nickname ?? 'Sin alias' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold fs-5">{{ $stat['total'] }}</td>
                                        <td class="text-center">
                                            <span class="badge-status bg-success">
                                                {{ $stat['ok'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge-status bg-danger">
                                                {{ $stat['ng'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="progress mb-2" style="height: 10px; width: 120px;">
                                                    <div class="progress-bar bg-success"
                                                         style="width: {{ $stat['ok_percentage'] }}%"
                                                         title="Eficiencia: {{ $stat['ok_percentage'] }}%">
                                                    </div>
                                                </div>
                                                <span class="fw-bold {{ $stat['ok_percentage'] >= 95 ? 'text-success' : ($stat['ok_percentage'] >= 90 ? 'text-warning' : 'text-danger') }}">
                                                    {{ $stat['ok_percentage'] }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-body">
                    <div class="alert alert-info mb-0 p-4 text-center">
                        <i class="fas fa-user-slash fa-2x mb-3 text-muted"></i>
                        <div class="fw-bold">No hay datos de operadores</div>
                        <small class="text-muted">No se encontraron registros de escaneos por usuario</small>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <!-- Fuente Google Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* Aplicar fuente */
        .card,
        .btn,
        .form-control,
        .table,
        .content-header h1 {
            font-family: 'Roboto', sans-serif !important;
        }

        /* Cards */
        .rounded-3 {
            border-radius: 12px !important;
        }

        .border-light-subtle {
            border-color: #f0f0f0 !important;
        }

        /* Icon boxes en resumen */
        .icon-box {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Formularios */
        .input-group .form-control {
            border-radius: 8px 0 0 8px !important;
            border: 1px solid #e0e0e0 !important;
        }

        .input-group-text {
            border-radius: 0 8px 8px 0 !important;
            background-color: white;
        }

        .form-control:focus {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
            outline: none !important;
        }

        /* Botones */
        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: 500 !important;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Badges simétricos - ESTILOS EXACTOS QUE PROPORCIONASTE */
        .badge-status {
            display: inline-block;
            padding: 0.4em 0.8em;
            text-align: center;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .badge-status.bg-primary {
            background-color: rgba(13, 110, 253, 0.1) !important;
            color: #0d6efd !important;
            border-color: rgba(13, 110, 253, 0.2) !important;
        }

        .badge-status.bg-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
            border-color: rgba(108, 117, 125, 0.2) !important;
        }

        .badge-status.bg-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
            color: #198754 !important;
            border-color: rgba(25, 135, 84, 0.2) !important;
        }

        .badge-status.bg-danger {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
            border-color: rgba(220, 53, 69, 0.2) !important;
        }

        .badge-status.bg-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
            color: #856404 !important;
            border-color: rgba(255, 193, 7, 0.2) !important;
        }

        .badge-status.bg-info {
            background-color: rgba(13, 202, 240, 0.1) !important;
            color: #0dcaf0 !important;
            border-color: rgba(13, 202, 240, 0.2) !important;
        }

        /* Tabla */
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
            box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.1);
        }

        .table thead th {
            font-weight: 700 !important;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
        }

        .table tbody td {
            font-size: 0.875rem;
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }

        .img-size-32 {
            width: 32px;
            height: 32px;
            object-fit: cover;
        }

        .img-size-40 {
            width: 40px;
            height: 40px;
            object-fit: cover;
        }

        /* Progress bars */
        .progress {
            border-radius: 10px;
            background-color: #f8f9fa;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        /* Gráficas */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        /* Tipografía */
        .fw-500 {
            font-weight: 500;
        }

        /* Alertas */
        .alert {
            border-radius: 8px;
            border: none;
        }

        /* Espaciado */
        .gap-1 {
            gap: 0.25rem;
        }
        .gap-2 {
            gap: 0.5rem;
        }

        /* Shadow para cards */
        .shadow-sm {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem 1rem;
            }

            .img-size-40 {
                width: 32px;
                height: 32px;
            }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuración de colores (coinciden con los badges)
            const colors = {
                ok: {
                    bg: 'rgba(25, 135, 84, 0.15)',
                    border: 'rgba(25, 135, 84, 0.8)'
                },
                ng: {
                    bg: 'rgba(220, 53, 69, 0.15)',
                    border: 'rgba(220, 53, 69, 0.8)'
                }
            };

            // Gráfica por hora
            const okData = Array(24).fill(0);
            const ngData = Array(24).fill(0);

            @if (isset($hourlyStats) && count($hourlyStats) > 0)
                @foreach ($hourlyStats as $hourStat)
                    @if (isset($hourStat['hour']) && $hourStat['hour'] >= 0 && $hourStat['hour'] <= 23)
                        okData[{{ $hourStat['hour'] }}] = {{ $hourStat['ok'] ?? 0 }};
                        ngData[{{ $hourStat['hour'] }}] = {{ $hourStat['ng'] ?? 0 }};
                    @endif
                @endforeach
            @endif

            const hourLabels = [];
            for (let i = 0; i < 24; i++) {
                hourLabels.push(i.toString().padStart(2, '0') + ':00');
            }

            const ctx = document.getElementById('hourlyChart');
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: hourLabels,
                        datasets: [{
                                label: 'OK',
                                backgroundColor: colors.ok.bg,
                                borderColor: colors.ok.border,
                                borderWidth: 2,
                                data: okData,
                                borderRadius: 6,
                            },
                            {
                                label: 'NG',
                                backgroundColor: colors.ng.bg,
                                borderColor: colors.ng.border,
                                borderWidth: 2,
                                data: ngData,
                                borderRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Hora del día',
                                    font: {
                                        size: 13,
                                        weight: '600'
                                    },
                                    color: '#6c757d'
                                },
                                ticks: {
                                    maxRotation: 45
                                }
                            },
                            y: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                title: {
                                    display: true,
                                    text: 'Cantidad de escaneos',
                                    font: {
                                        size: 13,
                                        weight: '600'
                                    },
                                    color: '#6c757d'
                                },
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    },
                                    usePointStyle: true,
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#212529',
                                bodyColor: '#6c757d',
                                borderColor: '#dee2e6',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12,
                                boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)'
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        }
                    }
                });
            }

            // Gráfica de tipos de NG
            @if (isset($ngTypes) && count($ngTypes) > 0)
                const ngTypesCtx = document.getElementById('ngTypesChart');
                if (ngTypesCtx) {
                    const ngLabels = [];
                    const ngCounts = [];

                    // Colores que coinciden con los badges
                    const ngColorsBg = [
                        'rgba(220, 53, 69, 0.15)',
                        'rgba(255, 109, 31, 0.15)',
                        'rgba(255, 193, 7, 0.15)',
                        'rgba(111, 66, 193, 0.15)',
                        'rgba(23, 162, 184, 0.15)',
                        'rgba(108, 117, 125, 0.15)',
                    ];

                    const ngColorsBorder = [
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 109, 31, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(111, 66, 193, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(108, 117, 125, 0.8)',
                    ];

                    @foreach ($ngTypes as $index => $ngType)
                        ngLabels.push('{{ addslashes($ngType['comment']) }}');
                        ngCounts.push({{ $ngType['count'] }});
                    @endforeach

                    new Chart(ngTypesCtx.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ngLabels,
                            datasets: [{
                                data: ngCounts,
                                backgroundColor: ngColorsBg.slice(0, ngLabels.length),
                                borderColor: ngColorsBorder.slice(0, ngLabels.length),
                                borderWidth: 2,
                                hoverOffset: 15
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        font: {
                                            size: 11,
                                            weight: '500'
                                        },
                                        usePointStyle: true,
                                        pointStyle: 'circle'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            @endif

            // Configurar fecha máxima
            const dateInput = document.querySelector('input[name="date"]');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('max', today);
            }
        });
    </script>
@endsection
