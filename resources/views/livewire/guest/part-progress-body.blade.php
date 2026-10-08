{{--
    Cuerpo de la tabla de /guest/part-progress. Los renglones de número de parte ya vienen
    armados como HTML (PartProgress::rowHtml) y en caché; aquí solo se imprimen, y el
    refresco automático los reemplaza uno por uno por su id.
--}}
@forelse($view['areas'] as $area)
    <tbody>
        {{-- Área --}}
        <tr>
            <td colspan="{{ $view['colCount'] }}" class="bg-zinc-100 dark:bg-zinc-950 px-4 py-2 border-b border-zinc-200 dark:border-zinc-800">
                <span class="sticky left-4 text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">{{ $area['name'] }}</span>
            </td>
        </tr>

        @foreach($area['lines'] as $line)
            {{-- Línea: con su color, igual que en / --}}
            <tr>
                <td colspan="{{ $view['colCount'] }}" class="bg-white dark:bg-zinc-900 px-4 pt-3 pb-2 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="sticky left-4 inline-flex items-center gap-2.5">
                        <span class="w-1 h-5 rounded-full" style="background-color: {{ $line['color'] ?: '#71717a' }}"></span>
                        <span class="text-base font-semibold text-zinc-800 dark:text-zinc-100">{{ $line['name'] }}</span>
                    </span>
                </td>
            </tr>
            {!! implode('', array_map(fn ($id) => $view['rowsHtml'][$id], $line['rows'])) !!}
        @endforeach
    </tbody>
@empty
    <tbody>
        <tr>
            <td colspan="{{ $view['colCount'] }}" class="px-4 py-16 text-center text-zinc-500 dark:text-zinc-400">
                @if($view['notFound'])
                    No existe el número de parte {{ $partNumber }}.
                @elseif($view['family'])
                    El número de parte {{ $view['family']['number'] }} y sus relacionados no tienen estación asignada.
                @else
                    No hay producción ni plan en esta semana.
                @endif
            </td>
        </tr>
    </tbody>
@endforelse
