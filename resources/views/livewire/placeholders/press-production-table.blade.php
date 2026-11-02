{{-- Esqueleto de la tabla de press-production mientras llega (carga diferida) --}}
<div class="w-full" aria-busy="true" aria-label="Cargando resumen del turno">
    <div class="bg-white dark:bg-ta-gray-900 rounded-xl border border-zinc-200 dark:border-ta-gray-800 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-200 dark:border-ta-gray-700/70 flex items-center justify-between gap-3">
            <div class="h-6 w-32 rounded bg-zinc-200 dark:bg-ta-gray-800 animate-pulse"></div>
            <div class="h-7 w-40 rounded-full bg-zinc-100 dark:bg-ta-gray-800 animate-pulse"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-zinc-200 dark:bg-ta-gray-700">
            @for($i = 0; $i < 4; $i++)
                <div class="bg-white dark:bg-ta-gray-900 px-3 py-3 flex flex-col items-center gap-2">
                    <div class="h-3 w-20 rounded bg-zinc-100 dark:bg-ta-gray-800 animate-pulse"></div>
                    <div class="h-7 w-16 rounded bg-zinc-200 dark:bg-ta-gray-800 animate-pulse"></div>
                </div>
            @endfor
        </div>
    </div>
</div>
