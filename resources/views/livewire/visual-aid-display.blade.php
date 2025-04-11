<div>
    <div class="fixed inset-0 bg-gray-900 overflow-hidden" wire:poll.60s="refreshTime">
        <!-- Contenedor principal centrado -->
        <div class="h-screen flex flex-col items-center justify-center p-4">
            <!-- Encabezado con número de parte -->
            <div class="text-center mb-8 animate-pulse">
                <h1 class="text-4xl font-bold mb-2">
                    🛠️ AYUDA VISUAL OPERATIVA
                </h1>
                @if($visualAid)
                    <div class="bg-indigo-600 inline-block px-6 py-3 rounded-full shadow-lg">
                        <p class="text-3xl text-white font-extrabold">
                            NÚMERO DE PARTE: <span class="text-yellow-300">{{ $visualAid->partNumber->number }}</span>
                        </p>
                    </div>
                @endif
            </div>

            <!-- Imagen a pantalla completa -->
            <div class="flex-1 w-full flex items-center justify-center">
                @if($visualAid)
                    <img
                        src="{{ asset('storage/' . $visualAid->path) }}"
                        alt="{{ $visualAid->alt_text ?? 'Imagen de referencia' }}"
                        class="object-contain max-w-full max-h-[80vh] border-4 border-white rounded-lg shadow-2xl"
                    >
                @else
                    <div class="text-center bg-red-500 p-8 rounded-xl">
                        <p class="text-2xl font-bold">⚠️ NO HAY AYUDA VISUAL DISPONIBLE</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="mt-4 text-opacity-70 text-sm">
                {{ $workCenter ?? 'Centro de trabajo' }} • {{ $currentTime->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:load', function() {
                // Actualizar cada minuto (60,000 ms)
                setInterval(() => {
                    Livewire.emit('refreshComponent');
                }, 60000);
            });
        </script>
    @endpush
</div>
