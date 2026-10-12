<x-guest-layout>
    <div class="min-h-screen flex flex-col bg-zinc-100 dark:bg-zinc-950">
        {{-- Header con el mismo estilo que las vistas de invitados (x-guest-header en production-progress-v2) --}}
        <header class="flex-shrink-0 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center gap-4 h-14 px-4 sm:px-6">
                <a href="{{ url('/') }}" aria-label="Inicio"
                    class="flex-shrink-0 rounded-md transition-opacity hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                    <img src="{{ asset('images/ykm.png') }}" alt="Y-Tec Keylex México" class="h-9 w-auto">
                </a>
                <div class="flex-1 min-w-0">
                    <h1 class="text-base sm:text-lg font-semibold text-zinc-800 dark:text-zinc-100 truncate">Eficiencia de prensas · Estampado</h1>
                </div>
            </div>
        </header>

        <main class="flex-1 p-3 sm:p-4">
            <livewire:guest.press-efficiency />
        </main>
    </div>
</x-guest-layout>
