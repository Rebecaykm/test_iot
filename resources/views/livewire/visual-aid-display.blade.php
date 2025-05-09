<div>
    <div x-data="display" class="h-screen bg-white flex items-center justify-center p-4">
        <div class="w-full h-full flex items-center justify-center">
            @if($visualAid)
                <div class="relative w-full h-full max-w-[90vw] max-h-[90vh]">
                    <img
                        src="{{ asset('storage/' . $visualAid->path) }}"
                        alt="{{ $visualAid->alt_text ?? 'Imagen de referencia' }}"
                        class="w-full h-full object-contain border-1 border-gray-200 rounded-lg shadow-lg"
                    >
                </div>
            @else
                <div class="flex flex-col items-center justify-center space-y-4">
                    <svg class="w-24 h-24 text-gray-600 opacity-75" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-800 text-2xl font-bold text-center">
                        No se encontró ayuda visual disponible
                    </p>
                    <p class="text-gray-500 text-center">
                        Por favor intenta más tarde o contacta al soporte
                    </p>
                </div>
            @endif
        </div>
    </div>
    @script
    <script>
        Alpine.data('display', () => {
            return {
                init() {
                    setInterval(() => {
                        try {
                            $wire.dispatchSelf("refresh");
                        } catch (error) {
                            console.error('Refresh error:', error);
                        }
                    }, 10000);
                }
            }
        });
    </script>
    @endscript
</div>
