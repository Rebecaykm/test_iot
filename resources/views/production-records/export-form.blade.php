@extends('adminlte::page')

@section('title', 'Reporte de Producción')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Reporte de Producción</h1>
        </div>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body" style="padding: 1.75rem;">
            <form action="{{ route('production.export-pdf-filtered') }}" method="POST" target="_blank" id="exportForm">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-field">
                            <label class="form-label-text">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date"
                                class="form-input @error('start_date') is-invalid @enderror"
                                value="{{ old('start_date') }}" required>
                            @error('start_date')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-field">
                            <label class="form-label-text">Fecha de Fin <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date"
                                class="form-input @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date') }}" required>
                            @error('end_date')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-field">
                            <label class="form-label-text">Líneas</label>
                            <div class="ms-dropdown" id="ms-lines">
                                <div class="ms-trigger" id="ms-lines-trigger">
                                    <div class="ms-chips" id="ms-lines-chips">
                                        <span class="ms-placeholder" id="ms-lines-placeholder">Todas las líneas</span>
                                    </div>
                                    <i class="fas fa-chevron-down ms-arrow" id="ms-lines-arrow"></i>
                                </div>
                                <div class="ms-panel" id="ms-lines-panel">
                                    <div class="ms-search-wrap">
                                        <i class="fas fa-search ms-search-icon"></i>
                                        <input type="text" class="ms-search" id="ms-lines-search" placeholder="Buscar línea...">
                                    </div>
                                    <div class="ms-options" id="ms-lines-options">
                                        @foreach ($lines as $line)
                                            @php $sel = in_array($line->id, old('lines', [])); @endphp
                                            <label class="ms-option {{ $sel ? 'ms-selected' : '' }}"
                                                data-value="{{ $line->id }}"
                                                data-label="{{ $line->name }}">
                                                <span class="ms-checkbox">
                                                    <i class="fas fa-check ms-check-icon"></i>
                                                </span>
                                                <span class="ms-option-text">{{ $line->name }}</span>
                                                <input type="checkbox" name="lines[]" value="{{ $line->id }}"
                                                    {{ $sel ? 'checked' : '' }} style="display:none;">
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('lines')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-field">
                            <label class="form-label-text">Estaciones</label>
                            <div class="ms-dropdown" id="ms-workcenters">
                                <div class="ms-trigger" id="ms-workcenters-trigger">
                                    <div class="ms-chips" id="ms-workcenters-chips">
                                        <span class="ms-placeholder" id="ms-workcenters-placeholder">Todas las estaciones</span>
                                    </div>
                                    <i class="fas fa-chevron-down ms-arrow" id="ms-workcenters-arrow"></i>
                                </div>
                                <div class="ms-panel" id="ms-workcenters-panel">
                                    <div class="ms-search-wrap">
                                        <i class="fas fa-search ms-search-icon"></i>
                                        <input type="text" class="ms-search" id="ms-workcenters-search" placeholder="Buscar estación...">
                                    </div>
                                    <div class="ms-options" id="ms-workcenters-options">
                                        @foreach ($workCenters as $wc)
                                            @php $sel = in_array($wc->id, old('work_centers', [])); @endphp
                                            <label class="ms-option {{ $sel ? 'ms-selected' : '' }}"
                                                data-value="{{ $wc->id }}"
                                                data-label="{{ $wc->number }} – {{ $wc->name }}">
                                                <span class="ms-checkbox">
                                                    <i class="fas fa-check ms-check-icon"></i>
                                                </span>
                                                <span class="ms-option-text">{{ $wc->number }} – {{ $wc->name }}</span>
                                                <input type="checkbox" name="work_centers[]" value="{{ $wc->id }}"
                                                    {{ $sel ? 'checked' : '' }} style="display:none;">
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('work_centers')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3" style="gap: 0.5rem;">
                    <a href="{{ route('production-records.index') }}" class="form-btn form-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Regresar
                    </a>
                    <button type="submit" class="form-btn form-btn-danger">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .card, .btn, .form-control, h1 { font-family: 'Inter', sans-serif !important; }

        .form-field { margin-bottom: 1.25rem; }
        .form-label-text {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }
        .form-input {
            display: block;
            width: 100%;
            padding: 0.55rem 0.75rem;
            font-size: 0.875rem;
            color: #334155;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-input:focus {
            border-color: #93c5fd;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
            outline: none;
        }
        .form-error { font-size: 0.78rem; color: #b91c1c; margin-top: 0.25rem; display: block; }

        /* ── Multi-select dropdown ── */
        .ms-dropdown { position: relative; }
        .ms-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 0.4rem 0.75rem;
            min-height: 38px;
            cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
            gap: 0.4rem;
            user-select: none;
        }
        .ms-trigger.open {
            border-color: #93c5fd;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
        }
        .ms-chips { display: flex; flex-wrap: wrap; gap: 3px; flex: 1; min-width: 0; padding: 1px 0; }
        .ms-placeholder { font-size: 0.875rem; color: #94a3b8; font-family: 'Inter', sans-serif; }
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
        .ms-chip-remove { cursor: pointer; color: #93c5fd; font-size: 0.65rem; }
        .ms-chip-remove:hover { color: #1d4ed8; }
        .ms-arrow { font-size: 0.65rem; color: #94a3b8; transition: transform 0.2s; flex-shrink: 0; }
        .ms-arrow.rotated { transform: rotate(180deg); }
        .ms-panel {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0; right: 0;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            z-index: 999;
            overflow: hidden;
        }
        .ms-panel.open { display: block; }
        .ms-search-wrap { display: flex; align-items: center; padding: 0.4rem 0.6rem; border-bottom: 1px solid #f1f5f9; gap: 0.4rem; }
        .ms-search-icon { font-size: 0.72rem; color: #94a3b8; }
        .ms-search { border: none; outline: none; font-size: 0.8rem; color: #334155; width: 100%; font-family: 'Inter', sans-serif; }
        .ms-search::placeholder { color: #94a3b8; }
        .ms-options { max-height: 200px; overflow-y: auto; padding: 0.25rem 0; }
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
        .ms-checkbox { width: 16px; height: 16px; border: 1.5px solid #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.12s; }
        .ms-option.ms-selected .ms-checkbox { background: #1d4ed8; border-color: #1d4ed8; }
        .ms-check-icon { font-size: 0.55rem; color: #fff; display: none; }
        .ms-option.ms-selected .ms-check-icon { display: block; }
        .ms-option-text { flex: 1; }

        /* ── Botones ── */
        .form-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1.1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 7px;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        .form-btn-secondary { background: transparent; color: #64748b; border-color: #e2e8f0; }
        .form-btn-secondary:hover { background: #f1f5f9; color: #475569; text-decoration: none; }
        .form-btn-danger { background: #fef2f2; color: #b91c1c; border-color: #fca5a5; }
        .form-btn-danger:hover { background: #fee2e2; color: #991b1b; text-decoration: none; }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function initMultiSelect(dropdownId, triggerId, chipsId, placeholderId, panelId, arrowId, searchId, optionsSelector) {
            const dropdown = document.getElementById(dropdownId);
            const trigger  = document.getElementById(triggerId);
            const chips    = document.getElementById(chipsId);
            const placeholder = document.getElementById(placeholderId);
            const panel    = document.getElementById(panelId);
            const arrow    = document.getElementById(arrowId);
            const search   = document.getElementById(searchId);
            const options  = dropdown.querySelectorAll('.ms-option');

            function renderChips() {
                chips.querySelectorAll('.ms-chip').forEach(c => c.remove());
                const selected = [...options].filter(o => o.classList.contains('ms-selected'));
                placeholder.style.display = selected.length ? 'none' : '';
                selected.forEach(opt => {
                    const chip = document.createElement('span');
                    chip.className = 'ms-chip';
                    chip.innerHTML = `${opt.dataset.label}<span class="ms-chip-remove" data-value="${opt.dataset.value}">&#x2715;</span>`;
                    chip.querySelector('.ms-chip-remove').addEventListener('click', e => {
                        e.stopPropagation();
                        const o = [...options].find(x => x.dataset.value == e.target.dataset.value);
                        if (o) { o.classList.remove('ms-selected'); o.querySelector('input').checked = false; }
                        renderChips();
                    });
                    chips.appendChild(chip);
                });
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
                if (open) { search.focus(); search.value = ''; filterOpts(''); }
            });

            search.addEventListener('input', () => filterOpts(search.value));

            function filterOpts(q) {
                const term = q.toLowerCase();
                options.forEach(o => o.classList.toggle('ms-hidden', !o.dataset.label.toLowerCase().includes(term)));
            }

            document.addEventListener('click', e => {
                if (!dropdown.contains(e.target)) {
                    panel.classList.remove('open');
                    trigger.classList.remove('open');
                    arrow.classList.remove('rotated');
                }
            });

            renderChips();
        }

        document.addEventListener('DOMContentLoaded', () => {
            initMultiSelect('ms-lines', 'ms-lines-trigger', 'ms-lines-chips', 'ms-lines-placeholder',
                'ms-lines-panel', 'ms-lines-arrow', 'ms-lines-search');
            initMultiSelect('ms-workcenters', 'ms-workcenters-trigger', 'ms-workcenters-chips', 'ms-workcenters-placeholder',
                'ms-workcenters-panel', 'ms-workcenters-arrow', 'ms-workcenters-search');

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').setAttribute('max', today);
            document.getElementById('end_date').setAttribute('max', today);

            document.getElementById('exportForm').addEventListener('submit', function (e) {
                const start = document.getElementById('start_date').value;
                const end   = document.getElementById('end_date').value;

                if (!start || !end) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Complete las fechas de inicio y fin.', confirmButtonColor: '#1d4ed8' });
                    return;
                }
                if (end < start) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Fecha incorrecta', text: 'La fecha de fin no puede ser anterior a la de inicio.', confirmButtonColor: '#1d4ed8' })
                        .then(() => { document.getElementById('end_date').value = start; document.getElementById('end_date').focus(); });
                    return;
                }

                const linesChecked = document.querySelectorAll('#ms-lines input[type=checkbox]:checked').length;
                const wcChecked    = document.querySelectorAll('#ms-workcenters input[type=checkbox]:checked').length;
                if (!linesChecked && !wcChecked) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Selección requerida', text: 'Seleccione al menos una línea o una estación.', confirmButtonColor: '#1d4ed8' });
                }
            });
        });
    </script>
@stop
