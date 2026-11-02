{{--
    Multi select de Tailwind para Livewire (vistas de invitado). Se enlaza con wire:model
    a una propiedad arreglo; options = [['value' => ..., 'label' => ...], ...].
--}}
@props(['options' => [], 'placeholder' => 'Todas', 'label' => null])

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
    }"
    @click.outside="open = false" @keydown.escape.window="open = false"
    {{ $attributes->whereDoesntStartWith('wire:model')->class('relative') }}>
    <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
        :aria-expanded="open" aria-haspopup="listbox" @if($label) aria-label="{{ $label }}" @endif
        class="flex h-10 w-full items-center gap-2 rounded-lg border border-zinc-300 bg-white pl-3 pr-2 text-left text-sm shadow-sm focus:outline-none focus-visible:border-blue-500 focus-visible:ring-1 focus-visible:ring-blue-500 dark:border-ta-gray-700 dark:bg-ta-gray-800"
        :class="open && 'border-blue-500 ring-1 ring-blue-500'">
        <span class="min-w-0 flex-1 truncate" :class="text ? 'text-zinc-800 dark:text-ta-gray-100' : 'text-zinc-500 dark:text-ta-gray-400'" x-text="text ?? @js($placeholder)">{{ $placeholder }}</span>
        <span x-show="text" x-cloak @click.stop="selected = []" role="button" aria-label="Quitar selección"
            class="rounded p-0.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-ta-gray-700 dark:hover:text-ta-gray-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </span>
        <svg class="h-4 w-4 flex-shrink-0 text-zinc-400 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
        class="absolute inset-x-0 z-30 mt-1 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg ring-1 ring-black/5 dark:border-ta-gray-700 dark:bg-ta-gray-800">
        <div class="border-b border-zinc-100 p-2 dark:border-ta-gray-700">
            <input x-ref="search" x-model="q" type="text" placeholder="Buscar…" autocomplete="off"
                class="h-8 w-full rounded-md border-zinc-300 bg-white text-sm text-zinc-800 focus:border-blue-500 focus:ring-blue-500 dark:border-ta-gray-600 dark:bg-ta-gray-900 dark:text-ta-gray-100">
        </div>
        <ul class="max-h-64 overflow-y-auto py-1" role="listbox" aria-multiselectable="true">
            <template x-for="o in filtered" :key="o.value">
                <li role="option" :aria-selected="has(o.value)" @click="toggle(o.value)"
                    class="flex cursor-pointer select-none items-center gap-2 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-ta-gray-200 dark:hover:bg-ta-gray-700/60">
                    <span class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded border"
                        :class="has(o.value) ? 'border-blue-600 bg-blue-600 text-white' : 'border-zinc-300 dark:border-ta-gray-500'">
                        <svg x-show="has(o.value)" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </span>
                    <span class="truncate" x-text="o.label"></span>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-400 dark:text-ta-gray-500">Sin resultados</li>
        </ul>
    </div>
</div>
