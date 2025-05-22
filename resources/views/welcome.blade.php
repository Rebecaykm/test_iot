<x-guest-layout>
    <div class="h-screen flex flex-col bg-gray-50">

        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <!-- Logo y título -->
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('images/ykm.png') }}" alt="Logo" class="h-10 w-auto">
                </div>

                <!-- Navegación central con pestañas -->
                <div class="flex items-center space-x-6">
                    <!-- Pestañas -->
                    <nav class="flex space-x-4" aria-label="Tabs">
                        <button id="tab-map"
                                class="tab-button flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-600 bg-blue-100 border border-blue-200"
                                onclick="switchTab('map')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Mapa
                        </button>
                        <button id="tab-dashboard"
                                class="tab-button flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 border border-transparent"
                                onclick="switchTab('dashboard')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Gráficas
                        </button>
                    </nav>
                </div>

                <!-- Navegación de login -->
                @if(Route::has('login'))
                    <nav class="flex items-center space-x-4 text-sm">
                        @auth
                            <a href="{{ url('/home') }}"
                               class="px-4 py-2 rounded-md text-gray-700 hover:text-white hover:bg-blue-700 transition duration-200">
                                Inicio
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 rounded-md text-gray-700 hover:text-white hover:bg-blue-700 transition duration-200">
                                Iniciar sesión
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-2 rounded-md text-gray-700 hover:text-white hover:bg-blue-700 transition duration-200">
                                    Registrarse
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1 overflow-hidden">
            <!-- Vista del Mapa -->
            <div id="map-view" class="content-view h-full">
                @livewire('guest.work-center-map-view')
            </div>

            <!-- Vista del Dashboard -->
            <div id="dashboard-view" class="content-view h-full overflow-y-auto hidden">
                @livewire('guest.work-center-dashboard')
            </div>
        </main>

    </div>

    <script>
        function switchTab(tabName) {
            // Ocultar todas las vistas
            document.querySelectorAll('.content-view').forEach(view => {
                view.classList.add('hidden');
            });

            // Remover estilos activos de todas las pestañas
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('text-blue-600', 'bg-blue-100', 'border-blue-200');
                button.classList.add('text-gray-500', 'border-transparent');
            });

            // Mostrar la vista seleccionada y activar la pestaña
            if (tabName === 'map') {
                document.getElementById('map-view').classList.remove('hidden');
                const tabMap = document.getElementById('tab-map');
                tabMap.classList.remove('text-gray-500', 'border-transparent');
                tabMap.classList.add('text-blue-600', 'bg-blue-100', 'border-blue-200');
            } else if (tabName === 'dashboard') {
                document.getElementById('dashboard-view').classList.remove('hidden');
                const tabDashboard = document.getElementById('tab-dashboard');
                tabDashboard.classList.remove('text-gray-500', 'border-transparent');
                tabDashboard.classList.add('text-blue-600', 'bg-blue-100', 'border-blue-200');
            }
        }

        // Inicializar con la pestaña del mapa activa por defecto
        document.addEventListener('DOMContentLoaded', function() {
            switchTab('map');
        });
    </script>
</x-guest-layout>
