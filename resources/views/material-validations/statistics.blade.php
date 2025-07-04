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
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i> Buscar
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
            <div class="info-box bg-primary h-100">
                <span class="info-box-icon"><i class="fas fa-info-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Escaneos</span>
                    <span class="info-box-number">{{ $dailyStats['total'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="info-box bg-success h-100">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Escaneos OK</span>
                    <span class="info-box-number">{{ $dailyStats['ok'] ?? 0 }}</span>
                    <span class="progress-description">
                        {{ $dailyStats['ok_percentage'] ?? 0 }}% del total
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="info-box bg-danger h-100">
                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Escaneos NG</span>
                    <span class="info-box-number">{{ $dailyStats['ng'] ?? 0 }}</span>
                    <span class="progress-description">
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
                <div class="card-header bg-light">
                    <h3 class="card-title">Escaneos por Hora - {{ request('date') ? \Carbon\Carbon::parse(request('date'))->format('d M Y') : now()->format('d M Y') }}</h3>
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
        @if(isset($shifts) && count($shifts) > 0)
            @foreach($shifts as $index => $shift)
                @php
                    $shiftInfo = $shiftInfos[$index] ?? null;
                    $shiftStat = $shiftStats[$shift->abbreviation] ?? [
                        'total' => 0,
                        'ok' => 0,
                        'ng' => 0,
                        'ok_percentage' => 0,
                        'ng_percentage' => 0
                    ];

                    // Determinar el título del turno
                    $shiftTitle = $shiftInfo->label ?? $shift->name ?? $shift->abbreviation;

                    // Determinar el color del badge
                    $badgeClass = 'bg-secondary';
                    if (isset($isToday) && $isToday) {
                        $badgeClass = $index === 0 ? 'bg-warning' : 'bg-primary'; // Anterior = warning, Actual = primary
                    } else {
                        $badgeClass = $shift->abbreviation === 'D' ? 'bg-primary' : 'bg-secondary';
                    }
                @endphp

                <div class="col-md-6 mb-3">
                    <div class="card card-uniform-height">
                        <div class="card-header bg-light">
                            <h3 class="card-title">
                                {{ $shiftTitle }}
                                @if(isset($isToday) && $isToday)
                                    <small class="text-muted ml-2">
                                        {{ $index === 0 ? '(Anterior)' : '(Actual)' }}
                                    </small>
                                @endif
                            </h3>
                        </div>
                        <div class="card-body d-flex flex-column">
                            @if($shiftInfo && $shiftInfo->timeRange)
                                <div class="shift-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $shift->abbreviation }}
                                        </span>
                                            <span class="ml-2">
                                            {{ $shiftInfo->timeRange->startDateTime->format('H:i') }} -
                                            {{ $shiftInfo->timeRange->endDateTime->format('H:i') }}
                                        </span>
                                            @if($shiftInfo->timeRange->startDateTime->format('d/m') !== $shiftInfo->timeRange->endDateTime->format('d/m'))
                                                <small class="text-muted">
                                                    ({{ $shiftInfo->timeRange->startDateTime->format('d/m') }} - {{ $shiftInfo->timeRange->endDateTime->format('d/m') }})
                                                </small>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="badge bg-success">{{ $shiftStat['ok'] }} OK</span>
                                            <span class="badge bg-danger">{{ $shiftStat['ng'] }} NG</span>
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

                                <div class="d-flex justify-content-between mt-auto">
                                    <div>
                                        <strong>Total:</strong> {{ $shiftStat['total'] }}
                                    </div>
                                    <div>
                                        <strong>Eficiencia:</strong> {{ $shiftStat['ok_percentage'] }}%
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info text-center py-4 m-0 flex-grow-1 d-flex align-items-center justify-content-center">
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

    <!-- Estadísticas por usuario -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h3 class="card-title">Estadísticas por Usuario</h3>
                </div>
                <div class="card-body">
                    @if(count($userStats) > 0)
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
                                @foreach($userStats as $stat)
                                @if(isset($stat['user']))
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($stat['user']->profile_photo_url)
                                            <img src="{{ $stat['user']->profile_photo_url }}"
                                                 alt="{{ $stat['user']->name }}"
                                                 class="rounded-circle img-size-32 mr-2">
                                            @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center img-size-32 mr-2">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            @endif
                                            <div>
                                                <div>{{ $stat['user']->name }}</div>
                                                <small class="text-muted">{{ $stat['user']->nickname ?? 'Sin alias' }}</small>
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
                                            <div class="progress-bar bg-success" style="width: {{ $stat['ok_percentage'] }}%"></div>
                                            <div class="progress-bar bg-danger" style="width: {{ $stat['ng_percentage'] }}%"></div>
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
        .progress-xs {
            height: 8px;
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
            min-width: 20px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .progress {
            min-width: 80px;
        }

        /* NUEVOS ESTILOS PARA SOLUCIONAR LOS PROBLEMAS */

        /* Contenedor de la gráfica con altura fija */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        /* Asegurar que el canvas respete el contenedor */
        .chart-container canvas {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
        }

        /* Tarjetas de turnos con altura uniforme */
        .card-uniform-height {
            height: 100%;
            min-height: 300px;
        }

        /* Contenido de las tarjetas con flexbox */
        .card-uniform-height .card-body {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Contenido flexible */
        .shift-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        /* Asegurar que los alerts ocupen todo el espacio */
        .card-uniform-height .alert {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        /* Responsive: En móviles, eliminar altura mínima */
        @media (max-width: 768px) {
            .card-uniform-height {
                min-height: auto;
            }
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
    // Inicializar datos para 24 horas
    const okData = Array(24).fill(0);
    const ngData = Array(24).fill(0);

    // Llenar con datos reales si existen
    @if(isset($hourlyStats) && count($hourlyStats) > 0)
        @foreach($hourlyStats as $hourStat)
            @if(isset($hourStat['hour']) && $hourStat['hour'] >= 0 && $hourStat['hour'] <= 23)
                okData[{{ $hourStat['hour'] }}] = {{ $hourStat['ok'] ?? 0 }};
                ngData[{{ $hourStat['hour'] }}] = {{ $hourStat['ng'] ?? 0 }};
            @endif
        @endforeach
    @endif

    // Generar etiquetas de horas
    const hourLabels = [];
    for (let i = 0; i < 24; i++) {
        hourLabels.push(i.toString().padStart(2, '0') + ':00');
    }

    // Crear gráfica
    const ctx = document.getElementById('hourlyChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: hourLabels,
                datasets: [
                    {
                        label: 'OK',
                        backgroundColor: 'rgba(0, 166, 62, 0.4)',
                        borderColor: 'rgba(1, 102, 48, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        data: okData,
                    },
                    {
                        label: 'NG',
                        backgroundColor: 'rgba(231, 0, 11, 0.4)',
                        borderColor: 'rgba(159, 7, 18, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        data: ngData,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // CLAVE: Esto permite que la gráfica se adapte al contenedor
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hora del día'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Cantidad de escaneos'
                        },
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return 'Hora: ' + context[0].label;
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y;
                            },
                            afterBody: function(context) {
                                if (context.length > 0) {
                                    const index = context[0].dataIndex;
                                    const total = okData[index] + ngData[index];
                                    return total > 0 ? 'Total: ' + total : '';
                                }
                                return '';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
