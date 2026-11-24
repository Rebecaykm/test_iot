<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-ta-gray-950">
        <x-guest-header />

        <main class="flex-1 min-h-0 flex flex-col p-3 sm:p-4">
            {{-- Catálogos de los filtros: se entregan una vez aquí para que no viajen en cada refresco del componente --}}
            <script>window.lineProgressCatalog = @json(\App\Livewire\Guest\LineProgress::catalog());</script>

            <livewire:guest.plant-progress />
        </main>
    </div>
</x-guest-layout>
