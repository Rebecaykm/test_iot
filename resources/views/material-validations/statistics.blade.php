@extends('adminlte::page')

@section('title', 'Estadísticas de Escaneos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Estadísticas de Escaneos</h1>
        <form action="{{ route('material-validations.statistics') }}" method="GET" class="form-inline">
            <div class="input-group input-group-sm">
                <input type="date" name="date" class="form-control"
                    value="{{ request('date') ?? now()->format('Y-m-d') }}">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
@stop

@section('content')
    <!-- Resumen del día -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="info-box bg-light h-100">
                <span class="info-box-icon bg-primary"><i class="fas fa-chart-bar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Escaneos</span>
                    <span class="info-box-number">{{ $dailyStats['total'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="info-box bg-light h-100">
                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Escaneos OK</span>
                    <span class="info-box-number">{{ $dailyStats['ok'] ?? 0 }}</span>
                    <span class="progress-description text-muted">
                        {{ $dailyStats['ok_percentage'] ?? 0 }}% del total
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="info-box bg-light h-100">
                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Escaneos NG</span>
                    <span class="info-box-number">{{ $dailyStats['ng'] ?? 0 }}</span>
                    <span class="progress-description text-muted">
                        {{ $dailyStats['ng_percentage'] ?? 0 }}% del total
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfica por hora -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Escaneos por Hora -
                        {{ request('date') ? \Carbon\Carbon::parse(request('date'))->format('d M Y') : now()->format('d M Y') }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas por turno -->
    <div class="row mb-4">
        <div class="col-12 mb-3">
            <h5>Estadísticas por Turno</h5>
        </div>
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
                    $badgeClass = 'bg-secondary';
                    if (isset($isToday) && $isToday) {
                        $badgeClass = $index === 0 ? 'bg-warning' : 'bg-primary';
                    } else {
                        $badgeClass = $shift->abbreviation === 'D' ? 'bg-primary' : 'bg-secondary';
                    }
                @endphp

                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <span class="badge {{ $badgeClass }} mr-2">{{ $shift->abbreviation }}</span>
                                {{ $shiftTitle }}
                                @if (isset($isToday) && $isToday)
                                    <small class="text-muted ml-2">
                                        {{ $index === 0 ? '(Anterior)' : '(Actual)' }}
                                    </small>
                                @endif
                            </h6>
                        </div>
                        <div class="card-body d-flex flex-column">
                            @if ($shiftInfo && $shiftInfo->timeRange)
                                <div class="shift-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-muted small">
                                            {{ $shiftInfo->timeRange->startDateTime->format('H:i') }} -
                                            {{ $shiftInfo->timeRange->endDateTime->format('H:i') }}
                                            @if ($shiftInfo->timeRange->startDateTime->format('d/m') !== $shiftInfo->timeRange->endDateTime->format('d/m'))
                                                <br><small>({{ $shiftInfo->timeRange->startDateTime->format('d/m') }} -
                                                    {{ $shiftInfo->timeRange->endDateTime->format('d/m') }})</small>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="badge bg-success">{{ $shiftStat['ok'] }} OK</span>
                                            <span class="badge bg-danger ml-1">{{ $shiftStat['ng'] }} NG</span>
                                        </div>
                                    </div>

                                    <div class="progress mb-3" style="height: 25px;">
                                        <div class="progress-bar bg-success"
                                            style="width: {{ $shiftStat['ok_percentage'] }}%">
                                            {{ $shiftStat['ok_percentage'] }}%
                                        </div>
                                        <div class="progress-bar bg-danger"
                                            style="width: {{ $shiftStat['ng_percentage'] }}%">
                                            {{ $shiftStat['ng_percentage'] }}%
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-auto pt-2 border-top">
                                    <div>
                                        <strong>Total:</strong> {{ $shiftStat['total'] }}
                                    </div>
                                    <div>
                                        <strong>Eficiencia:</strong> {{ $shiftStat['ok_percentage'] }}%
                                    </div>
                                </div>
                            @else
                                <div
                                    class="alert alert-info text-center py-4 m-0 flex-grow-1 d-flex align-items-center justify-content-center">
                                    <div>
                                        <i class="fas fa-info-circle mr-2"></i>
                                        No hay datos para {{ $shiftTitle }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="col-12">
                <div class="alert alert-warning text-center py-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    No hay turnos configurados
                </div>
            </div>
        @endif
    </div>

    <!-- Tipos de NG -->
    @if (isset($ngTypes) && count($ngTypes) > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Tipos de NG
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="ngTypesChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Tipo de NG</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-center">Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($ngTypes as $ngType)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-danger mr-1">NG</span>
                                                        {{ $ngType['comment'] }}
                                                    </td>
                                                    <td class="text-center">
                                                        <strong>{{ $ngType['count'] }}</strong>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <div class="progress mr-2" style="height: 15px; width: 60px;">
                                                                <div class="progress-bar bg-danger"
                                                                    style="width: {{ $ngType['percentage'] }}%">
                                                                </div>
                                                            </div>
                                                            <small>{{ $ngType['percentage'] }}%</small>
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
            </div>
        </div>
    @endif

    <!-- Estadísticas por usuario -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Estadísticas por Usuario</h3>
                </div>
                <div class="card-body">
                    @if (count($userStats) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Usuario</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">OK</th>
                                        <th class="text-center">% OK</th>
                                        <th class="text-center">NG</th>
                                        <th class="text-center">% NG</th>
                                        <th class="text-center">Rendimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($userStats as $stat)
                                        @if (isset($stat['user']))
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if ($stat['user']->profile_photo_url)
                                                            <img src="{{ $stat['user']->profile_photo_url }}"
                                                                alt="{{ $stat['user']->name }}"
                                                                class="rounded-circle img-size-32 mr-2">
                                                        @else
                                                            <div
                                                                class="rounded-circle bg-secondary d-flex align-items-center justify-content-center img-size-32 mr-2">
                                                                <i class="fas fa-user text-white"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div>{{ $stat['user']->name }}</div>
                                                            <small
                                                                class="text-muted">{{ $stat['user']->nickname ?? 'Sin alias' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ $stat['total'] }}</td>
                                                <td class="text-center text-success">{{ $stat['ok'] }}</td>
                                                <td class="text-center">{{ $stat['ok_percentage'] }}%</td>
                                                <td class="text-center text-danger">{{ $stat['ng'] }}</td>
                                                <td class="text-center">{{ $stat['ng_percentage'] }}%</td>
                                                <td class="text-center">
                                                    <div class="progress mx-auto" style="height: 15px; width: 100px;">
                                                        <div class="progress-bar bg-success"
                                                            style="width: {{ $stat['ok_percentage'] }}%"></div>
                                                    </div>
                                                    <small>{{ $stat['ok_percentage'] }}%</small>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle mr-2"></i>
                            No hay datos de usuarios para mostrar
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .info-box {
            cursor: default;
            margin-bottom: 0;
            height: 100%;
        }

        .info-box-content {
            padding: 10px;
        }

        .img-size-32 {
            width: 32px;
            height: 32px;
            object-fit: cover;
        }

        .card-header h3.card-title {
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .info-box-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 70px;
        }

        .card-header {
            padding: 0.75rem 1.25rem;
        }

        .badge {
            font-weight: 500;
        }

        .progress-bar {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        /* Contenedor de la gráfica */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .chart-container canvas {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
        }

        /* Tarjetas de turnos con altura uniforme */
        .card {
            height: 100%;
        }

        .card-body {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .shift-content {
            flex-grow: 1;
        }

        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 1,
                                data: okData,
                            },
                            {
                                label: 'NG',
                                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                                borderColor: 'rgba(220, 53, 69, 1)',
                                borderWidth: 1,
                                data: ngData,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Hora del día'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Cantidad de escaneos'
                                },
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            }
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
                    const ngColors = [
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(255, 109, 31, 0.8)',
                        'rgba(111, 66, 193, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
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
                                backgroundColor: ngColors.slice(0, ngLabels.length),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            @endif
        });
    </script>
@endsection
