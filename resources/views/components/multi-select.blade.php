@props([
    'name',
    'label',
    'options' => [],
    'placeholder' => 'Todas las opciones',
    'searchPlaceholder' => 'Buscar...',
    'emptyMessage' => 'No hay opciones disponibles.',
])

@php
    $uid = 'ms-' . \Illuminate\Support\Str::slug($name);
@endphp

<div {{ $attributes->merge(['class' => 'filter-field']) }}>
    <label class="filter-label-text" id="{{ $uid }}-label">{{ $label }}</label>

    @if (count($options) > 0)
        <div class="ms-dropdown" id="{{ $uid }}-dropdown">
            <div class="ms-trigger" id="{{ $uid }}-trigger" role="button" tabindex="0"
                aria-labelledby="{{ $uid }}-label" aria-haspopup="listbox">
                <div class="ms-chips" id="{{ $uid }}-chips">
                    <span class="ms-placeholder" id="{{ $uid }}-placeholder">{{ $placeholder }}</span>
                </div>
                <i class="fas fa-chevron-down ms-arrow" id="{{ $uid }}-arrow" aria-hidden="true"></i>
            </div>
            <div class="ms-panel" id="{{ $uid }}-panel">
                <div class="ms-search-wrap">
                    <i class="fas fa-search ms-search-icon" aria-hidden="true"></i>
                    <input type="text" class="ms-search" id="{{ $uid }}-search"
                        placeholder="{{ $searchPlaceholder }}">
                </div>
                <div class="ms-options" id="{{ $uid }}-options">
                    @foreach ($options as $option)
                        @php $selected = !empty($option['selected']); @endphp
                        <label class="ms-option {{ $selected ? 'ms-selected' : '' }}"
                            data-value="{{ $option['value'] }}"
                            data-label="{{ $option['label'] }}">
                            <span class="ms-checkbox">
                                <i class="fas fa-check ms-check-icon" aria-hidden="true"></i>
                            </span>
                            <span class="ms-option-text">{{ $option['label'] }}</span>
                            <input type="checkbox" name="{{ $name }}[]"
                                value="{{ $option['value'] }}"
                                {{ $selected ? 'checked' : '' }}
                                style="display:none;">
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <script>
            (function () {
                const uid = {!! json_encode($uid) !!};
                const trigger     = document.getElementById(uid + '-trigger');
                const panel       = document.getElementById(uid + '-panel');
                const arrow       = document.getElementById(uid + '-arrow');
                const chips       = document.getElementById(uid + '-chips');
                const placeholder = document.getElementById(uid + '-placeholder');
                const search      = document.getElementById(uid + '-search');
                const options     = document.querySelectorAll('#' + uid + '-options .ms-option');
                const dropdown    = document.getElementById(uid + '-dropdown');

                // El panel se saca de su contenedor y se cuelga de <body> en posición
                // fixed; así nunca lo recorta un ancestro con overflow:hidden (p.ej. las
                // tarjetas .rounded-4.overflow-hidden que se usan en todo el panel admin).
                function positionPanel() {
                    const rect = trigger.getBoundingClientRect();
                    panel.style.position = 'fixed';
                    panel.style.left = rect.left + 'px';
                    panel.style.top = (rect.bottom + 4) + 'px';
                    panel.style.width = rect.width + 'px';
                    panel.style.minWidth = rect.width + 'px';
                }

                function onViewportChange(e) {
                    // Ignorar el scroll interno de la lista de opciones (.ms-options);
                    // solo cerrar cuando el scroll ocurre fuera del panel (p.ej. la página).
                    if (panel.contains(e.target)) return;
                    closePanel();
                }

                function openPanel() {
                    document.body.appendChild(panel);
                    positionPanel();
                    panel.classList.add('open');
                    trigger.classList.add('open');
                    arrow.classList.add('rotated');
                    search.focus();
                    search.value = '';
                    filterOptions('');
                    window.addEventListener('scroll', onViewportChange, true);
                    window.addEventListener('resize', onViewportChange);
                }

                function closePanel() {
                    panel.classList.remove('open');
                    trigger.classList.remove('open');
                    arrow.classList.remove('rotated');
                    window.removeEventListener('scroll', onViewportChange, true);
                    window.removeEventListener('resize', onViewportChange);
                }

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

                function toggleTrigger() {
                    panel.classList.contains('open') ? closePanel() : openPanel();
                }

                trigger.addEventListener('click', toggleTrigger);
                trigger.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleTrigger();
                    }
                });

                search.addEventListener('input', () => filterOptions(search.value));

                function filterOptions(q) {
                    const term = q.toLowerCase();
                    options.forEach(opt => {
                        opt.classList.toggle('ms-hidden', !opt.dataset.label.toLowerCase().includes(term));
                    });
                }

                document.addEventListener('click', e => {
                    if (!dropdown.contains(e.target) && !panel.contains(e.target)) {
                        closePanel();
                    }
                });

                renderChips();
            })();
        </script>
    @else
        <div class="text-muted small mb-0">{{ $emptyMessage }}</div>
    @endif
</div>
