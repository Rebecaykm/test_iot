{{--
    Renglón de número de parte: punto de estado + número; abajo clase + nombre; a la derecha real / plan.
    @include y no componente: con ~400 renglones los componentes anónimos tardaban ~0.5 s en pintarse.
--}}
<div wire:key="{{ $key }}" class="flex items-center gap-3 px-3 py-2.5">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 min-w-0">
            {{-- Estado como punto: verde que parpadea = en proceso, rojo = detenido --}}
            @if($part['state'] === 'live')
                <span class="lp-dot lp-dot-green relative top-0 right-0 flex-shrink-0" title="En proceso"></span><span class="sr-only">En proceso</span>
            @else
                <span class="lp-dot-stop" title="Detenido"></span><span class="sr-only">Detenido</span>
            @endif
            <span class="truncate text-sm font-semibold tracking-wide text-zinc-800 dark:text-ta-gray-100">{{ $part['number'] }}</span>
        </div>
        <div class="mt-0.5 flex items-center gap-1.5 min-w-0 text-xs text-zinc-500 dark:text-ta-gray-400">
            @if($part['item_class'])
                <span class="lp-class-tag flex-shrink-0">{{ $part['item_class'] }}</span>
            @endif
            <span class="truncate">{{ $part['name'] ?: 'Sin nombre' }}@if(!empty($station) && $part['wc_name']) · {{ $part['wc_name'] }}@endif</span>
        </div>
    </div>
    {{-- Real / Plan: el real grande y el plan del turno tenue --}}
    <span class="min-w-16 flex-shrink-0 whitespace-nowrap text-right tabular-nums" title="Real {{ number_format($part['qty']) }} · Plan {{ number_format($part['plan']) }}">
        <span class="text-lg {{ $part['qty'] > 0 ? 'font-bold text-zinc-900 dark:text-ta-gray-50' : 'font-medium text-zinc-500 dark:text-ta-gray-400' }}">{{ number_format($part['qty']) }}</span>@if($part['plan'] > 0)<span class="text-sm font-medium text-zinc-500 dark:text-ta-gray-400"> / {{ number_format($part['plan']) }}</span>@endif
    </span>
</div>
