{{-- Filtro, buscador y Limpiar filtros de la barra superior: a la izquierda en el inicio y a la derecha (junto a la semana) en línea y estación --}}
{{-- Filtro (líneas en el inicio, estaciones dentro de una línea): guardado en la URL y en el navegador --}}
@if($filter)
    <div wire:ignore x-data="{{ $filter[0] }}" @click.outside="open = false" class="relative w-full sm:w-48">
        <button @click="toggleOpen()" x-ref="trigger" type="button" class="{{ $control }} w-full flex items-center justify-between gap-1.5 px-2.5">
            <span class="flex items-center gap-1.5 min-w-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                <span x-text="label" class="truncate font-medium"></span>
            </span>
            <span class="flex items-center gap-1 flex-shrink-0">
                <span x-show="selected.length > 0" x-text="selected.length"
                    class="inline-flex items-center justify-center min-w-[1rem] h-4 px-1 text-[10px] font-bold bg-[#2563eb] dark:bg-[#3b82f6] text-white rounded-full"></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-zinc-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </span>
        </button>

        <div x-show="open" x-transition.opacity.duration.100ms style="display: none;" class="{{ $panel }} w-full sm:w-72">
            <div class="p-2 border-b border-zinc-200 dark:border-ta-gray-800">
                <input x-model="search" type="text" placeholder="{{ $filter[1] }}"
                    class="w-full h-7 px-2.5 py-0 text-xs border border-zinc-300 dark:border-ta-gray-700/70 rounded-md bg-white dark:bg-ta-gray-900 text-zinc-800 dark:text-ta-gray-200 placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="overflow-y-auto py-1" :style="`max-height: ${listMaxHeight}px`">
                {{-- En el inicio, agrupadas por área: la casilla del área elige o quita todas sus líneas --}}
                <template x-for="group in groups" :key="group.name || 'all'">
                    <div>
                        <label x-show="group.name" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold cursor-pointer hover:bg-zinc-50 dark:hover:bg-ta-gray-800 transition-colors">
                            <input type="checkbox" :checked="groupState(group) === 'all'" x-effect="$el.indeterminate = groupState(group) === 'some'" @change="toggleGroup(group)"
                                class="h-3.5 w-3.5 rounded border-zinc-400 dark:border-ta-gray-600 dark:bg-ta-gray-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                            <span class="min-w-0 uppercase tracking-wide text-zinc-800 dark:text-ta-gray-100" x-text="group.name"></span>
                            <span class="ml-auto text-[11px] font-normal text-zinc-500 dark:text-ta-gray-400" x-text="group.options.length"></span>
                        </label>
                        <template x-for="option in group.options" :key="option.id">
                            <label class="flex items-center gap-2 py-1.5 pr-3 text-xs cursor-pointer hover:bg-zinc-50 dark:hover:bg-ta-gray-800 transition-colors" :class="group.name ? 'pl-8' : 'pl-3'">
                                <input type="checkbox" :checked="isSelected(option.id)" @change="toggle(option.id)"
                                    class="h-3.5 w-3.5 rounded border-zinc-400 dark:border-ta-gray-600 dark:bg-ta-gray-900 text-[#2563eb] focus:ring-blue-500 focus:ring-offset-0 flex-shrink-0">
                                <span class="min-w-0 text-zinc-700 dark:text-ta-gray-200" x-text="option.name"></span>
                                <span x-show="sub(option)" class="ml-auto text-[11px] text-zinc-500 dark:text-ta-gray-400 truncate" x-text="sub(option)"></span>
                            </label>
                        </template>
                    </div>
                </template>
                <p x-show="filtered.length === 0" class="text-center text-xs text-zinc-500 dark:text-ta-gray-400 py-4">Sin resultados</p>
            </div>
            <div x-show="selected.length > 0" style="display: none;" class="p-1.5 border-t border-zinc-200 dark:border-ta-gray-800">
                <button @click="clear()" type="button" x-text="clearText"
                    class="w-full px-3 py-1 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"></button>
            </div>
        </div>
    </div>
@else
    {{-- Vista de números de parte: buscador en vivo (va en la URL como ?q=) --}}
    <div class="relative w-full sm:w-56">
        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input type="search" wire:model.live.debounce.300ms="partSearch" placeholder="Buscar número de parte..." autocomplete="off" spellcheck="false"
            aria-label="Buscar número de parte" @keydown.escape.stop="$wire.set('partSearch', '')"
            class="{{ $control }} w-full pl-8 pr-7 uppercase placeholder:normal-case placeholder-zinc-500 [&::-webkit-search-cancel-button]:hidden">
        @if($partSearch !== '')
            <button type="button" wire:click="$set('partSearch', '')" title="Limpiar búsqueda" aria-label="Limpiar búsqueda"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 p-0.5 rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-ta-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        @endif
    </div>
@endif

{{--
    Limpiar filtro: borra también la selección guardada en este navegador. Va como método:
    Alpine evalúa @click como expresión ("resultado = ...") y un try/catch suelto ahí no es válido.
--}}
<button type="button" x-cloak
    x-data="{
        clear() {
            try {
                localStorage.removeItem('lineProgress.lines');
                localStorage.removeItem('lineProgress.wcs');
            } catch (e) {}
            $wire.clearFilters();
        },
    }"
    x-show="($wire.selectedLines || []).length || ($wire.selectedWorkCenters || []).length"
    @click="clear()"
    class="h-8 inline-flex items-center gap-1 rounded-lg px-2 text-xs font-medium text-zinc-500 dark:text-ta-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    Limpiar filtros
</button>
