{{--
    Ramas de números de parte (anteriores o posteriores): cada hijo cuelga de su padre con una línea.
    Los que están en cero y solo conectan con uno que sí tuvo actividad van atenuados.
--}}
<ul class="ml-4 border-l border-zinc-300 dark:border-ta-gray-700">
    @foreach($nodes as $i => $n)
        @php($k = $key . '-' . $i)
        <li class="relative">
            <span class="absolute left-0 top-6 w-3 border-t border-zinc-300 dark:border-ta-gray-700" aria-hidden="true"></span>
            <div class="ml-3 {{ $n['active'] ? '' : 'opacity-60' }}">
                @include('livewire.guest.partials.part-row', ['part' => $n, 'station' => true, 'key' => $k])
            </div>
            @if($n['children'])
                <div class="ml-3">
                    @include('livewire.guest.partials.part-tree', ['nodes' => $n['children'], 'key' => $k])
                </div>
            @endif
        </li>
    @endforeach
</ul>
