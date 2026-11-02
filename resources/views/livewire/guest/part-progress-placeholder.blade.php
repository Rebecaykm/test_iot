{{-- Esqueleto: con $cardsOnly solo las tarjetas (cambio de filtro o turno); sin él, también la barra (carga diferida) --}}
<div class="flex flex-col gap-3" aria-busy="true" aria-label="Cargando producción">
    @unless($cardsOnly ?? false)
        <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-ta-gray-800 dark:bg-ta-gray-900 lg:flex-row lg:items-center">
            <div class="h-10 w-full rounded-lg bg-zinc-100 animate-pulse dark:bg-ta-gray-800 lg:w-96"></div>
            <div class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-3">
                @for($i = 0; $i < 3; $i++)
                    <div class="h-10 rounded-lg bg-zinc-100 animate-pulse dark:bg-ta-gray-800"></div>
                @endfor
            </div>
        </div>
    @endunless

    <div class="h-5 w-64 rounded bg-zinc-200 animate-pulse dark:bg-ta-gray-800"></div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @for($i = 0; $i < 6; $i++)
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-ta-gray-800 dark:bg-ta-gray-900">
                <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2.5 dark:border-ta-gray-800">
                    <div class="h-4 w-24 rounded bg-zinc-200 animate-pulse dark:bg-ta-gray-800"></div>
                    <div class="h-4 w-12 rounded bg-zinc-100 animate-pulse dark:bg-ta-gray-800"></div>
                </div>
                @for($j = 0; $j < 3; $j++)
                    <div class="flex items-center gap-3 px-3 py-2.5">
                        <div class="flex-1 space-y-1.5">
                            <div class="h-3.5 w-28 rounded bg-zinc-200 animate-pulse dark:bg-ta-gray-800"></div>
                            <div class="h-3 w-40 rounded bg-zinc-100 animate-pulse dark:bg-ta-gray-800/60"></div>
                        </div>
                        <div class="h-5 w-12 rounded bg-zinc-200 animate-pulse dark:bg-ta-gray-800"></div>
                    </div>
                @endfor
            </div>
        @endfor
    </div>
</div>
