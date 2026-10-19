@extends('adminlte::page')

@section('title', 'Sincronización a Infor')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Sincronización a Infor</h1>
            <small class="text-muted">
                Selecciona las estaciones que se envían a Infor y la fecha planeada desde la que se envían.
            </small>
        </div>
    </div>
@stop

@section('content')
    @include('partials.theme-alerts')

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>No se guardó la configuración: cada estación seleccionada necesita su fecha de inicio de envío.
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

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

    <form method="POST" action="{{ route('infor-sync.update') }}" id="infor-sync-form">
        @csrf
        @method('PUT')

        <div class="row">
            @foreach ($environments as $env)
                @php
                    $envKey = $env['key'];
                    $setting = $env['setting'];
                    $hasOldInput = session()->hasOldInput();
                    $enabled = $hasOldInput
                        ? (bool) old("{$envKey}.enabled")
                        : (bool) ($setting->enabled ?? false);

                    // [work_center_id => 'Y-m-d'] de las estaciones guardadas
                    $savedDates = ($setting?->workCenters ?? collect())
                        ->mapWithKeys(fn ($wc) => [$wc->work_center_id => $wc->sync_from->format('Y-m-d')]);

                    // Si falló la validación se respeta lo que el usuario capturó
                    $stationState = function ($workCenterId) use ($hasOldInput, $envKey, $savedDates) {
                        if ($hasOldInput) {
                            return [
                                (bool) old("{$envKey}.stations.{$workCenterId}.selected"),
                                old("{$envKey}.stations.{$workCenterId}.sync_from"),
                            ];
                        }

                        return [$savedDates->has($workCenterId), $savedDates->get($workCenterId)];
                    };
                @endphp

                <div class="col-xl-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 env-card rounded-4 overflow-hidden"
                        data-env="{{ $envKey }}">

                        {{-- Encabezado del ambiente --}}
                        <div class="card-header bg-white py-3 env-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="env-icon env-icon-{{ $envKey }}">
                                            <i class="fas {{ $env['icon'] }}"></i>
                                        </span>
                                        <span class="fw-600 text-dark" style="font-size: 0.95rem;">{{ $env['title'] }}</span>
                                        <span class="badge-soft env-status-badge {{ $enabled ? 'badge-success' : 'badge-secondary' }}"
                                            data-env="{{ $envKey }}">
                                            {{ $enabled ? 'Envío activo' : 'Envío desactivado' }}
                                        </span>
                                    </div>
                                    <small class="env-description">{{ $env['description'] }}</small>
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

                        {{-- Búsqueda y controles de la lista --}}
                        <div class="env-toolbar d-flex align-items-center flex-wrap gap-2">
                            <div class="filter-group flex-grow-1">
                                <i class="fas fa-search filter-icon"></i>
                                <input type="search" class="filter-input w-100 station-search" data-env="{{ $envKey }}"
                                    placeholder="Buscar línea, estación o número"
                                    aria-label="Buscar línea, estación o número en {{ $env['title'] }}">
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn-action btn-action-secondary btn-action-sm expand-all"
                                    data-env="{{ $envKey }}">
                                    <i class="fas fa-angle-double-down mr-1"></i>Expandir
                                </button>
                                <button type="button" class="btn-action btn-action-secondary btn-action-sm collapse-all"
                                    data-env="{{ $envKey }}">
                                    <i class="fas fa-angle-double-up mr-1"></i>Contraer
                                </button>
                            </div>
                            <small class="env-summary w-100" data-env="{{ $envKey }}"></small>
                        </div>

                        {{-- Líneas (contraídas) con sus estaciones --}}
                        <div class="card-body p-0 env-body {{ $enabled ? '' : 'env-body-disabled' }}">
                            <div class="line-list">
                                @forelse ($lines as $line)
                                    @php
                                        $lineDomId = "{$envKey}-line-{$line->id}";
                                        $lineSelectedCount = $line->workCenters
                                            ->filter(fn ($wc) => $stationState($wc->id)[0])
                                            ->count();
                                        $lineTotal = $line->workCenters->count();
                                        $lineHasErrors = $line->workCenters
                                            ->contains(fn ($wc) => $errors->has("{$envKey}.stations.{$wc->id}.sync_from"));
                                    @endphp

                                    <div class="line-item" data-env="{{ $envKey }}" data-line="{{ $line->id }}"
                                        data-search="{{ Str::lower(Str::ascii($line->name)) }}">
                                        <div class="line-header d-flex align-items-center">
                                            <div class="custom-control custom-checkbox line-check-wrap">
                                                <input type="checkbox" class="custom-control-input line-check"
                                                    id="{{ $lineDomId }}" data-env="{{ $envKey }}"
                                                    data-line="{{ $line->id }}">
                                                <label class="custom-control-label" for="{{ $lineDomId }}">
                                                    <span class="sr-only">Seleccionar todas las estaciones de {{ $line->name }}</span>
                                                </label>
                                            </div>

                                            <button type="button" class="line-toggle d-flex align-items-center gap-2"
                                                data-toggle="collapse" data-target="#{{ $lineDomId }}-stations"
                                                aria-controls="{{ $lineDomId }}-stations"
                                                aria-expanded="{{ $lineHasErrors ? 'true' : 'false' }}">
                                                <span class="line-dot" style="background-color: {{ $line->color ?: '#cbd5e1' }};"></span>
                                                <span class="line-name">{{ $line->name }}</span>
                                                <span class="badge-soft line-counter {{ $lineSelectedCount === 0 ? 'badge-secondary' : ($lineSelectedCount === $lineTotal ? 'badge-success' : 'badge-primary') }}"
                                                    data-env="{{ $envKey }}" data-line="{{ $line->id }}">
                                                    {{ $lineSelectedCount }}/{{ $lineTotal }}
                                                </span>
                                                <span class="badge-soft badge-warning line-missing d-none"
                                                    data-env="{{ $envKey }}" data-line="{{ $line->id }}"></span>
                                                <i class="fas fa-chevron-down line-chevron ml-auto" aria-hidden="true"></i>
                                            </button>
                                        </div>

                                        <div class="collapse {{ $lineHasErrors ? 'show' : '' }}" id="{{ $lineDomId }}-stations">
                                            <div class="station-list">
                                                <div class="station-row station-head" aria-hidden="true">
                                                    <span class="station-name-col">Estación</span>
                                                    <span class="station-number-col">Número</span>
                                                    <span class="station-date-col">Enviar desde</span>
                                                </div>

                                                @foreach ($line->workCenters as $workCenter)
                                                    @php
                                                        $wcDomId = "{$envKey}-wc-{$workCenter->id}";
                                                        [$wcSelected, $wcSyncFrom] = $stationState($workCenter->id);
                                                        $dateErrorKey = "{$envKey}.stations.{$workCenter->id}.sync_from";
                                                    @endphp

                                                    <div class="station-row {{ $wcSelected ? 'is-selected' : '' }}"
                                                        data-search="{{ Str::lower(Str::ascii($workCenter->name . ' ' . $workCenter->number)) }}">
                                                        <div class="station-name-col custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input wc-check"
                                                                id="{{ $wcDomId }}"
                                                                name="{{ $envKey }}[stations][{{ $workCenter->id }}][selected]"
                                                                value="1" data-env="{{ $envKey }}"
                                                                data-line="{{ $line->id }}"
                                                                data-date="{{ $wcDomId }}-date"
                                                                @checked($wcSelected)>
                                                            <label class="custom-control-label" for="{{ $wcDomId }}">
                                                                <span class="fw-500 station-name">{{ $workCenter->name }}</span>
                                                            </label>
                                                        </div>

                                                        <div class="station-number-col">
                                                            <span class="badge-soft badge-secondary station-number">{{ $workCenter->number }}</span>
                                                        </div>

                                                        <div class="station-date-col">
                                                            <input type="date" id="{{ $wcDomId }}-date"
                                                                name="{{ $envKey }}[stations][{{ $workCenter->id }}][sync_from]"
                                                                value="{{ $wcSyncFrom }}"
                                                                class="field-input station-date @error($dateErrorKey) is-invalid @enderror"
                                                                aria-label="Enviar a Infor desde (fecha planeada) — {{ $workCenter->name }}"
                                                                @required($wcSelected)>
                                                            @error($dateErrorKey)
                                                                <div class="field-error">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">No hay líneas con estaciones</p>
                                        <small>Asigna estaciones a una línea para poder enviarlas a Infor.</small>
                                    </div>
                                @endforelse

                                <div class="search-empty text-center py-4 text-muted d-none" data-env="{{ $envKey }}">
                                    <i class="fas fa-search fa-2x mb-2 d-block" style="color: #cbd5e1;"></i>
                                    <p class="mb-1 fw-500" style="color: #475569;">Ninguna línea o estación coincide</p>
                                    <small>Prueba con el nombre de la línea, de la estación o su número.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn-action btn-action-solid" id="infor-sync-submit">
                <i class="fas fa-save mr-1"></i>Guardar configuración
            </button>
        </div>
    </form>
