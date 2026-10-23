<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-zinc-950">
        <x-guest-header />

        <main class="flex-1 min-h-0 flex flex-col p-3 sm:p-4">
            {{-- Catálogos de los filtros: se entregan una vez aquí para que no viajen en cada refresco del componente --}}
            <script>window.lineProgressCatalog = @json(\App\Livewire\Guest\LineProgress::catalog());</script>

            {{--
                Filtros guardados en este navegador: se aplican ANTES de pintar la tabla, redirigiendo a la
                URL con ?lines= (inicio) o ?wcs= (estaciones de una línea). Antes se recuperaban después de
                cargar y se veía todo por un momento. Si la URL ya trae el filtro, manda el de la URL.
            --}}
            @php $filterParam = isset($wcId) ? null : (isset($lineId) ? 'wcs' : 'lines'); @endphp
            @if($filterParam)
                <script>
                    (function () {
                        const param = @js($filterParam);
                        const params = new URLSearchParams(window.location.search);
                        if ([...params.keys()].some(k => k === param || k.startsWith(param + '['))) return;

                        try {
                            const catalog = window.lineProgressCatalog;
                            const list = param === 'lines' ? Object.values(catalog.lines) : catalog.workCenters.filter(w => w.line_id === @js($lineId ?? null));
                            const ids = JSON.parse(localStorage.getItem('lineProgress.' + param) || '[]')
                                .filter(id => list.some(item => item.id === id));
                            if (!ids.length) return;

                            ids.forEach((id, i) => params.append(`${param}[${i}]`, id));
                            window.location.replace(window.location.pathname + '?' + params.toString());
                        } catch (e) { /* almacenamiento no disponible: se muestra todo */ }
                    })();
                </script>
            @endif

            <livewire:guest.line-progress :line-id="$lineId ?? null" :wc-id="$wcId ?? null" />
        </main>
    </div>
</x-guest-layout>
