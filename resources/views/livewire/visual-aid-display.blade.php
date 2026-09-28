{{--
    Se actualiza cada 30 s para seguir el número de parte que se está produciendo.
    Todo en un renglón (Volver · estación · En Proceso + MID · reloj) para dejarle
    el mayor alto posible a la imagen; además tiene pantalla completa.
--}}
<div wire:poll.30s="refresh" class="flex flex-col flex-1 min-h-0 gap-3"
    x-data="{
        full: false,
        toggleFull() {
            if (document.fullscreenElement) { document.exitFullscreen(); return; }
            this.$refs.aid?.requestFullscreen?.();
        },
    }"
    @fullscreenchange.document="full = !!document.fullscreenElement">

    <div class="flex items-center justify-between gap-4 flex-shrink-0">
        <div class="flex items-center gap-4 min-w-0">
            {{-- Volver: a la página anterior si se llegó desde este sistema; si se abrió directo, a la vista de la estación --}}
            <a href="{{ route('guest.work-center-progress', $workCenter) }}"
                onclick="if (document.referrer && new URL(document.referrer).origin === window.location.origin && history.length > 1) { history.back(); return false; }"
                title="Volver"
                class="inline-flex h-9 flex-shrink-0 items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 pl-2.5 pr-3.5 text-sm font-semibold text-zinc-700 dark:text-zinc-200 shadow-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Volver
            </a>

            <h1 class="truncate text-xl font-bold text-zinc-800 dark:text-zinc-50 flex items-center">
                <span class="w-2.5 h-2.5 flex-shrink-0 bg-[#2563eb] dark:bg-[#3b82f6] rounded-full inline-block mr-2.5"></span>
                {{ $workCenter }}
            </h1>
        </div>

        <div class="flex items-center gap-4 min-w-0">
            @if($isProducing)
                {{-- MID del número de parte; si la parte no tiene MID registrado, se muestra el número de parte --}}
                <div class="flex items-center gap-3 min-w-0">
                    <span class="lp-wc-live mt-0"><span class="lp-dot"></span>En Proceso</span>
                    <span class="truncate text-lg font-bold tabular-nums text-zinc-900 dark:text-zinc-50" title="No. de parte {{ $currentPartNumber }}">
                        {{ $currentMid ?? $currentPartNumber ?? '—' }}
                    </span>
                </div>
            @endif

            @if($visualAidPath)
                <button type="button" @click="toggleFull()" title="Pantalla completa (Esc para salir)" aria-label="Pantalla completa"
                    class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300 shadow-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                    </svg>
                </button>
            @endif

            <div wire:ignore><x-clock /></div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden flex flex-col flex-1 min-h-0">

        {{-- 1) Ayuda visual activa: ocupa toda la tarjeta; doble clic = pantalla completa --}}
        @if($visualAidPath)
            <div x-ref="aid" @dblclick="toggleFull()" class="flex-1 min-h-0 p-1 bg-white dark:bg-zinc-900 cursor-zoom-in" :class="full && 'cursor-zoom-out'"
                title="Doble clic: pantalla completa">
                <img src="{{ asset('storage/' . $visualAidPath) }}"
                    alt="{{ $visualAidAlt ?? 'Ayuda visual ' . ($currentMid ?? $currentPartNumber ?? '') }}"
                    class="w-full h-full object-contain">
            </div>

        {{-- 2) En producción, pero sin ayuda visual activa --}}
        @elseif($isProducing)
            <div class="flex-1 min-h-0 flex items-center justify-center p-6">
                <div class="flex max-w-md flex-col items-center text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-400/10 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-400/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-xl font-bold text-zinc-800 dark:text-zinc-50">Sin ayuda visual</h2>
                    <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $currentMid ?? $currentPartNumber }}</span>
                        no tiene una ayuda visual activa.
                    </p>
                    <p class="mt-4 rounded-lg border border-amber-600/20 bg-amber-50 dark:bg-amber-400/10 px-4 py-2 text-sm font-medium text-amber-800 dark:text-amber-300">
                        Avisa a Soporte Técnico para cargarla.
                    </p>
                </div>
            </div>

        {{-- 3) Sin producción en el turno --}}
        @else
            <div class="flex-1 min-h-0 flex items-center justify-center p-6">
                <div class="flex max-w-md flex-col items-center text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 ring-1 ring-inset ring-zinc-200 dark:ring-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-xl font-bold text-zinc-800 dark:text-zinc-50">Sin producción</h2>
                    <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        No hay un número de parte en proceso en este turno.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
