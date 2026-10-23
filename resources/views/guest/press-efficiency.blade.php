<x-guest-layout :floating-theme-toggle="false">
    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-950">
        <x-guest-header>
            <h1 class="text-base sm:text-lg font-semibold text-zinc-800 dark:text-zinc-100 truncate">Eficiencia de prensas · Estampado</h1>
        </x-guest-header>

        <main class="flex-1 p-4 md:p-6">
            <livewire:guest.press-efficiency />
        </main>
    </div>
</x-guest-layout>
