<div class="space-y-6">
    {{-- Buscador --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form wire:submit="search" class="p-3 sm:p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                <div>
                    <label for="partNumber" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Número de parte
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="partNumber"
                            wire:model="partNumber"
                            placeholder="Ej. BJS753140"
                            autocomplete="off"
                            class="block w-full rounded-md border-gray-300 bg-white py-1.5 pl-8 pr-2.5 text-sm uppercase text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                        >
                    </div>
                    @error('partNumber')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="direction" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Proceso
                    </label>
                    <select
                        id="direction"
                        wire:model="direction"
                        class="block w-full cursor-pointer rounded-md border-0 bg-white py-2 pl-3 pr-8 text-sm font-medium text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 transition-shadow hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-600 dark:hover:ring-gray-500 sm:w-36"
                    >
                        <option value="previous">Anterior</option>
                        <option value="next">Siguiente</option>
                    </select>
                </div>

                <div>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="search"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-1.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    >
                        <svg wire:loading wire:target="search" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg wire:loading.remove wire:target="search" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Buscar</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Resultados --}}
    @if($searched)
        @if(!$part)
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white/60 p-10 text-center dark:border-gray-600 dark:bg-gray-800/40">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">No se encontró el número de parte</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Verifica que "<span class="font-mono font-medium">{{ $partNumber }}</span>" esté escrito correctamente.
                </p>
            </div>
        @else
            {{-- Árbol: el número de parte buscado es la raíz de la lista --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">
                    {{ $direction === 'next' ? 'Dónde se usa (ensambles superiores)' : 'De qué está compuesto (componentes)' }}
                </h2>

                <ul class="space-y-0.5">
                    @include('livewire.guest.partials.part-number-tree-node', ['node' => $tree, 'level' => 0])
                </ul>

                @if(empty($tree['children']))
                    <p class="mt-2 pl-9 text-sm text-gray-500 dark:text-gray-400">
                        {{ $direction === 'next'
                            ? 'Este número de parte no se usa en ningún otro ensamble registrado.'
                            : 'Este número de parte no tiene componentes registrados.' }}
                    </p>
                @endif
            </div>
        @endif
    @endif
</div>
