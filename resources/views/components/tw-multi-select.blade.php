{{--
    Multi select de Tailwind para Livewire (vistas de invitado). Se enlaza con wire:model
    a una propiedad arreglo; options = [['value' => ..., 'label' => ...], ...].
    Opciones como casillas reales: se eligen con teclado (Tab / Espacio) y las lee el lector de pantalla.
--}}
@props(['options' => [], 'placeholder' => 'Todas', 'label' => null])

@php($uid = 'ms-' . md5($attributes->wire('model')->value()))

<div x-data="{
        open: false,
        q: '',
        selected: @entangle($attributes->wire('model')),
        options: @js(collect($options)->map(fn ($o) => ['value' => (string) $o['value'], 'label' => $o['label']])->values()),
        get filtered() { return this.options.filter(o => o.label.toLowerCase().includes(this.q.toLowerCase())) },
        has(v) { return (this.selected || []).includes(v) },
        toggle(v) { this.selected = this.has(v) ? this.selected.filter(s => s !== v) : [...(this.selected || []), v] },
        get text() {
            const names = this.options.filter(o => this.has(o.value)).map(o => o.label);
            return names.length ? names[0] + (names.length > 1 ? ' +' + (names.length - 1) : '') : null;
        },
        close(focus) { this.open = false; if (focus) this.$refs.trigger.focus() },
    }"
    @click.outside="close(false)" @keydown.escape="close(true)"
    {{-- wire:ignore: las opciones las pinta Alpine; si Livewire las compara con su HTML las borra y se pierde el foco. Para cambiar opciones, cambiar el wire:key --}}
    wire:ignore
    {{ $attributes->whereDoesntStartWith('wire:model')->class('relative') }}>
    <button type="button" x-ref="trigger" @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
        :aria-expanded="open" aria-controls="{{ $uid }}" @if($label) aria-label="{{ $label }}" @endif
        class="flex h-10 w-full items-center gap-2 rounded-lg border border-zinc-300 bg-white pl-3 text-left text-sm shadow-sm focus:outline-none focus-visible:border-blue-500 focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-ta-gray-700 dark:bg-ta-gray-800"
        :class="[open && 'border-blue-500 ring-1 ring-blue-500', text ? 'pr-16' : 'pr-9']">
        <span class="min-w-0 flex-1 truncate" :class="text ? 'text-zinc-800 dark:text-ta-gray-100' : 'text-zinc-500 dark:text-ta-gray-400'" x-text="text ?? @js($placeholder)">{{ $placeholder }}</span>
    </button>

    {{-- Fuera del botón: un botón dentro de otro no es válido ni accesible --}}
    <button type="button" x-show="text" x-cloak @click="selected = []; $refs.trigger.focus()" aria-label="Quitar selección de {{ $label ?? 'filtro' }}"
        class="absolute right-8 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-ta-gray-400 dark:hover:bg-ta-gray-700 dark:hover:text-ta-gray-200">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    </button>
    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500 transition-transform dark:text-ta-gray-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>

    <div id="{{ $uid }}" x-show="open" x-cloak x-transition.opacity.duration.100ms role="group" @if($label) aria-label="{{ $label }}" @endif
        class="absolute inset-x-0 z-30 mt-1 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg ring-1 ring-black/5 dark:border-ta-gray-700 dark:bg-ta-gray-800">
        <div class="border-b border-zinc-100 p-2 dark:border-ta-gray-700">
            <input x-ref="search" x-model="q" type="text" placeholder="Buscar…" autocomplete="off" aria-label="Buscar en {{ $label ?? 'opciones' }}"
                class="h-8 w-full rounded-md border-zinc-300 bg-white text-sm text-zinc-800 placeholder:text-zinc-500 focus:border-blue-500 focus:ring-blue-500 dark:border-ta-gray-600 dark:bg-ta-gray-900 dark:text-ta-gray-100 dark:placeholder:text-ta-gray-400">
        </div>
        <div class="max-h-64 overflow-y-auto py-1">
            <template x-for="o in filtered" :key="o.value">
                <label class="flex cursor-pointer select-none items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 focus-within:bg-zinc-50 dark:text-ta-gray-200 dark:hover:bg-ta-gray-700/60 dark:focus-within:bg-ta-gray-700/60">
                    <input type="checkbox" :checked="has(o.value)" @change="toggle(o.value)"
                        class="h-4 w-4 flex-shrink-0 rounded border-zinc-400 text-blue-600 focus:ring-blue-500 dark:border-ta-gray-500 dark:bg-ta-gray-900">
                    <span class="truncate" x-text="o.label"></span>
                </label>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-ta-gray-400">Sin resultados</p>
        </div>
    </div>
</div>
