{{--
    Header de las vistas de invitados: logo (lleva a /), contenido opcional al centro
    y la acción de sesión siempre visible (antes estaba escondida en un menú de tres puntos).
--}}
<header class="flex-shrink-0 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
    <div class="flex items-center gap-4 h-14 px-4 sm:px-6">
        <a href="{{ url('/') }}" aria-label="Inicio"
            class="flex-shrink-0 rounded-md transition-opacity hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
            <img src="{{ asset('images/ykm.png') }}" alt="Y-Tec Keylex México" class="h-9 w-auto">
        </a>

        <div class="flex-1 min-w-0">{{ $slot }}</div>

        {{-- Acciones: tema y sesión, juntas y a la misma altura --}}
        <div class="flex flex-shrink-0 items-center gap-2">
            <x-theme-toggle inline />

            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/home') }}"
                        class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3.5 text-sm font-semibold text-zinc-700 dark:text-zinc-200 shadow-sm transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        Panel Administrativo
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex h-9 items-center gap-2 rounded-lg bg-zinc-900 px-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900 focus-visible:ring-offset-2 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white dark:focus-visible:ring-zinc-100 dark:focus-visible:ring-offset-zinc-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Acceder
                    </a>
                @endauth
            @endif
        </div>
    </div>
</header>
