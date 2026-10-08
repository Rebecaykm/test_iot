{{-- Mientras llega la tabla (carga diferida): misma tarjeta, con renglones de esqueleto --}}
<div class="flex flex-col min-h-0">
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col min-h-0 overflow-hidden">
        <div class="px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700/70 flex items-center justify-between gap-4">
            <div class="h-[34px] w-80 rounded-lg bg-zinc-100 dark:bg-zinc-800 animate-pulse"></div>
            <div class="flex items-center gap-2 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                <svg class="h-4 w-4 animate-spin text-[#2563eb] dark:text-[#60a5fa]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Cargando producción…
            </div>
        </div>
        <div class="h-[68px] bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700/70"></div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @for($i = 0; $i < 8; $i++)
                <div class="flex items-center gap-4 px-4 py-3">
                    <div class="h-4 w-20 rounded bg-zinc-100 dark:bg-zinc-800 animate-pulse"></div>
                    <div class="h-4 w-28 rounded bg-zinc-100 dark:bg-zinc-800 animate-pulse"></div>
                    <div class="h-6 flex-1 rounded bg-zinc-50 dark:bg-zinc-800/60 animate-pulse"></div>
                </div>
            @endfor
        </div>
    </div>
</div>
