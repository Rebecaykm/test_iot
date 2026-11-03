{{--
    Renglón de número de parte: número + clase + nombre, estado y cantidad producida.
    @include y no componente: con ~400 renglones los componentes anónimos tardaban ~0.5 s en pintarse.
--}}
<div wire:key="{{ $key }}" class="flex items-center gap-3 px-3 py-2.5">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 min-w-0">
            <span class="truncate text-sm font-semibold tracking-wide text-zinc-800 dark:text-ta-gray-100">{{ $part['number'] }}</span>
            @if($part['item_class'])
                <span class="lp-class-tag flex-shrink-0">{{ $part['item_class'] }}</span>
            @endif
        </div>
        <div class="truncate text-xs text-zinc-500 dark:text-ta-gray-400">
            {{ $part['name'] ?: 'Sin nombre' }}@if(!empty($station) && $part['wc_name']) · {{ $part['wc_name'] }}@endif
        </div>
    </div>
    @include('livewire.guest.partials.part-state', ['state' => $part['state']])
    <span class="w-16 flex-shrink-0 text-right text-lg tabular-nums {{ $part['qty'] > 0 ? 'font-bold text-zinc-900 dark:text-ta-gray-50' : 'font-medium text-zinc-500 dark:text-ta-gray-400' }}">{{ number_format($part['qty']) }}</span>
</div>
