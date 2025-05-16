<x-guest-layout>
    <div class="h-screen flex flex-col bg-gray-50">

        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <!-- Logo y título -->
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('images/ykm.png') }}" alt="Logo" class="h-10 w-auto">
                </div>

                <!-- Navegación -->
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

        <!-- Contenido principal con mapa -->
        <main class="flex-1 overflow-hidden">
            <div class="h-full">
                @livewire('guest.work-center-map-view')
            </div>
        </main>

    </div>
</x-guest-layout>
