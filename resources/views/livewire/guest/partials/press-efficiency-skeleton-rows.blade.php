{{-- Filas de esqueleto con la forma de la tabla de eficiencia (5 = renglones por página, 11 columnas) --}}
@foreach(range(1, 5) as $i)
    <tr>
        <td class="px-5 py-3.5 sm:pl-6"><div class="h-3 w-20 {{ $bone }}"></div></td>
        <td class="px-3 py-3.5"><div class="h-3 w-24 {{ $bone }}"></div></td>
        <td class="px-3 py-3.5"><div class="h-3 w-20 {{ $bone }}"></div><div class="mt-1.5 h-3 w-20 {{ $bone }}"></div></td>
        <td class="px-3 py-3.5"><div class="h-3 w-32 {{ $bone }}"></div></td>
        @foreach(range(1, 6) as $c)
            <td class="px-3 py-3.5"><div class="ml-auto h-3 w-12 {{ $bone }}"></div></td>
        @endforeach
        <td class="px-5 py-3.5 sm:pr-6"><div class="ml-auto h-5 w-16 rounded-full {{ $bone }}"></div></td>
    </tr>
@endforeach
