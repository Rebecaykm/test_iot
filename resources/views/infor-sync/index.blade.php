@extends('adminlte::page')

@section('title', 'Sincronización a Infor')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Sincronización a Infor</h1>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    @php
        $environments = [
            [
                'key' => 'live',
                'title' => 'Ambiente Live',
                'icon' => 'fa-broadcast-tower',
                'description' => 'LX834FU01.YF013 · LX834OU.YSF013C',
                'setting' => $liveSetting,
            ],
            [
                'key' => 'proto',
                'title' => 'Ambiente Proto',
                'icon' => 'fa-flask',
                'description' => 'LX834FU02.YF013 · LX834OU02.YSF013C',
                'setting' => $protoSetting,
            ],
        ];
    @endphp

    <form method="POST" action="{{ route('infor-sync.update') }}">
        @csrf
        @method('PUT')

        <div class="row">
            @foreach ($environments as $env)
                @php
                    $envKey = $env['key'];
                    $setting = $env['setting'];
                    $enabled = (bool) ($setting->enabled ?? false);
                    $selectedIds = $setting->work_center_ids ?? [];
                @endphp

                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 env-card rounded-4 overflow-hidden"
                        data-env="{{ $envKey }}">

                        {{-- Encabezado del ambiente --}}
                        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                                <div>
                                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                        <span class="env-icon env-icon-{{ $envKey }}">
                                            <i class="fas {{ $env['icon'] }}"></i>
                                        </span>
                                        <span class="fw-600 text-dark" style="font-size: 0.95rem;">{{ $env['title'] }}</span>
                                        <span class="badge-soft env-status-badge {{ $enabled ? 'badge-success' : 'badge-secondary' }}"
                                            data-env="{{ $envKey }}">
                                            {{ $enabled ? 'Envío activo' : 'Envío desactivado' }}
                                        </span>
                                    </div>
                                    <small class="text-muted"
                                        style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.7rem;">
                                        {{ $env['description'] }}
                                    </small>
                                </div>

                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input env-toggle"
                                        id="toggle-{{ $envKey }}" name="{{ $envKey }}[enabled]" value="1"
                                        data-env="{{ $envKey }}" @checked($enabled)>
                                    <label class="custom-control-label fw-600" for="toggle-{{ $envKey }}"
                                        style="font-size: 0.8rem; color: #475569;">
                                        Enviar a Infor
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Tabla de líneas y estaciones --}}
                        <div class="card-body p-0 env-body {{ $enabled ? '' : 'env-body-disabled' }}">
                            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr class="table-head-row">
                                            <th class="th-cell" scope="col">Línea / Estación</th>
                                            <th class="th-cell text-right" scope="col">Número</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lines as $line)
                                            @php
                                                $lineDomId = "{$envKey}-line-{$line->id}";
                                                $lineSelectedCount = $line->workCenters->whereIn('id', $selectedIds)->count();
                                                $lineTotal = $line->workCenters->count();
                                            @endphp

                                            <tr class="line-row">
                                                <td class="td-cell">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input line-check"
                                                            id="{{ $lineDomId }}" data-env="{{ $envKey }}"
                                                            data-line="{{ $line->id }}">
                                                        <label class="custom-control-label" for="{{ $lineDomId }}">
                                                            <span class="fw-600 text-dark" style="font-size: 0.85rem;">
                                                                <i class="fas fa-pallet mr-1" style="color: #64748b;"></i>{{ $line->name }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class="td-cell text-right">
                                                    <span class="badge-soft badge-secondary line-counter"
                                                        data-env="{{ $envKey }}" data-line="{{ $line->id }}">
                                                        {{ $lineSelectedCount }}/{{ $lineTotal }} estaciones
                                                    </span>
                                                </td>
                                            </tr>

                                            @foreach ($line->workCenters as $workCenter)
                                                @php $wcDomId = "{$envKey}-wc-{$workCenter->id}"; @endphp
                                                <tr class="td-row">
                                                    <td class="td-cell" style="padding-left: 2.4rem !important;">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input wc-check"
                                                                id="{{ $wcDomId }}" name="{{ $envKey }}[work_centers][]"
                                                                value="{{ $workCenter->id }}" data-env="{{ $envKey }}"
                                                                data-line="{{ $line->id }}"
                                                                @checked(in_array($workCenter->id, $selectedIds))>
                                                            <label class="custom-control-label" for="{{ $wcDomId }}">
                                                                <span class="fw-500" style="font-size: 0.83rem; color: #334155;">
                                                                    {{ $workCenter->name }}
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td class="td-cell text-right">
                                                        <span class="badge-soft badge-secondary"
                                                            style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                                            {{ $workCenter->number }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center py-5">
                                                    <div class="text-muted">
                                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                                        <p class="mb-1 fw-500" style="color: #475569;">
                                                            No se encontraron líneas con estaciones
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn-action btn-action-solid">
                <i class="fas fa-save mr-1"></i>Guardar configuración
            </button>
        </div>
    </form>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
    <style>
        /* ── Ícono del ambiente (específico de esta vista) ── */
        .env-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .env-icon-live  { background: #f0fdf4; color: #15803d; }
        .env-icon-proto { background: #eff6ff; color: #1d4ed8; }

        /* ── Encabezado de tabla fijo al hacer scroll (extiende .th-cell del partial) ── */
        .th-cell {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 1;
        }
        .line-row {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        /* ── Cuerpo deshabilitado (solo visual, sigue siendo editable) ── */
        .env-body-disabled { opacity: 0.55; }

        /* ── Switch: color "activo" verde (envío a Infor), forma ya viene del partial ── */
        .custom-switch .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #15803d;
            border-color: #15803d;
        }
    </style>
@stop

@section('js')
    @include('partials.theme-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const stationsOf = (env, line) =>
                document.querySelectorAll(`.wc-check[data-env="${env}"][data-line="${line}"]`);

            // Sincroniza el estado del checkbox de la línea y su contador
            const refreshLine = (env, line) => {
                const stations = [...stationsOf(env, line)];
                const checked = stations.filter(cb => cb.checked).length;

                const lineCheck = document.querySelector(`.line-check[data-env="${env}"][data-line="${line}"]`);
                lineCheck.checked = checked > 0 && checked === stations.length;
                lineCheck.indeterminate = checked > 0 && checked < stations.length;

                const counter = document.querySelector(`.line-counter[data-env="${env}"][data-line="${line}"]`);
                counter.textContent = `${checked}/${stations.length} estaciones`;
            };

            // Check de línea: selecciona / deselecciona todas sus estaciones
            document.querySelectorAll('.line-check').forEach(lineCheck => {
                lineCheck.addEventListener('change', () => {
                    const { env, line } = lineCheck.dataset;
                    stationsOf(env, line).forEach(cb => cb.checked = lineCheck.checked);
                    refreshLine(env, line);
                });
            });

            // Check de estación: actualiza el estado de su línea
            document.querySelectorAll('.wc-check').forEach(wcCheck => {
                wcCheck.addEventListener('change', () => {
                    refreshLine(wcCheck.dataset.env, wcCheck.dataset.line);
                });
            });

            // Toggle del ambiente: actualiza badge y atenúa el cuerpo
            document.querySelectorAll('.env-toggle').forEach(toggle => {
                toggle.addEventListener('change', () => {
                    const env = toggle.dataset.env;
                    const badge = document.querySelector(`.env-status-badge[data-env="${env}"]`);
                    const body = document.querySelector(`.env-card[data-env="${env}"] .env-body`);

                    badge.textContent = toggle.checked ? 'Envío activo' : 'Envío desactivado';
                    badge.classList.toggle('badge-success', toggle.checked);
                    badge.classList.toggle('badge-secondary', !toggle.checked);
                    body.classList.toggle('env-body-disabled', !toggle.checked);
                });
            });

            // Estado inicial de todas las líneas
            document.querySelectorAll('.line-check').forEach(lineCheck => {
                refreshLine(lineCheck.dataset.env, lineCheck.dataset.line);
            });
        });
    </script>
@stop
