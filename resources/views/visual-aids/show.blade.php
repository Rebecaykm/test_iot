<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-zinc-950">
        <x-guest-header />

        {{-- El renglón de Volver/estación/MID/reloj va dentro del componente: el MID cambia con cada actualización --}}
        <main class="flex-1 min-h-0 flex flex-col p-3 sm:p-4">
            @livewire('visual-aid-display', ['work_center' => $work_center])
        </main>
    </div>
</x-guest-layout>
