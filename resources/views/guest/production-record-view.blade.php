<x-guest-layout>
    <div class="bg-gray-200 dark:bg-gray-900 min-h-screen">
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <!-- Logo y título -->
                <div class="flex items-center space-x-4">
                    <a href="{{ url('/') }}" class="flex items-center hover:opacity-80 transition-opacity duration-200">
                        <img src="{{ asset('images/ykm.png') }}" alt="Logo" class="h-10 w-auto">
                    </a>
                </div>

                <!-- Navegación de login -->
                @if(Route::has('login'))
                <nav class="flex items-center space-x-4 text-sm">
                    @auth
                        <a href="{{ url('/home') }}" class="inline-block px-4 py-2 text-sm font-medium rounded-md bg-transparent border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition-colors">
                            Inicio
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-block px-5 py-1.5 text-[#1b1b18] dark:text-[#EDEDEC] border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#1915014a] dark:hover:border-[#62605b] bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm text-sm leading-normal transition-colors">
                            Iniciar sesión
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-block px-5 py-1.5 text-[#1b1b18] dark:text-[#EDEDEC] border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#1915014a] dark:hover:border-[#62605b] bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm text-sm leading-normal transition-colors">
                                Registrarse
                            </a>
                        @endif
                    @endauth
                </nav>
                @endif
            </div>
        </header>

        <div class="container mx-auto px-6 lg:px-8 py-6">
            <div class="flex flex-col w-full">

                @include('clock')

                <div class="w-full mt-4">
                    <livewire:guest.production-record-view :work-center-id="$workCenterId" />
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
