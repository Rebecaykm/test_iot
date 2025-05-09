<x-guest-layout>
    <div class="h-screen bg-white flex flex-col">
        <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 h-[70px]">
            <div class="flex justify-between items-center">
                <img src="{{ asset('images/ykm.png') }}" alt="Logo" class="h-12 w-auto">

                @if(Route::has('login'))
                <nav class="-mx-3 flex">
                    @auth
                    <a href="{{ url('/home') }}"
                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]">
                        Inicio
                    </a>
                    @else
                    <a href="{{ route('login') }}"
                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]">
                        Iniciar sesión
                    </a>

                    <!-- @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] ml-4">
                        Registrarse
                    </a>
                    @endif -->
                    @endauth
                </nav>
                @endif
            </div>
        </header>

        <!-- Contenedor del mapa que ocupa el espacio restante -->
        <div class="flex-1 overflow-hidden">
            @livewire('guest.work-center-map-view')
        </div>
    </div>
</x-guest-layout>
