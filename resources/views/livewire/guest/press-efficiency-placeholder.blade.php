@php
    // Esqueleto de la carga inicial (carga diferida): misma forma que la vista para que nada salte al llegar los datos
    $card = 'rounded-2xl border border-gray-200 bg-white dark:border-ta-gray-800 dark:bg-white/[0.03]';
    $bone = 'rounded bg-gray-200 dark:bg-ta-gray-800 motion-safe:animate-pulse';
@endphp

<div class="flex flex-col gap-4 md:gap-6" aria-busy="true">
    <span class="sr-only" role="status">Cargando eficiencia de prensas…</span>

    {{-- Filtros --}}
    <div class="{{ $card }} px-4 py-3 sm:px-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between" aria-hidden="true">
        <div class="h-9 w-full sm:w-56 rounded-lg {{ $bone }}"></div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="h-9 w-28 rounded-lg {{ $bone }}"></div>
            <div class="h-9 w-48 rounded-lg {{ $bone }}"></div>
            <div class="h-9 w-56 rounded-lg {{ $bone }}"></div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="{{ $card }} overflow-hidden" aria-hidden="true">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-xs">
                <thead>
                    <tr class="border-b border-brand-100 bg-brand-50 dark:border-brand-500/20 dark:bg-brand-500/10">
                        @foreach(range(1, 11) as $c)
                            <th class="px-3 py-3.5 first:pl-6"><div class="h-3 w-16 {{ $bone }}"></div></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-ta-gray-800">
                    @include('livewire.guest.partials.press-efficiency-skeleton-rows', ['bone' => $bone])
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 dark:border-ta-gray-800 px-5 py-3 sm:px-6"><div class="h-3 w-48 {{ $bone }}"></div></div>
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6" aria-hidden="true">
        @foreach(['h-[380px]', 'h-[380px]', 'h-[460px] xl:col-span-2'] as $size)
            <div class="{{ $card }} overflow-hidden {{ str_contains($size, 'col-span') ? 'xl:col-span-2' : '' }}">
                <div class="border-b border-brand-100 bg-brand-50 px-5 py-3.5 dark:border-brand-500/20 dark:bg-brand-500/10">
                    <div class="h-4 w-72 max-w-full {{ $bone }}"></div>
                </div>
                <div class="flex items-end gap-3 px-6 py-6 {{ explode(' ', $size)[0] }}">
                    @foreach([70, 45, 85, 30, 60, 50, 75, 40] as $h)
                        <div class="flex-1 rounded-t {{ $bone }}" style="height: {{ $h }}%"></div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
