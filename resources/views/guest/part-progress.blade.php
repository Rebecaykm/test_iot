<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-zinc-950">
        <x-guest-header />

        <main class="flex-1 min-h-0 flex flex-col gap-3 p-3 sm:p-4">
            <div class="flex items-center justify-between gap-4 flex-shrink-0">
                <div class="flex items-center gap-4 min-w-0">
                    {{-- Volver: a la página anterior si se llegó desde este sistema; si se abrió directo, al inicio (/) --}}
                    <a href="{{ url('/') }}"
                        onclick="if (document.referrer && new URL(document.referrer).origin === window.location.origin && history.length > 1) { history.back(); return false; }"
                        title="Volver"
                        class="inline-flex h-9 flex-shrink-0 items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 pl-2.5 pr-3.5 text-sm font-semibold text-zinc-700 dark:text-zinc-200 shadow-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Volver
                    </a>

                    <h1 class="truncate text-2xl font-bold text-zinc-800 dark:text-zinc-50 flex items-center">
                        <span class="w-2.5 h-2.5 flex-shrink-0 bg-[#2563eb] dark:bg-[#3b82f6] rounded-full inline-block mr-2.5"></span>
                        Avance por número de parte
                    </h1>
                </div>

                <x-clock />
            </div>

            {{-- lazy: la página aparece de inmediato y la tabla llega en cuanto está lista --}}
            <livewire:guest.part-progress lazy />
        </main>
    </div>
</x-guest-layout>
