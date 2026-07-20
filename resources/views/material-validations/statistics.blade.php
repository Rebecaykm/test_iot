@extends('adminlte::page')

@section('title', 'Estadísticas de Escaneos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Estadísticas de Escaneos</h1>
        </div>

        <a href="{{ route('material-validations.index') }}" class="btn-action btn-action-primary">
            <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
            </svg>
            <span class="d-none d-md-inline">Ver Historial</span>
        </a>
    </div>
@stop

@section('content')
    @php
        $selectedDate = request('date') ? \Carbon\Carbon::parse(request('date')) : now();
        $okPct = $dailyStats['ok_percentage'] ?? 0;
        $effColor = $okPct >= 95 ? 'success' : ($okPct >= 90 ? 'warning' : 'danger');
    @endphp

    {{-- Filtro de fecha --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body py-3">
            <form action="{{ route('material-validations.statistics') }}" method="GET"
                class="d-flex flex-wrap align-items-end" style="gap: 0.65rem;">
                <div class="filter-field">
                    <label class="filter-label-text">Fecha</label>
                    <div class="filter-group">
                        <i class="fas fa-calendar-alt filter-icon"></i>
                        <input type="date" name="date" class="filter-input"
                            value="{{ request('date') ?? now()->format('Y-m-d') }}"
                            max="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="d-flex" style="gap: 0.4rem; padding-bottom: 1px;">
                    <button class="btn-filter-submit" type="submit">
                        <i class="fas fa-search mr-1"></i>Buscar
                    </button>
                    @if (request('date'))
                        <a href="{{ route('material-validations.statistics') }}" class="btn-filter-clear">
                            <i class="fas fa-times mr-1"></i>Hoy
                        </a>
                    @endif
                </div>
                <div class="ml-auto d-none d-md-flex align-items-center text-muted" style="font-size: 0.8rem;">
                    <i class="far fa-calendar mr-1"></i>{{ $selectedDate->locale('es')->isoFormat('dddd, D [de] MMMM YYYY') }}
                </div>
            </form>
        </div>
    </div>

    {{-- Resumen del día --}}
    <div class="row mb-2">
        {{-- Total --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-label">Total Escaneos</div>
                        <div class="stat-value">{{ number_format($dailyStats['total'] ?? 0) }}</div>
                        <div class="stat-sub">Del día seleccionado</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- OK --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body stat-card">
                    <div class="stat-icon stat-icon-success">
                        <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-label">Escaneos OK</div>
                        <div class="stat-value text-success">{{ number_format($dailyStats['ok'] ?? 0) }}</div>
                        <div class="stat-sub">{{ $dailyStats['ok_percentage'] ?? 0 }}% del total</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- NG --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body stat-card">
                    <div class="stat-icon stat-icon-danger">
                        <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-label">Escaneos NG</div>
                        <div class="stat-value text-danger">{{ number_format($dailyStats['ng'] ?? 0) }}</div>
                        <div class="stat-sub">{{ $dailyStats['ng_percentage'] ?? 0 }}% del total</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Eficiencia --}}
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body stat-card">
                    <div class="stat-icon stat-icon-{{ $effColor }}">
                        <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                        </svg>
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-label">Eficiencia</div>
                        <div class="stat-value text-{{ $effColor }}">{{ $okPct }}%</div>
                        <div class="stat-sub">Escaneos OK sobre total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráfica por hora --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="section-title mb-0">
                <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                Escaneos por Hora
            </h5>
            <span class="text-muted d-none d-sm-block" style="font-size: 0.78rem;">Distribución horaria</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Estadísticas por turno --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="section-title mb-0">
                <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
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
                                'total' => 0, 'ok' => 0, 'ng' => 0,
                                'ok_percentage' => 0, 'ng_percentage' => 0,
                            ];
                            $shiftTitle = $shiftInfo->label ?? ($shift->name ?? $shift->abbreviation);
                            $shiftEff = $shiftStat['ok_percentage'];
                            $shiftEffColor = $shiftEff >= 95 ? 'success' : ($shiftEff >= 90 ? 'warning' : 'danger');
                        @endphp

                        <div class="col-lg-6 mb-3">
                            <div class="shift-card h-100">
                                <div class="d-flex align-items-center mb-3" style="gap: 0.5rem;">
                                    <span class="badge-soft badge-primary">{{ $shift->abbreviation }}</span>
                                    <span class="fw-600 text-dark" style="font-size: 0.9rem;">{{ $shiftTitle }}</span>
                                    @if (isset($isToday) && $isToday)
                                        <span class="badge-soft badge-{{ $index === 0 ? 'warning' : 'success' }}">
                                            {{ $index === 0 ? 'Anterior' : 'Actual' }}
                                        </span>
                                    @endif
                                </div>

                                @if ($shiftInfo && $shiftInfo->timeRange)
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 0.4rem;">
                                        <span class="text-muted" style="font-size: 0.78rem;">
                                            <i class="far fa-clock mr-1"></i>
                                            {{ $shiftInfo->timeRange->startDateTime->format('H:i') }} –
                                            {{ $shiftInfo->timeRange->endDateTime->format('H:i') }}
                                        </span>
                                        <div class="d-flex" style="gap: 0.3rem;">
                                            <span class="badge-soft badge-success">{{ $shiftStat['ok'] }} OK</span>
                                            <span class="badge-soft badge-danger">{{ $shiftStat['ng'] }} NG</span>
                                        </div>
                                    </div>

                                    <div class="progress-track mb-3">
                                        <div class="progress-seg bg-soft-success" style="width: {{ $shiftStat['ok_percentage'] }}%"></div>
                                        <div class="progress-seg bg-soft-danger" style="width: {{ $shiftStat['ng_percentage'] }}%"></div>
                                    </div>

                                    <div class="d-flex justify-content-between pt-3" style="border-top: 1px solid #f1f5f9;">
                                        <div>
                                            <div class="stat-label">Total</div>
                                            <div class="fw-600 text-dark" style="font-size: 1.15rem;">{{ $shiftStat['total'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="stat-label">Eficiencia</div>
                                            <div class="fw-600 text-{{ $shiftEffColor }}" style="font-size: 1.15rem;">{{ $shiftStat['ok_percentage'] }}%</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="empty-note">
                                        <i class="fas fa-info-circle mr-1"></i>No hay datos para {{ $shiftTitle }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="empty-note">
                            <i class="fas fa-exclamation-triangle mr-1"></i>No hay turnos configurados
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tipos de NG --}}
    @if (isset($ngTypes) && count($ngTypes) > 0)
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #e9ecef;">
                <h5 class="section-title mb-0">
                    <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    Tipos de NG
                </h5>
                <span class="badge-soft badge-danger">Total NG: {{ $dailyStats['ng'] ?? 0 }}</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-5 mb-4 mb-md-0">
                        <div class="chart-container" style="height: 280px;">
                            <canvas id="ngTypesChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr class="table-head-row">
                                        <th class="th-cell">Tipo de NG</th>
                                        <th class="th-cell text-center">Cantidad</th>
                                        <th class="th-cell">Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ngTypes as $ngType)
                                        <tr class="td-row">
                                            <td class="td-cell">
                                                <span class="fw-500 text-dark" style="font-size: 0.85rem;">{{ $ngType['comment'] }}</span>
                                            </td>
                                            <td class="td-cell text-center">
                                                <span class="badge-soft badge-danger">{{ $ngType['count'] }}</span>
                                            </td>
                                            <td class="td-cell">
                                                <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                                    <div class="progress-track" style="flex: 1; min-width: 60px;">
                                                        <div class="progress-seg bg-soft-danger" style="width: {{ $ngType['percentage'] }}%"></div>
                                                    </div>
                                                    <span class="fw-600 text-danger" style="font-size: 0.78rem; min-width: 42px;">{{ $ngType['percentage'] }}%</span>
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

    {{-- Estadísticas por usuario --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #e9ecef;">
            <h5 class="section-title mb-0">
                <svg class="hi" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Estadísticas por Usuario
            </h5>
            <span class="text-muted" style="font-size: 0.78rem;">{{ count($userStats) }} operadores</span>
        </div>
        <div class="card-body p-0">
            @if (count($userStats) > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="table-head-row">
                                <th class="th-cell">Operador</th>
                                <th class="th-cell text-center">Total</th>
                                <th class="th-cell text-center">OK</th>
                                <th class="th-cell text-center">NG</th>
                                <th class="th-cell text-center" style="min-width: 160px;">Eficiencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($userStats as $stat)
                                @if (isset($stat['user']))
                                    @php
                                        $uEff = $stat['ok_percentage'];
                                        $uEffColor = $uEff >= 95 ? 'success' : ($uEff >= 90 ? 'warning' : 'danger');
                                    @endphp
                                    <tr class="td-row">
                                        <td class="td-cell">
                                            <div class="d-flex align-items-center">
                                                @if ($stat['user']->profile_photo_url)
                                                    <img src="{{ $stat['user']->profile_photo_url }}"
                                                        alt="{{ $stat['user']->name }}"
                                                        class="rounded-circle mr-2"
                                                        style="width: 34px; height: 34px; object-fit: cover; border: 1px solid #e2e8f0;">
                                                @else
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mr-2"
                                                        style="width: 34px; height: 34px; background: #eff6ff; color: #1d4ed8;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $stat['user']->name }}</div>
                                                    <small class="text-muted">{{ $stat['user']->nickname ?? 'Sin alias' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="td-cell text-center">
                                            <span class="fw-600 text-dark" style="font-size: 0.95rem;">{{ $stat['total'] }}</span>
                                        </td>
                                        <td class="td-cell text-center">
                                            <span class="badge-soft badge-success">{{ $stat['ok'] }}</span>
                                        </td>
                                        <td class="td-cell text-center">
                                            <span class="badge-soft badge-danger">{{ $stat['ng'] }}</span>
                                        </td>
                                        <td class="td-cell text-center">
                                            <div class="d-flex flex-column align-items-center" style="gap: 3px;">
                                                <div class="progress-track w-100" style="max-width: 130px;">
                                                    <div class="progress-seg bg-soft-{{ $uEffColor }}" style="width: {{ $uEff }}%"></div>
                                                </div>
                                                <small class="fw-600 text-{{ $uEffColor }}" style="font-size: 0.72rem;">{{ $uEff }}%</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-user-slash fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                        <p class="mb-1 fw-500" style="color: #475569;">No hay datos de operadores</p>
                        <small>No se encontraron registros de escaneos por usuario</small>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .card, .btn, .form-control, .table, .content-header h1 {
            font-family: 'Inter', sans-serif !important;
        }

        /* ── Heroicons ── */
        .hi { width: 20px; height: 20px; }

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
        .btn-action .hi { width: 16px; height: 16px; }
        .btn-action-primary {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #93c5fd;
        }
        .btn-action-primary:hover { background: #dbeafe; color: #1e40af; text-decoration: none; }

        /* ── Filtros ── */
        .filter-field { display: flex; flex-direction: column; gap: 0.25rem; }
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
        .filter-icon { color: #94a3b8; font-size: 0.75rem; margin-right: 0.45rem; }
        .filter-input {
            border: none;
            background: transparent;
            font-size: 0.82rem;
            color: #334155;
            outline: none;
            height: 100%;
            font-family: 'Inter', sans-serif;
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
            transition: all 0.15s;
        }
        .btn-filter-clear:hover { background: #f1f5f9; color: #475569; text-decoration: none; }

        /* ── Tarjetas de resumen ── */
        .stat-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.35rem; }
        .stat-icon {
            width: 54px; height: 54px;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon .hi { width: 27px; height: 27px; }
        .stat-icon-primary { background: #eff6ff; color: #1d4ed8; }
        .stat-icon-success { background: #f0fdf4; color: #15803d; }
        .stat-icon-danger  { background: #fef2f2; color: #b91c1c; }
        .stat-icon-warning { background: #fefce8; color: #b45309; }
        .stat-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }
        .stat-value { font-size: 1.65rem; font-weight: 700; color: #1e293b; line-height: 1.15; }
        .stat-sub { font-size: 0.74rem; color: #94a3b8; }

        /* ── Títulos de sección ── */
        .section-title {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #334155;
        }
        .section-title .hi { color: #64748b; }

        /* ── Tarjeta de turno ── */
        .shift-card {
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 11px;
            padding: 1rem 1.1rem;
        }

        /* ── Tabla ── */
        .table-head-row { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
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
        .td-row { border-bottom: 1px solid #f1f5f9 !important; transition: background 0.1s ease; }
        .td-row:hover { background-color: #f8fafc !important; }
        .td-cell { padding: 0.5rem 0.85rem !important; vertical-align: middle !important; border-top: none !important; }

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

        /* ── Barras de progreso ── */
        .progress-track {
            display: flex;
            height: 8px;
            border-radius: 10px;
            background: #e2e8f0;
            overflow: hidden;
        }
        .progress-seg { height: 100%; transition: width 0.6s ease; }
        .bg-soft-success { background: #22c55e; }
        .bg-soft-danger  { background: #ef4444; }
        .bg-soft-warning { background: #f59e0b; }

        /* ── Gráficas ── */
        .chart-container { position: relative; height: 360px; width: 100%; }

        /* ── Notas vacías ── */
        .empty-note {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
            padding: 0.85rem 1rem;
            color: #64748b;
            font-size: 0.82rem;
            width: 100%;
        }

        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }
        .text-success { color: #15803d !important; }
        .text-danger  { color: #b91c1c !important; }
        .text-warning { color: #b45309 !important; }

        @media (max-width: 768px) {
            .chart-container { height: 280px; }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const colors = {
                ok: { bg: 'rgba(34, 197, 94, 0.18)', border: 'rgba(21, 128, 61, 0.85)' },
                ng: { bg: 'rgba(239, 68, 68, 0.18)', border: 'rgba(185, 28, 28, 0.85)' }
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
                        datasets: [
                            {
                                label: 'OK',
                                backgroundColor: colors.ok.bg,
                                borderColor: colors.ok.border,
                                borderWidth: 1.5,
                                data: okData,
                                borderRadius: 6,
                            },
                            {
                                label: 'NG',
                                backgroundColor: colors.ng.bg,
                                borderColor: colors.ng.border,
                                borderWidth: 1.5,
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
                                grid: { display: false },
                                ticks: { maxRotation: 45, font: { size: 11 }, color: '#94a3b8' }
                            },
                            y: {
                                grid: { color: 'rgba(0, 0, 0, 0.04)' },
                                beginAtZero: true,
                                ticks: { precision: 0, stepSize: 1, color: '#94a3b8' }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { padding: 18, font: { size: 12, weight: '600' }, usePointStyle: true, color: '#475569' }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.97)',
                                titleColor: '#1e293b',
                                bodyColor: '#64748b',
                                borderColor: '#e2e8f0',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12
                            }
                        },
                        interaction: { intersect: false, mode: 'index' }
                    }
                });
            }

            // Gráfica de tipos de NG
            @if (isset($ngTypes) && count($ngTypes) > 0)
                const ngTypesCtx = document.getElementById('ngTypesChart');
                if (ngTypesCtx) {
                    const ngLabels = [];
                    const ngCounts = [];

                    const ngColorsBg = [
                        'rgba(239, 68, 68, 0.18)',
                        'rgba(249, 115, 22, 0.18)',
                        'rgba(245, 158, 11, 0.18)',
                        'rgba(139, 92, 246, 0.18)',
                        'rgba(20, 184, 166, 0.18)',
                        'rgba(100, 116, 139, 0.18)',
                    ];
                    const ngColorsBorder = [
                        'rgba(185, 28, 28, 0.85)',
                        'rgba(194, 65, 12, 0.85)',
                        'rgba(180, 83, 9, 0.85)',
                        'rgba(109, 40, 217, 0.85)',
                        'rgba(15, 118, 110, 0.85)',
                        'rgba(71, 85, 105, 0.85)',
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
                                hoverOffset: 12
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '64%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { padding: 14, font: { size: 11, weight: '500' }, usePointStyle: true, pointStyle: 'circle', color: '#475569' }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            @endif
        });
    </script>
@endsection