@stop

@section('css')
    @include('partials.theme-styles')
    @include('partials.theme-buttons-outline')
    <style>
        /* ── Encabezado del ambiente ── */
        .env-header { border-bottom: 1px solid #e2e8f0; }
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
        .env-description {
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.7rem;
            color: #64748b;
        }

        /* ── Barra de búsqueda / resumen ── */
        .env-toolbar {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .env-toolbar .filter-group { background: #fff; min-width: 200px; }
        .env-summary { font-size: 0.75rem; color: #64748b; }

        /* ── Lista de líneas ── */
        .line-list { max-height: 600px; overflow-y: auto; }
        .line-item { border-bottom: 1px solid #e2e8f0; }
        .line-item:last-of-type { border-bottom: 0; }
        .line-header { padding: 0 1rem; }
        .line-header:hover { background: #f8fafc; }
        .line-check-wrap { margin-right: 0.25rem; }
        .line-toggle {
            flex: 1 1 auto;
            min-width: 0;
            padding: 0.7rem 0;
            border: 0;
            background: transparent;
            color: #334155;
            text-align: left;
            border-radius: 6px;
        }
        .line-toggle:focus { outline: none; }
        .line-toggle:focus-visible { box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.45); }
        .line-dot {
            flex: 0 0 auto;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
        }
        .line-name {
            font-size: 0.86rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .line-chevron { color: #94a3b8; font-size: 0.75rem; }
        .line-toggle[aria-expanded="true"] .line-chevron { transform: rotate(180deg); }
        @media (prefers-reduced-motion: no-preference) {
            .line-chevron { transition: transform 0.2s ease; }
        }

        /* ── Estaciones de una línea ── */
        .station-list {
            background: #fcfdfe;
            border-top: 1px solid #f1f5f9;
            padding: 0.25rem 0 0.5rem;
        }
        .station-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 1rem 0.4rem 2.6rem;
        }
        .station-row:hover { background: #f1f5f9; }
        .station-row.is-selected { background: #f8fbff; }
        .station-row.is-selected:hover { background: #eff6ff; }
        .station-head,
        .station-head:hover {
            background: transparent;
            padding-top: 0.5rem;
            padding-bottom: 0.25rem;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
        }
        .station-name-col { flex: 1 1 auto; min-width: 0; }
        .station-number-col { flex: 0 0 auto; width: 76px; text-align: right; }
        .station-date-col { flex: 0 0 auto; width: 160px; }
        .station-name { font-size: 0.83rem; color: #334155; }
        .station-number { font-family: 'SFMono-Regular', Consolas, monospace; }
        .field-input.station-date { height: 32px; padding: 0.2rem 0.5rem; font-size: 0.8rem; }

        @media (max-width: 575px) {
            .station-row { flex-wrap: wrap; padding-left: 1rem; }
            .station-head { display: none; }
            .station-name-col { flex-basis: 100%; }
            .station-number-col { text-align: left; }
            .station-date-col { flex: 1 1 auto; }
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
            const normalize = text => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

            const stationsOf = (env, line) =>
                document.querySelectorAll(`.wc-check[data-env="${env}"][data-line="${line}"]`);
            const dateOf = wcCheck => document.getElementById(wcCheck.dataset.date);
            const lineItemOf = (env, line) =>
                document.querySelector(`.line-item[data-env="${env}"][data-line="${line}"]`);

            // Abre/cierra una línea sin animación (búsqueda, expandir/contraer todo)
            const setOpen = (lineItem, open) => {
                lineItem.querySelector('.collapse').classList.toggle('show', open);
                lineItem.querySelector('.line-toggle').setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            // La fecha de inicio es obligatoria solo para las estaciones marcadas
            const refreshStation = wcCheck => {
                const dateInput = dateOf(wcCheck);
                dateInput.required = wcCheck.checked;
                if (!wcCheck.checked || dateInput.value) dateInput.classList.remove('is-invalid');
                wcCheck.closest('.station-row').classList.toggle('is-selected', wcCheck.checked);
            };

            // Checkbox de la línea, contador y aviso de estaciones sin fecha
            const refreshLine = (env, line) => {
                const stations = [...stationsOf(env, line)];
                const checked = stations.filter(cb => cb.checked);
                const missing = checked.filter(cb => !dateOf(cb).value).length;

                const lineCheck = document.querySelector(`.line-check[data-env="${env}"][data-line="${line}"]`);
                lineCheck.checked = checked.length > 0 && checked.length === stations.length;
                lineCheck.indeterminate = checked.length > 0 && checked.length < stations.length;

                const counter = document.querySelector(`.line-counter[data-env="${env}"][data-line="${line}"]`);
                counter.textContent = `${checked.length}/${stations.length}`;
                counter.classList.toggle('badge-secondary', checked.length === 0);
                counter.classList.toggle('badge-success', checked.length > 0 && checked.length === stations.length);
                counter.classList.toggle('badge-primary', checked.length > 0 && checked.length < stations.length);

                const missingBadge = document.querySelector(`.line-missing[data-env="${env}"][data-line="${line}"]`);
                missingBadge.textContent = missing === 1 ? '1 sin fecha' : `${missing} sin fecha`;
                missingBadge.classList.toggle('d-none', missing === 0);

                refreshSummary(env);
            };

            // Resumen del ambiente: estaciones seleccionadas y en cuántas líneas
            const refreshSummary = env => {
                const checked = [...document.querySelectorAll(`.wc-check[data-env="${env}"]:checked`)];
                const lines = new Set(checked.map(cb => cb.dataset.line)).size;
                const summary = document.querySelector(`.env-summary[data-env="${env}"]`);

                summary.textContent = checked.length === 0
                    ? 'Ninguna estación seleccionada'
                    : `${checked.length} ${checked.length === 1 ? 'estación seleccionada' : 'estaciones seleccionadas'} en ${lines} ${lines === 1 ? 'línea' : 'líneas'}`;
            };

            // Búsqueda: muestra solo las líneas/estaciones que coinciden y las abre
            const applySearch = env => {
                const input = document.querySelector(`.station-search[data-env="${env}"]`);
                const term = normalize(input.value);
                let visibleLines = 0;

                document.querySelectorAll(`.line-item[data-env="${env}"]`).forEach(lineItem => {
                    const rows = lineItem.querySelectorAll('.station-row:not(.station-head)');
                    const lineMatches = term === '' || lineItem.dataset.search.includes(term);
                    let visibleRows = 0;

                    rows.forEach(row => {
                        const show = lineMatches || row.dataset.search.includes(term);
                        row.classList.toggle('d-none', !show);
                        if (show) visibleRows++;
                    });

                    lineItem.classList.toggle('d-none', visibleRows === 0);
                    if (visibleRows > 0) visibleLines++;
                    if (term !== '' && visibleRows > 0) setOpen(lineItem, true);
                });

                document.querySelector(`.search-empty[data-env="${env}"]`)
                    .classList.toggle('d-none', visibleLines > 0);
            };

            document.querySelectorAll('.station-search').forEach(input => {
                input.addEventListener('input', () => applySearch(input.dataset.env));
            });

            // Expandir / contraer todas las líneas visibles del ambiente
            document.querySelectorAll('.expand-all, .collapse-all').forEach(button => {
                button.addEventListener('click', () => {
                    const open = button.classList.contains('expand-all');
                    document.querySelectorAll(`.line-item[data-env="${button.dataset.env}"]:not(.d-none)`)
                        .forEach(lineItem => setOpen(lineItem, open));
                });
            });

            // Check de línea: selecciona / deselecciona todas sus estaciones
            document.querySelectorAll('.line-check').forEach(lineCheck => {
                lineCheck.addEventListener('change', () => {
                    const { env, line } = lineCheck.dataset;
                    stationsOf(env, line).forEach(cb => {
                        cb.checked = lineCheck.checked;
                        refreshStation(cb);
                    });
                    refreshLine(env, line);

                    // Abre la línea para capturar las fechas que falten
                    if (lineCheck.checked) setOpen(lineItemOf(env, line), true);
                });
            });

            // Check de estación: actualiza su fecha y el estado de su línea
            document.querySelectorAll('.wc-check').forEach(wcCheck => {
                wcCheck.addEventListener('change', () => {
                    refreshStation(wcCheck);
                    refreshLine(wcCheck.dataset.env, wcCheck.dataset.line);

                    const dateInput = dateOf(wcCheck);
                    if (wcCheck.checked && !dateInput.value) dateInput.focus();
                });

                dateOf(wcCheck).addEventListener('change', () => {
                    refreshStation(wcCheck);
                    refreshLine(wcCheck.dataset.env, wcCheck.dataset.line);
                });
            });

            // Antes de validar: abre las líneas con estaciones sin fecha para que el
            // navegador pueda enfocar el campo (no puede enfocar uno dentro de una línea contraída)
            document.getElementById('infor-sync-submit').addEventListener('click', () => {
                const missing = [...document.querySelectorAll('.wc-check:checked')]
                    .filter(cb => !dateOf(cb).value);

                missing.forEach(cb => {
                    const search = document.querySelector(`.station-search[data-env="${cb.dataset.env}"]`);
                    if (cb.closest('.d-none') && search.value) {
                        search.value = '';
                        applySearch(cb.dataset.env);
                    }
                    dateOf(cb).classList.add('is-invalid');
                    setOpen(lineItemOf(cb.dataset.env, cb.dataset.line), true);
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
