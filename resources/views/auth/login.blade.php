<x-guest-layout>
    {{--
        Login con el mismo sistema visual de las vistas de invitados (zinc, azul del plan, botón "Acceder").
        Markup propio (sin <x-authentication-card>/<x-input>) para no cambiar registro, recuperar
        contraseña, 2FA, etc., que usan esos componentes compartidos.
    --}}
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10 bg-zinc-100 dark:bg-zinc-950">
        <div class="w-full max-w-sm">
            <a href="{{ url('/') }}" aria-label="Inicio"
                class="mx-auto mb-8 block w-fit rounded-md transition-opacity hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <img src="{{ asset('images/ykm.png') }}" alt="Y-Tec Keylex México" class="h-16 w-auto">
            </a>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm p-6 sm:p-8">
                <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">Iniciar sesión</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ingresa con tu correo y contraseña.</p>

                @session('status')
                    <div class="mt-5 rounded-lg border border-green-600/20 bg-green-50 dark:bg-green-500/10 px-3 py-2 text-sm font-medium text-green-700 dark:text-green-400">
                        {{ $value }}
                    </div>
                @endsession

                @if ($errors->any())
                    <div class="mt-5 flex gap-2.5 rounded-lg border border-red-600/20 bg-red-50 dark:bg-red-500/10 px-3 py-2.5 text-sm text-red-700 dark:text-red-400" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <ul class="space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4"
                    x-data="{ sending: false, showPassword: false }" @submit="sending = true">
                    @csrf

                    {{-- Correo --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Correo electrónico</label>
                        <div class="relative mt-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                placeholder="nombre@empresa.com"
                                @class([
                                    'block w-full h-10 rounded-lg pl-9 pr-3 text-sm shadow-sm bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 placeholder-zinc-500 focus:ring-2 focus:ring-offset-0',
                                    'border-zinc-300 dark:border-zinc-700 focus:border-[#2563eb] focus:ring-[#2563eb]/30' => !$errors->has('email'),
                                    'border-red-500 focus:border-red-500 focus:ring-red-500/30' => $errors->has('email'),
                                ])>
                        </div>
                    </div>

                    {{-- Contraseña (con botón para mostrarla) --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Contraseña</label>
                        <div class="relative mt-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            <input id="password" name="password" required autocomplete="current-password"
                                :type="showPassword ? 'text' : 'password'" type="password"
                                class="block w-full h-10 rounded-lg pl-9 pr-10 text-sm shadow-sm bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 border-zinc-300 dark:border-zinc-700 focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/30 focus:ring-offset-0">
                            <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Recuérdame --}}
                    <label for="remember_me" class="flex w-fit items-center gap-2 cursor-pointer select-none">
                        <input id="remember_me" type="checkbox" name="remember"
                            class="h-4 w-4 rounded border-zinc-400 dark:border-zinc-600 dark:bg-zinc-950 text-[#2563eb] focus:ring-[#2563eb] dark:focus:ring-offset-zinc-900">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Recuérdame</span>
                    </label>

                    {{-- Acceder: mismo estilo que el botón del header; se desactiva mientras se envía --}}
                    <button type="submit" :disabled="sending"
                        class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-zinc-900 px-4 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white dark:focus-visible:ring-zinc-100 dark:focus-visible:ring-offset-zinc-900">
                        <svg x-show="!sending" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <svg x-show="sending" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                        </svg>
                        <span x-text="sending ? 'Accediendo…' : 'Acceder'">Acceder</span>
                    </button>
                </form>
            </div>

            <a href="{{ url('/') }}"
                class="mx-auto mt-6 flex w-fit items-center gap-1.5 rounded-md text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Volver al inicio
            </a>
        </div>
    </div>
</x-guest-layout>
