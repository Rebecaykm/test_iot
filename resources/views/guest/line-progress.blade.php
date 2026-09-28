<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-zinc-950">
        <x-guest-header />

        <main class="flex-1 min-h-0 flex flex-col p-3 sm:p-4">
            {{-- Catálogos de los filtros: se entregan una vez aquí para que no viajen en cada refresco del componente --}}
            <script>window.lineProgressCatalog = @json(\App\Livewire\Guest\LineProgress::catalog());</script>

            {{--
                Filtros guardados en este navegador: se aplican ANTES de pintar la tabla, redirigiendo a la
                URL con ?lines= / ?wcs=. Antes se recuperaban después de cargar y se veía toda la planta
                por un momento. Si la URL ya trae filtros, mandan los de la URL.
            --}}
            <script>
                (function () {
                    const params = new URLSearchParams(window.location.search);
                    const inUrl = name => [...params.keys()].some(k => k === name || k.startsWith(name + '['));
                    if (inUrl('lines') || inUrl('wcs')) return;

                    try {
                        const catalog = window.lineProgressCatalog;
                        const stored = (key, list) => JSON.parse(localStorage.getItem(key) || '[]')
                            .filter(id => list.some(item => item.id === id));
                        const lines = stored('lineProgress.lines', catalog.lines);
                        const wcs = stored('lineProgress.wcs', catalog.workCenters);
                        if (!lines.length && !wcs.length) return;

                        lines.forEach((id, i) => params.append(`lines[${i}]`, id));
                        wcs.forEach((id, i) => params.append(`wcs[${i}]`, id));
                        window.location.replace(window.location.pathname + '?' + params.toString());
                    } catch (e) { /* almacenamiento no disponible: se muestra toda la planta */ }
                })();
            </script>

            <livewire:guest.line-progress />
        </main>
    </div>
</x-guest-layout>
