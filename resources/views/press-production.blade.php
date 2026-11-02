<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-ta-gray-950">
        <x-guest-header />

        {{-- overflow-y-auto: en pantallas bajas la gráfica conserva su alto mínimo y el contenido hace scroll --}}
        <main class="flex-1 min-h-0 flex flex-col gap-3 p-3 sm:p-4 overflow-y-auto">
            <div class="flex items-center justify-between gap-4 flex-shrink-0">
                {{--
                    Volver: al avance de la línea de la estación (/guest/progress/line/{línea});
                    si la línea no está en el avance, a la vista de la estación.
                --}}
                <a href="{{ \App\Livewire\Guest\LineProgress::lineUrlFor($workCenter) ?? route('guest.work-center-progress', $workCenter) }}"
                    title="Volver"
                    class="inline-flex h-9 flex-shrink-0 items-center gap-2 rounded-lg border border-zinc-300 dark:border-ta-gray-700 bg-white dark:bg-ta-gray-900 pl-2.5 pr-3.5 text-sm font-semibold text-zinc-700 dark:text-ta-gray-200 shadow-sm transition-colors hover:bg-zinc-50 dark:hover:bg-ta-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Volver
                </a>

                <x-clock />
            </div>

            {{-- Resumen del turno (alto según contenido) y gráfica (llena el resto de la pantalla) --}}
            <div class="flex-shrink-0">
                <livewire:press-production-table :work-center="$workCenter" :realTime="true" lazy />
            </div>

            <livewire:press-production-graph :work-center="$workCenter" :real-time="'true'" lazy />
        </main>
    </div>
</x-guest-layout>
