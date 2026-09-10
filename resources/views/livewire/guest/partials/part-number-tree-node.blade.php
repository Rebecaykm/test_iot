@php
    $dotColors = [
        1 => 'bg-blue-500',
        2 => 'bg-violet-500',
        3 => 'bg-emerald-500',
        4 => 'bg-amber-500',
        0 => 'bg-rose-500',
    ];
    $dotColor = $dotColors[$level] ?? $dotColors[$level % 5];
    $hasChildren = !empty($node['children']);
    $station = trim(($node['station_number'] ?? '') . ' - ' . ($node['station'] ?? ''), ' -');
@endphp

<li>
    <div x-data="{ open: {{ $level <= 2 ? 'true' : 'false' }}, details: false }">
        <div class="group flex items-center gap-2 rounded-md px-1.5 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/40">
            {{-- Toggle de hijos --}}
            <button
                type="button"
                @if($hasChildren) @click="open = !open" @endif
                @unless($hasChildren) tabindex="-1" @endunless
                class="flex h-4 w-4 shrink-0 items-center justify-center rounded {{ $hasChildren ? 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer' : '' }}"
            >
                @if($hasChildren)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-150" :class="open ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                @endif
            </button>

            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotColor }}"></span>

            {{-- Solo el número de parte --}}
            <span class="min-w-0 flex-1 truncate font-mono text-sm font-semibold text-gray-900 dark:text-white">
                {{ $node['number'] ?? '—' }}
            </span>

            {{-- Toggle de detalles --}}
            <button
                type="button"
                @click="details = !details"
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                :aria-label="details ? 'Ocultar detalles' : 'Ver detalles'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-150" :class="details ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        {{-- Detalles ocultos por defecto: en un renglón, no en lista apilada --}}
        <ul x-show="details" x-cloak x-transition.duration.150ms class="ml-9 flex flex-wrap gap-x-4 gap-y-0.5 py-1 text-xs text-gray-500 dark:text-gray-400">
            <li>Estación: {{ $station !== '' ? $station : 'Sin asignar' }}</li>
            <li>Nombre: {{ $node['name'] ?? 'N/A' }}</li>
            <li>Clase: {{ $node['item_class'] ?? 'N/A' }}</li>
            <li>Standard Pack: {{ $node['standard_pack_quantity'] ?? 'N/A' }}</li>
        </ul>

        @if($hasChildren)
            <ul x-show="open" x-transition.duration.150ms class="ml-2.5 space-y-0.5 border-l border-gray-200 dark:border-gray-700 pl-4">
                @foreach($node['children'] as $child)
                    @include('livewire.guest.partials.part-number-tree-node', ['node' => $child, 'level' => $level + 1])
                @endforeach
            </ul>
        @endif
    </div>
</li>
