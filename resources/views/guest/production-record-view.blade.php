<x-guest-layout>
    <div class="bg-gray-200 dark:bg-gray-900 min-h-screen">
        <div class="container mx-auto px-6 lg:px-8 py-6">
            <div class="flex flex-col w-full">

                @include('clock')

                <!-- Menú de opciones y título -->
                <div class="flex justify-between items-center my-4 gap-4">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white whitespace-nowrap">
                        Detalles de Producción
                    </h1>

                    <div class="flex items-center gap-4">
                        <!-- Leyenda de estatus -->
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                                <span class="hidden sm:inline text-xs font-medium text-gray-600 dark:text-gray-400">Pend.</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                <span class="hidden sm:inline text-xs font-medium text-gray-600 dark:text-gray-400">Prog.</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                                <span class="hidden sm:inline text-xs font-medium text-gray-600 dark:text-gray-400">Det.</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                                <span class="hidden sm:inline text-xs font-medium text-gray-600 dark:text-gray-400">Comp.</span>
                            </span>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>

                            <!-- Dropdown menu -->
                        <div x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                            style="display: none;">

                            <a href="{{ url('/') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                </svg>
                                <span>Inicio</span>
                            </a>

                            @php
                                // Obtener información del workCenter para la ruta de gráficas
                                $workCenterModel = \App\Models\WorkCenter::with('line.area')->find($workCenterId);
                                $isEstampado = false;

                                if ($workCenterModel && $workCenterModel->line && $workCenterModel->line->area) {
                                    $areaName = strtolower($workCenterModel->line->area->name);
                                    if ($areaName === 'estampado') {
                                        $isEstampado = true;
                                        $graphRoute = route('press-production', $workCenterModel->name);
                                    } else {
                                        $graphRoute = route('production-dashboard', $workCenterModel->name);
                                    }
                                } else {
                                    $graphRoute = $workCenterModel ? route('production-dashboard', $workCenterModel->name) : url('/');
                                }
                            @endphp

                            <a href="{{ $graphRoute }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                                </svg>
                                <span>Gráfica de Producción</span>
                            </a>

                            @if($workCenterModel && $isEstampado)
                                <a href="{{ route('press-production-timeline', $workCenterModel->name) }}"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Tiempo de Producción</span>
                                </a>
                            @endif

                            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/home') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Panel Administrativo</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                        class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-600 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Iniciar Sesión</span>
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                    </div>
                </div>

                <div class="w-full">
                    <livewire:guest.production-record-view :work-center-id="$workCenterId" />
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
