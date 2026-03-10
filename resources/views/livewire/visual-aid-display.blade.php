<div>
    <div x-data="display" class="h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="w-full h-full flex items-center justify-center">

            {{-- ✅ ESTADO 1: Hay ayuda visual activa --}}
            @if($visualAidPath)
                <div class="relative w-full h-full flex flex-col">
                    {{-- Header con info del part number --}}
                    <div class="flex items-center justify-between bg-white border-b border-gray-200 px-6 py-3 shadow-sm">
                        <div class="flex items-center space-x-3">
                            <span class="flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Número de Parte en Producción</p>
                                <p class="text-lg font-bold text-gray-800">{{ $currentPartNumber ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Estación</p>
                            <p class="text-lg font-bold text-gray-800">{{ $workCenter }}</p>
                        </div>
                    </div>

                    {{-- Imagen de ayuda visual — sin padding, ocupa todo el espacio restante --}}
                    <div class="flex-1 min-h-0">
                        <img
                            src="{{ asset('storage/' . $visualAidPath) }}"
                            alt="{{ $visualAidAlt ?? 'Ayuda visual - ' . ($currentPartNumber ?? '') }}"
                            class="w-full h-full object-contain"
                        >
                    </div>
                </div>

            {{-- ⚠️ ESTADO 2: Hay producción activa pero sin ayuda visual asignada --}}
            @elseif($isProducing)
                <div class="flex flex-col items-center justify-center space-y-6 max-w-lg text-center px-8">
                    <div class="relative">
                        <div class="w-28 h-28 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-14 h-14 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 9.75h18M3.75 6h16.5A2.25 2.25 0 0122.5 8.25v7.5A2.25 2.25 0 0120.25 18H3.75A2.25 2.25 0 011.5 15.75V8.25A2.25 2.25 0 013.75 6z"/>
                            </svg>
                        </div>
                        <div class="absolute -top-1 -right-1 w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L1 21h22L12 2zm0 3.5L20.5 19h-17L12 5.5zM11 10v4h2v-4h-2zm0 5v2h2v-2h-2z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold text-gray-800">
                            Ayuda Visual No Disponible
                        </h2>
                        <p class="text-gray-600 text-base leading-relaxed">
                            La estación <span class="font-semibold text-gray-800">{{ $workCenter }}</span>
                            se encuentra en producción con el número de parte
                            <span class="font-semibold text-amber-600">{{ $currentPartNumber ?? '—' }}</span>,
                            pero no tiene una ayuda visual activa asignada.
                        </p>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-6 py-4 w-full">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M13 9h-2V7h2m0 10h-2v-6h2m-1-9A10 10 0 002 12a10 10 0 0010 10 10 10 0 0010-10A10 10 0 0012 2z"/>
                            </svg>
                            <p class="text-amber-800 text-sm text-left">
                                Comunica esta situación al equipo de <span class="font-semibold">Soporte Técnico</span>
                                para que pueda asignar o cargar la ayuda visual correspondiente a este número de parte.
                            </p>
                        </div>
                    </div>
                </div>

            {{-- 🔴 ESTADO 3: No hay producción activa en esta estación --}}
            @else
                <div class="flex flex-col items-center justify-center space-y-6 max-w-lg text-center px-8">
                    <div class="w-28 h-28 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-14 h-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z"/>
                        </svg>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold text-gray-700">
                            Estación Sin Producción Activa
                        </h2>
                        <p class="text-gray-500 text-base leading-relaxed">
                            La estación <span class="font-semibold text-gray-700">{{ $workCenter }}</span>
                            no tiene ningún número de parte en proceso para el turno actual.
                        </p>
                    </div>
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
                            $wire.dispatchSelf('refresh');
                        } catch (error) {
                            console.error('Error al actualizar la pantalla:', error);
                        }
                    }, 10000);
                }
            }
        });
    </script>
    @endscript
</div>
