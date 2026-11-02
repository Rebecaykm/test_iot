{{-- Esqueleto de una gráfica (press-production y su timeline) mientras llega (carga diferida) --}}
<div class="flex flex-col flex-1 min-h-[360px]" aria-busy="true" aria-label="Cargando gráfica">
    <div class="bg-white dark:bg-ta-gray-900 rounded-xl border border-zinc-200 dark:border-ta-gray-800 shadow-sm flex-1 flex flex-col p-4 sm:p-6 gap-4">
        <div class="flex justify-end gap-3">
            @for($i = 0; $i < 3; $i++)
                <div class="h-3 w-14 rounded bg-zinc-100 dark:bg-ta-gray-800 animate-pulse"></div>
            @endfor
        </div>
        <div class="flex-1 flex items-end gap-2 sm:gap-4 border-b border-l border-zinc-200 dark:border-ta-gray-800 pl-2 min-h-[240px]">
            @foreach([30, 45, 60, 52, 75, 68, 85, 40, 55, 35, 20, 25] as $h)
                <div class="flex-1 rounded-t bg-zinc-100 dark:bg-ta-gray-800 animate-pulse" style="height: {{ $h }}%"></div>
            @endforeach
        </div>
    </div>
</div>
