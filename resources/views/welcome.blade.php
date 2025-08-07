<x-guest-layout>
    <div class="flex flex-col">
        <!-- Contenido principal -->
        <main class="flex-1 overflow-hidden bg-gray-50">
            <!-- Vista del Dashboard (única visible) -->
            <div class="h-full overflow-y-auto">
                @livewire('guest.work-center-dashboard')
            </div>
        </main>
    </div>
</x-guest-layout>
