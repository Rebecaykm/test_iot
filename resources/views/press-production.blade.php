<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-zinc-950">
        <x-guest-header />

        {{-- overflow-y-auto: en pantallas bajas la gráfica conserva su alto mínimo y el contenido hace scroll --}}
        <main class="flex-1 min-h-0 flex flex-col gap-3 p-3 sm:p-4 overflow-y-auto">
            <div class="flex items-center justify-between gap-4 flex-shrink-0">
                {{--
                    Volver: a la página anterior si se llegó desde este sistema;
                    si se abrió directo (sin historial), a la vista de la estación.
                --}}
                <a href="{{ Route::has('guest.work-center-progress') ? route('guest.work-center-progress', $workCenter) : url('/') }}"
                    onclick="if (document.referrer && new URL(document.referrer).origin === window.location.origin && history.length > 1) { history.back(); return false; }"
                    title="Volver"
                    class="inline-flex h-9 flex-shrink-0 items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 pl-2.5 pr-3.5 text-sm font-semibold text-zinc-700 dark:text-zinc-200 shadow-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Volver
                </a>

                <x-clock />
            </div>

            {{-- Resumen del turno (alto según contenido) y gráfica (llena el resto de la pantalla) --}}
            <div class="flex-shrink-0">
                <livewire:press-production-table :work-center="$workCenter" :realTime="true" />
            </div>

            <livewire:press-production-graph :work-center="$workCenter" :real-time="'true'" />
        </main>
    </div>
</x-guest-layout>
