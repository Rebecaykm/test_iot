@props([
    'name',
    'label',
    'options' => [],
    'searchThreshold' => 6,
])

@php
    $uid = 'ss-' . \Illuminate\Support\Str::slug($name);
    $selectedOption = collect($options)->first(fn ($o) => !empty($o['selected'])) ?? ($options[0] ?? null);
@endphp

<div {{ $attributes->merge(['class' => 'filter-field']) }}>
    <label class="filter-label-text" id="{{ $uid }}-label">{{ $label }}</label>

    <div class="ms-dropdown" id="{{ $uid }}-dropdown">
        <div class="ms-trigger" id="{{ $uid }}-trigger" role="button" tabindex="0"
            aria-labelledby="{{ $uid }}-label" aria-haspopup="listbox" aria-expanded="false">
            <span class="ss-value" id="{{ $uid }}-value">
                @if (!empty($selectedOption['swatch']))
                    <span class="ms-color-swatch" style="background-color: {{ $selectedOption['swatch'] }}"></span>
                @endif
                {{ $selectedOption['label'] ?? '' }}
            </span>
            <i class="fas fa-chevron-down ms-arrow" id="{{ $uid }}-arrow" aria-hidden="true"></i>
        </div>
        <div class="ms-panel" id="{{ $uid }}-panel">
            @if (count($options) > $searchThreshold)
                <div class="ms-search-wrap">
                    <i class="fas fa-search ms-search-icon" aria-hidden="true"></i>
                    <input type="text" class="ms-search" id="{{ $uid }}-search" placeholder="Buscar...">
                </div>
            @endif
            <div class="ms-options" id="{{ $uid }}-options">
                @foreach ($options as $option)
                    @php $selected = !empty($option['selected']); @endphp
                    <div class="ms-option {{ $selected ? 'ms-selected' : '' }}"
                        data-value="{{ $option['value'] }}"
                        data-label="{{ $option['label'] }}"
                        @if (!empty($option['swatch'])) data-swatch="{{ $option['swatch'] }}" @endif>
                        <span class="ms-checkbox">
                            <i class="fas fa-check ms-check-icon" aria-hidden="true"></i>
                        </span>
                        @if (!empty($option['swatch']))
                            <span class="ms-color-swatch" style="background-color: {{ $option['swatch'] }}"></span>
                        @endif
                        <span class="ms-option-text">{{ $option['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <select name="{{ $name }}" id="{{ $uid }}-native" class="d-none" tabindex="-1" aria-hidden="true">
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}" {{ !empty($option['selected']) ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
    </div>

    <script>
        (function () {
            const uid      = {!! json_encode($uid) !!};
            const trigger  = document.getElementById(uid + '-trigger');
            const panel    = document.getElementById(uid + '-panel');
            const arrow    = document.getElementById(uid + '-arrow');
            const valueEl  = document.getElementById(uid + '-value');
            const search   = document.getElementById(uid + '-search');
            const native   = document.getElementById(uid + '-native');
            const options  = document.querySelectorAll('#' + uid + '-options .ms-option');
            const dropdown = document.getElementById(uid + '-dropdown');

            function selectOption(opt) {
                options.forEach(o => o.classList.remove('ms-selected'));
                opt.classList.add('ms-selected');
                valueEl.innerHTML = '';
                if (opt.dataset.swatch) {
                    const swatch = document.createElement('span');
                    swatch.className = 'ms-color-swatch';
                    swatch.style.backgroundColor = opt.dataset.swatch;
                    valueEl.appendChild(swatch);
                }
                valueEl.appendChild(document.createTextNode(opt.dataset.label));
                native.value = opt.dataset.value;
                native.dispatchEvent(new Event('change', { bubbles: true }));
                close();
            }

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
                close();
            }

            function open_() {
                document.body.appendChild(panel);
                positionPanel();
                panel.classList.add('open');
                trigger.classList.add('open');
                arrow.classList.add('rotated');
                trigger.setAttribute('aria-expanded', 'true');
                if (search) { search.focus(); search.value = ''; filterOptions(''); }
                window.addEventListener('scroll', onViewportChange, true);
                window.addEventListener('resize', onViewportChange);
            }

            function close() {
                panel.classList.remove('open');
                trigger.classList.remove('open');
                arrow.classList.remove('rotated');
                trigger.setAttribute('aria-expanded', 'false');
                window.removeEventListener('scroll', onViewportChange, true);
                window.removeEventListener('resize', onViewportChange);
            }

            function toggle() {
                panel.classList.contains('open') ? close() : open_();
            }

            trigger.addEventListener('click', toggle);
            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
                if (e.key === 'Escape') close();
            });

            options.forEach(opt => opt.addEventListener('click', () => selectOption(opt)));

            if (search) {
                search.addEventListener('input', () => filterOptions(search.value));
            }

            function filterOptions(q) {
                const term = q.toLowerCase();
                options.forEach(opt => {
                    opt.classList.toggle('ms-hidden', !opt.dataset.label.toLowerCase().includes(term));
                });
            }

            document.addEventListener('click', e => {
                if (!dropdown.contains(e.target) && !panel.contains(e.target)) close();
            });
        })();
    </script>
</div>
