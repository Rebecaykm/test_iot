<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>
<body class="bg-gray-50">
<div class="font-sans text-gray-900 antialiased min-h-screen">

    <!-- Header Global para todas las vistas de invitados -->
    <header class="bg-white shadow-sm">
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

    <!-- Contenido de cada vista -->
    {{ $slot }}

</div>

@livewireScripts

<!-- Stack para scripts y estilos adicionales -->
@stack('scripts')
@stack('styles')
</body>
</html>
