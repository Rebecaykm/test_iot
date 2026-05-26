@extends('adminlte::page')

@section('title', 'Resumen de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Resumen de Producción</h1>
        </div>

        <a href="{{ route('production-records.summary.export', request()->query()) }}"
            class="btn-action btn-action-success">
            <i class="fas fa-file-excel"></i>
            <span class="d-none d-md-inline">Descargar Excel</span>
        </a>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">

        {{-- Filtros --}}
        <div class="card-header bg-white py-3" style="border-bottom: 1px solid #e9ecef;">
            <form method="GET" action="{{ route('production-records.summary') }}">
                <div class="d-flex flex-wrap align-items-end justify-content-end" style="gap: 0.65rem;">

                    <div class="filter-field">
                        <label class="filter-label-text">N° de Parte</label>
                        <div class="filter-group">
                            <i class="fas fa-barcode filter-icon"></i>
                            <input type="text" name="search" class="filter-input"
                                placeholder="Ej: PN-100..." value="{{ $search }}">
                        </div>
                    </div>

                    <div class="filter-field" style="min-width: 220px;">
                        <label class="filter-label-text">Estaciones</label>
                        <div class="ms-dropdown" id="ms-dropdown">
                            <div class="ms-trigger" id="ms-trigger">
                                <div class="ms-chips" id="ms-chips">
                                    <span class="ms-placeholder" id="ms-placeholder">Todas las estaciones</span>
                                </div>
                                <i class="fas fa-chevron-down ms-arrow" id="ms-arrow"></i>
                            </div>
                            <div class="ms-panel" id="ms-panel">
                                <div class="ms-search-wrap">
                                    <i class="fas fa-search ms-search-icon"></i>
                                    <input type="text" class="ms-search" id="ms-search" placeholder="Buscar estación...">
                                </div>
                                <div class="ms-options" id="ms-options">
                                    @foreach ($workCenters as $wc)
                                        @php $selected = is_array($selectedCenters) && in_array($wc->name, $selectedCenters); @endphp
                                        <label class="ms-option {{ $selected ? 'ms-selected' : '' }}"
                                            data-value="{{ $wc->name }}"
                                            data-label="{{ $wc->number }} – {{ $wc->name }}">
                                            <span class="ms-checkbox">
                                                <i class="fas fa-check ms-check-icon"></i>
                                            </span>
                                            <span class="ms-option-text">{{ $wc->number }} – {{ $wc->name }}</span>
                                            <input type="checkbox" name="work_centers[]"
                                                value="{{ $wc->name }}"
                                                {{ $selected ? 'checked' : '' }}
                                                style="display:none;">
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-label-text">Turno</label>
                        <div class="filter-group">
                            <i class="fas fa-clock filter-icon"></i>
                            <select name="shift" class="filter-input" style="cursor: pointer;">
                                <option value="">Todos</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->abbreviation }}"
                                        {{ $selectedShift === $shift->abbreviation ? 'selected' : '' }}>
                                        {{ $shift->abbreviation }} – {{ $shift->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-label-text">Desde</label>
                        <div class="filter-group">
                            <i class="fas fa-calendar filter-icon"></i>
                            <input type="date" name="startDate" class="filter-input" value="{{ $startDate }}">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-label-text">Hasta</label>
                        <div class="filter-group">
                            <i class="fas fa-calendar-check filter-icon"></i>
                            <input type="date" name="endDate" class="filter-input" value="{{ $endDate }}">
                        </div>
                    </div>

                    <div class="d-flex" style="gap: 0.4rem; padding-bottom: 1px;">
                        <button type="submit" class="btn-filter-submit">
                            <i class="fas fa-filter mr-1"></i>Filtrar
                        </button>
                        @if ($hasFilters)
                            <a href="{{ route('production-records.summary') }}" class="btn-filter-clear">
                                <i class="fas fa-times mr-1"></i>Limpiar
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="table-head-row">
                            <th class="th-cell">Estación</th>
                            <th class="th-cell">N° de Parte</th>
                            <th class="th-cell">Fecha</th>
                            <th class="th-cell">Turno</th>
                            <th class="th-cell text-center">Plan</th>
                            <th class="th-cell text-center">Real</th>
                            <th class="th-cell">Inicio</th>
                            <th class="th-cell">Término</th>
                            <th class="th-cell text-center">Tiempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionRecords as $rec)
                            @php
                                $prod = $rec->produced_quantity;
                                $plan = $rec->planned_quantity;
                                $color = $prod >= $plan ? 'success' : ($prod >= $plan * 0.8 ? 'warning' : 'danger');
                            @endphp
                            <tr class="td-row">
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $rec->work_number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $rec->work_name }}</div>
                                </td>
                                <td class="td-cell">
                                    <div class="fw-600 text-dark" style="font-size: 0.85rem;">{{ $rec->part_number }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $rec->part_name }}</div>
                                </td>
                                <td class="td-cell">
                                    <div style="font-size: 0.82rem; color: #334155;">
                                        {{ \Carbon\Carbon::parse($rec->planned_date)->format('d-m-Y') }}
                                    </div>
                                </td>
                                <td class="td-cell">
                                    <span class="badge-soft badge-primary">{{ $rec->shift_name }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary">{{ number_format($plan) }}</span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-{{ $color }}">{{ number_format($prod) }}</span>
                                </td>
                                <td class="td-cell">
                                    <span style="font-size: 0.82rem; color: #475569; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $rec->production_start ? \Carbon\Carbon::parse($rec->production_start)->format('H:i') : '—' }}
                                    </span>
                                </td>
                                <td class="td-cell">
                                    <span style="font-size: 0.82rem; color: #475569; font-family: 'SFMono-Regular', Consolas, monospace;">
                                        {{ $rec->production_end ? \Carbon\Carbon::parse($rec->production_end)->format('H:i') : '—' }}
                                    </span>
                                </td>
                                <td class="td-cell text-center">
                                    @if ($rec->production_start && $rec->production_end)
                                        @php
                                            $minutes = (int) \Carbon\Carbon::parse($rec->production_start)
                                                ->diffInMinutes(\Carbon\Carbon::parse($rec->production_end));
                                        @endphp
                                        <span class="badge-soft badge-secondary" style="font-family: 'SFMono-Regular', Consolas, monospace;">
                                            {{ $minutes }} min
                                        </span>
                                    @else
                                        <span style="color: #cbd5e1; font-size: 0.82rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                                        <p class="mb-1 fw-500" style="color: #475569;">No se encontraron registros</p>
                                        <small>Intenta ajustar los filtros de búsqueda</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($productionRecords->count() > 0)
                        <tfoot style="background: #f8fafc; border-top: 2px solid #e2e8f0; position: sticky; bottom: 0; z-index: 5;">
                            <tr>
                                <td colspan="4" class="td-cell text-right fw-600" style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em;">
                                    Totales
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-secondary fw-600">
                                        {{ number_format($productionRecords->sum('planned_quantity')) }}
                                    </span>
                                </td>
                                <td class="td-cell text-center">
                                    <span class="badge-soft badge-primary fw-600">
                                        {{ number_format($productionRecords->sum('produced_quantity')) }}
                                    </span>
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .card, .btn, .form-control, .table, .content-header h1 {
            font-family: 'Inter', sans-serif !important;
        }

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
        .btn-action-success {
            background: #f0fdf4;
            color: #15803d;
            border-color: #86efac;
        }
        .btn-action-success:hover {
            background: #dcfce7;
            color: #166534;
            text-decoration: none;
        }

        /* ── Filtros ── */
        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
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
        .filter-icon {
            color: #94a3b8;
            font-size: 0.75rem;
            margin-right: 0.45rem;
        }
        .filter-input {
            border: none;
            background: transparent;
            font-size: 0.82rem;
            color: #334155;
            outline: none;
            height: 100%;
            font-family: 'Inter', sans-serif;
        }
        .filter-input::placeholder { color: #94a3b8; }
        .filter-input option { color: #334155; }
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
        .btn-filter-clear:hover {
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
        }

        /* ── Multi-select dropdown ── */
        .ms-dropdown {
            position: relative;
        }
        .ms-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 0 0.6rem;
            min-height: 34px;
            cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
            gap: 0.4rem;
            user-select: none;
        }
        .ms-trigger.open,
        .ms-trigger:focus-within {
            border-color: #93c5fd;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
        }
        .ms-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
            padding: 3px 0;
            flex: 1;
            min-width: 0;
        }
        .ms-placeholder {
            font-size: 0.82rem;
            color: #94a3b8;
            font-family: 'Inter', sans-serif;
            line-height: 1;
        }
        .ms-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 1px 6px;
            white-space: nowrap;
        }
        .ms-chip-remove {
            cursor: pointer;
            color: #93c5fd;
            font-size: 0.65rem;
            line-height: 1;
        }
        .ms-chip-remove:hover { color: #1d4ed8; }
        .ms-arrow {
            font-size: 0.65rem;
            color: #94a3b8;
            transition: transform 0.2s;
            flex-shrink: 0;
        }
        .ms-arrow.rotated { transform: rotate(180deg); }
        .ms-panel {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            z-index: 999;
            overflow: hidden;
        }
        .ms-panel.open { display: block; }
        .ms-search-wrap {
            display: flex;
            align-items: center;
            padding: 0.4rem 0.6rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 0.4rem;
        }
        .ms-search-icon { font-size: 0.72rem; color: #94a3b8; }
        .ms-search {
            border: none;
            outline: none;
            font-size: 0.8rem;
            color: #334155;
            width: 100%;
            font-family: 'Inter', sans-serif;
        }
        .ms-search::placeholder { color: #94a3b8; }
        .ms-options {
            max-height: 200px;
            overflow-y: auto;
            padding: 0.25rem 0;
        }
        .ms-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.7rem;
            cursor: pointer;
            font-size: 0.82rem;
            color: #334155;
            margin: 0;
            font-weight: 400;
            transition: background 0.1s;
        }
        .ms-option:hover { background: #f8fafc; }
        .ms-option.ms-selected { background: #eff6ff; color: #1d4ed8; }
        .ms-option.ms-hidden { display: none; }
        .ms-checkbox {
            width: 16px;
            height: 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.12s;
        }
        .ms-option.ms-selected .ms-checkbox {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }
        .ms-check-icon {
            font-size: 0.55rem;
            color: #fff;
            display: none;
        }
        .ms-option.ms-selected .ms-check-icon { display: block; }
        .ms-option-text { flex: 1; }

        /* ── Tabla ── */
        .table-head-row {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
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
        .td-row {
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background 0.1s ease;
        }
        .td-row:hover { background-color: #f8fafc !important; }
        .td-cell {
            padding: 0.5rem 0.85rem !important;
            vertical-align: middle !important;
            border-top: none !important;
        }

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

        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }

        @media (max-width: 767px) {
            .filter-field { width: 100%; }
            .card-header form > div { flex-direction: column; }
            .filter-group { width: 100%; }
        }
    </style>
@stop

@section('js')
    <script>
        (function () {
            const trigger   = document.getElementById('ms-trigger');
            const panel     = document.getElementById('ms-panel');
            const arrow     = document.getElementById('ms-arrow');
            const chips     = document.getElementById('ms-chips');
            const placeholder = document.getElementById('ms-placeholder');
            const search    = document.getElementById('ms-search');
            const options   = document.querySelectorAll('#ms-options .ms-option');

            function renderChips() {
                const selected = [...options].filter(o => o.classList.contains('ms-selected'));
                chips.querySelectorAll('.ms-chip').forEach(c => c.remove());

                if (selected.length === 0) {
                    placeholder.style.display = '';
                } else {
                    placeholder.style.display = 'none';
                    selected.forEach(opt => {
                        const chip = document.createElement('span');
                        chip.className = 'ms-chip';
                        chip.innerHTML = `${opt.dataset.label}<span class="ms-chip-remove" data-value="${opt.dataset.value}">&#x2715;</span>`;
                        chip.querySelector('.ms-chip-remove').addEventListener('click', e => {
                            e.stopPropagation();
                            deselect(opt.dataset.value);
                        });
                        chips.appendChild(chip);
                    });
                }
            }

            function deselect(value) {
                const opt = [...options].find(o => o.dataset.value === value);
                if (!opt) return;
                opt.classList.remove('ms-selected');
                opt.querySelector('input[type=checkbox]').checked = false;
                renderChips();
            }

            options.forEach(opt => {
                opt.addEventListener('click', e => {
                    e.preventDefault();
                    opt.classList.toggle('ms-selected');
                    opt.querySelector('input[type=checkbox]').checked = opt.classList.contains('ms-selected');
                    renderChips();
                });
            });

            trigger.addEventListener('click', () => {
                const open = panel.classList.toggle('open');
                trigger.classList.toggle('open', open);
                arrow.classList.toggle('rotated', open);
                if (open) { search.focus(); search.value = ''; filterOptions(''); }
            });

            search.addEventListener('input', () => filterOptions(search.value));

            function filterOptions(q) {
                const term = q.toLowerCase();
                options.forEach(opt => {
                    opt.classList.toggle('ms-hidden', !opt.dataset.label.toLowerCase().includes(term));
                });
            }

            document.addEventListener('click', e => {
                if (!document.getElementById('ms-dropdown').contains(e.target)) {
                    panel.classList.remove('open');
                    trigger.classList.remove('open');
                    arrow.classList.remove('rotated');
                }
            });

            renderChips();
        })();
    </script>
@stop
