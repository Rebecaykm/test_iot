@extends('adminlte::page')

@section('title', 'Scrap')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.4rem;">Registro de Scrap</h1>
        </div>
    </div>
@stop

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body" style="padding: 1.75rem;">
            <form action="{{ route('scrap-records.store') }}" method="POST" id="scrapForm">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-field">
                            <label class="form-label-text">Número de Parte <span class="text-danger">*</span></label>
                            <div class="ss-dropdown" id="ss-part-number">
                                <div class="ss-trigger">
                                    <span class="ss-value ss-placeholder">Seleccione un número de parte...</span>
                                    <i class="fas fa-chevron-down ss-arrow"></i>
                                </div>
                                <div class="ss-panel">
                                    <div class="ss-search-wrap">
                                        <i class="fas fa-search ss-search-icon"></i>
                                        <input type="text" class="ss-search" placeholder="Buscar...">
                                    </div>
                                    <div class="ss-options">
                                        @foreach ($partNumbers as $partNumber)
                                            <div class="ss-option {{ old('part_number_id') == $partNumber->id ? 'ss-selected' : '' }}"
                                                data-value="{{ $partNumber->id }}">
                                                {{ $partNumber->number }} – {{ $partNumber->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <select name="part_number_id" id="part_number_id" style="display:none;">
                                <option value="">Seleccione...</option>
                                @foreach ($partNumbers as $partNumber)
                                    <option value="{{ $partNumber->id }}"
                                        {{ old('part_number_id') == $partNumber->id ? 'selected' : '' }}>
                                        {{ $partNumber->number }} – {{ $partNumber->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('part_number_id')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-field">
                            <label class="form-label-text">Tipo de Scrap <span class="text-danger">*</span></label>
                            <div class="ss-dropdown" id="ss-scrap">
                                <div class="ss-trigger">
                                    <span class="ss-value ss-placeholder">Seleccione un tipo de scrap...</span>
                                    <i class="fas fa-chevron-down ss-arrow"></i>
                                </div>
                                <div class="ss-panel">
                                    <div class="ss-search-wrap">
                                        <i class="fas fa-search ss-search-icon"></i>
                                        <input type="text" class="ss-search" placeholder="Buscar...">
                                    </div>
                                    <div class="ss-options">
                                        @foreach ($scraps as $scrap)
                                            <div class="ss-option {{ old('scrap_id') == $scrap->id ? 'ss-selected' : '' }}"
                                                data-value="{{ $scrap->id }}">
                                                {{ $scrap->code }} – {{ $scrap->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <select name="scrap_id" id="scrap_id" style="display:none;">
                                <option value="">Seleccione...</option>
                                @foreach ($scraps as $scrap)
                                    <option value="{{ $scrap->id }}"
                                        {{ old('scrap_id') == $scrap->id ? 'selected' : '' }}>
                                        {{ $scrap->code }} – {{ $scrap->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('scrap_id')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-field">
                            <label class="form-label-text">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="0.01" id="quantity" name="quantity"
                                class="form-input @error('quantity') is-invalid @enderror"
                                value="{{ old('quantity') }}"
                                placeholder="Ingrese la cantidad de scrap" required>
                            @error('quantity')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3" style="gap: 0.5rem;">
                    <a href="{{ $source == 'production-records.index' ? route('production-records.index') : route('scrap-records.index') }}"
                        class="form-btn form-btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="form-btn form-btn-danger">
                        <i class="fas fa-save"></i> Guardar Registro
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
        .form-input::placeholder { color: #94a3b8; }
        .form-error { font-size: 0.78rem; color: #b91c1c; margin-top: 0.25rem; display: block; }

        /* ── Single select dropdown ── */
        .ss-dropdown { position: relative; }
        .ss-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 0.55rem 0.75rem;
            cursor: pointer;
            transition: border-color 0.15s, box-shadow 0.15s;
            gap: 0.5rem;
            user-select: none;
        }
        .ss-trigger.open {
            border-color: #93c5fd;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
        }
        .ss-value { font-size: 0.875rem; color: #334155; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ss-placeholder { color: #94a3b8; }
        .ss-arrow { font-size: 0.65rem; color: #94a3b8; transition: transform 0.2s; flex-shrink: 0; }
        .ss-arrow.rotated { transform: rotate(180deg); }
        .ss-panel {
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
        .ss-panel.open { display: block; }
        .ss-search-wrap {
            display: flex;
            align-items: center;
            padding: 0.4rem 0.6rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 0.4rem;
        }
        .ss-search-icon { font-size: 0.72rem; color: #94a3b8; }
        .ss-search { border: none; outline: none; font-size: 0.8rem; color: #334155; width: 100%; font-family: 'Inter', sans-serif; }
        .ss-search::placeholder { color: #94a3b8; }
        .ss-options { max-height: 200px; overflow-y: auto; padding: 0.25rem 0; }
        .ss-option {
            padding: 0.42rem 0.75rem;
            font-size: 0.82rem;
            color: #334155;
            cursor: pointer;
            transition: background 0.1s;
        }
        .ss-option:hover { background: #f8fafc; }
        .ss-option.ss-selected { background: #eff6ff; color: #1d4ed8; font-weight: 600; }
        .ss-option.ss-hidden { display: none; }

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
        function initSingleSelect(wrapperId, selectId) {
            const wrapper = document.getElementById(wrapperId);
            const select  = document.getElementById(selectId);
            const trigger = wrapper.querySelector('.ss-trigger');
            const value   = wrapper.querySelector('.ss-value');
            const panel   = wrapper.querySelector('.ss-panel');
            const arrow   = wrapper.querySelector('.ss-arrow');
            const options = wrapper.querySelectorAll('.ss-option');
            const search  = wrapper.querySelector('.ss-search');

            function syncDisplay() {
                const sel = select.options[select.selectedIndex];
                if (sel && sel.value) {
                    value.textContent = sel.text;
                    value.classList.remove('ss-placeholder');
                } else {
                    value.textContent = value.dataset.placeholder;
                    value.classList.add('ss-placeholder');
                }
                options.forEach(o => o.classList.toggle('ss-selected', o.dataset.value == select.value));
            }

            value.dataset.placeholder = value.textContent;
            syncDisplay();

            trigger.addEventListener('click', () => {
                const open = panel.classList.toggle('open');
                trigger.classList.toggle('open', open);
                arrow.classList.toggle('rotated', open);
                if (open) { search.focus(); search.value = ''; filterOpts(''); }
            });

            options.forEach(opt => {
                opt.addEventListener('click', () => {
                    select.value = opt.dataset.value;
                    syncDisplay();
                    panel.classList.remove('open');
                    trigger.classList.remove('open');
                    arrow.classList.remove('rotated');
                });
            });

            search.addEventListener('input', () => filterOpts(search.value));

            function filterOpts(q) {
                const term = q.toLowerCase();
                options.forEach(o => o.classList.toggle('ss-hidden', !o.textContent.toLowerCase().includes(term)));
            }

            document.addEventListener('click', e => {
                if (!wrapper.contains(e.target)) {
                    panel.classList.remove('open');
                    trigger.classList.remove('open');
                    arrow.classList.remove('rotated');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initSingleSelect('ss-part-number', 'part_number_id');
            initSingleSelect('ss-scrap', 'scrap_id');

            document.getElementById('quantity').addEventListener('input', function () {
                if (this.value < 0) this.value = 0;
            });

            document.getElementById('scrapForm').addEventListener('submit', function (e) {
                if (!document.getElementById('part_number_id').value) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Seleccione un número de parte.', confirmButtonColor: '#1d4ed8' });
                    return;
                }
                if (!document.getElementById('scrap_id').value) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Seleccione un tipo de scrap.', confirmButtonColor: '#1d4ed8' });
                    return;
                }
                const qty = parseFloat(document.getElementById('quantity').value);
                if (qty > 100) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Confirmar cantidad?',
                        text: `La cantidad ingresada es ${qty}. ¿Es correcto?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#1d4ed8',
                        cancelButtonColor: '#e2e8f0',
                        confirmButtonText: 'Sí, es correcto',
                        cancelButtonText: 'Cancelar'
                    }).then(result => { if (result.isConfirmed) this.submit(); });
                }
            });
        });
    </script>
@stop
