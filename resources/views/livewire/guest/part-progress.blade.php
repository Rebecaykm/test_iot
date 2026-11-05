@php($busy = 'line,wc,part,prevShift,nextShift,goNow,clearFilters')

<div @if($isLive) wire:poll.60s="poll" @endif class="flex flex-col gap-3">
    {{-- Turno + filtros --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-ta-gray-800 dark:bg-ta-gray-900 lg:flex-row lg:items-center">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="prevShift" title="Turno anterior" aria-label="Turno anterior"
                class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-zinc-300 text-zinc-600 hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-ta-gray-700 dark:text-ta-gray-300 dark:hover:bg-ta-gray-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>

            <div class="min-w-0 flex-1 text-center lg:w-64 lg:flex-none">
                <div class="truncate text-sm font-semibold text-zinc-800 dark:text-ta-gray-100">{{ $shiftName }} · {{ $shiftHours }}</div>
                <div class="truncate text-xs capitalize text-zinc-500 dark:text-ta-gray-400">{{ $dateLabel }}</div>
            </div>

            <button type="button" wire:click="nextShift" @disabled($isLive) title="Turno siguiente" aria-label="Turno siguiente"
                class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-zinc-300 text-zinc-600 hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-40 dark:border-ta-gray-700 dark:text-ta-gray-300 dark:hover:bg-ta-gray-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>

            @unless($isLive)
                <button type="button" wire:click="goNow"
                    class="inline-flex h-10 flex-shrink-0 items-center gap-1.5 rounded-lg border border-emerald-600 px-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-600"></span></span>
                    Turno actual
                </button>
            @endunless
        </div>

        <div class="grid flex-1 grid-cols-2 gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
            <x-tw-multi-select wire:model.live="line" :options="$lineOptions" placeholder="Todas las líneas" label="Líneas" />
            {{-- wire:key con las líneas: al cambiarlas se rehace la lista de estaciones --}}
            <x-tw-multi-select wire:model.live="wc" :options="$stationOptions" placeholder="Todas las estaciones" label="Estaciones" wire:key="wc-{{ implode('-', $line) }}" />

            <div class="relative col-span-2 sm:col-span-1">
                <label class="sr-only" for="pp-part">Número de parte</label>
                <input id="pp-part" type="search" wire:model.live.debounce.400ms="part" placeholder="Buscar número de parte" autocomplete="off"
                    class="h-10 w-full rounded-lg border-zinc-300 bg-white pl-9 text-sm uppercase text-zinc-800 placeholder:normal-case placeholder:text-zinc-400 focus:border-blue-500 focus:ring-blue-500 dark:border-ta-gray-700 dark:bg-ta-gray-800 dark:text-ta-gray-100 dark:placeholder:text-ta-gray-500">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500 dark:text-ta-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            </div>

            {{-- Solo aparece si hay búsqueda o algún filtro --}}
            @if($line || $wc || trim($part) !== '')
                <button type="button" wire:click="clearFilters"
                    class="col-span-2 inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-zinc-300 bg-white px-3 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 sm:col-span-1 dark:border-ta-gray-700 dark:bg-ta-gray-800 dark:text-ta-gray-200 dark:hover:bg-ta-gray-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    Limpiar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- Esqueleto al cambiar filtro o turno (el refresco de cada minuto no lo muestra) --}}
    <div wire:loading.delay wire:target="{{ $busy }}" class="w-full">
        @include('livewire.guest.part-progress-placeholder', ['cardsOnly' => true])
    </div>

    <div wire:loading.delay.remove wire:target="{{ $busy }}" class="flex flex-col gap-3">
        {{-- Número de parte buscado: info y su cadena anterior / posterior --}}
        @if($detail)
            @php($p = $detail['part'])
            <section class="rounded-xl border border-blue-200 bg-white shadow-sm dark:border-blue-500/30 dark:bg-ta-gray-900">
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold tracking-wide text-zinc-900 dark:text-ta-gray-50">{{ $p['number'] }}</h2>
                            @if($p['item_class'])<span class="lp-class-tag">{{ $p['item_class'] }}</span>@endif
                            @include('livewire.guest.partials.part-state', ['state' => $p['state']])
                        </div>
                        <p class="mt-0.5 text-sm text-zinc-600 dark:text-ta-gray-300">{{ $p['name'] ?: 'Sin nombre' }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-ta-gray-400">{{ collect([$p['line_name'], $p['wc_name']])->filter()->join(' · ') ?: 'Sin estación' }}</p>
                    </div>
                    <div class="flex items-start justify-between gap-4 sm:flex-col sm:items-end">
                        <div class="sm:text-right">
                            <div class="text-3xl font-bold tabular-nums text-zinc-900 dark:text-ta-gray-50">{{ number_format($p['qty']) }}</div>
                            <div class="text-xs text-zinc-500 dark:text-ta-gray-400">piezas en el turno</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 border-t border-zinc-100 dark:border-ta-gray-800 md:grid-cols-2 md:divide-x md:divide-zinc-100 dark:md:divide-ta-gray-800">
                    @foreach(['prev' => 'Anteriores (componentes)', 'next' => 'Posteriores (dónde se usa)'] as $dir => $title)
                        <div class="min-w-0 {{ $loop->first ? '' : 'border-t border-zinc-100 dark:border-ta-gray-800 md:border-t-0' }}">
                            <h3 class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-ta-gray-400">{{ $title }} · {{ $detail[$dir . 'Count'] }}</h3>
                            @if($detail[$dir])
                                <div class="pb-2">@include('livewire.guest.partials.part-tree', ['nodes' => $detail[$dir], 'key' => $dir])</div>
                            @else
                                <p class="px-3 pb-3 text-sm text-zinc-500 dark:text-ta-gray-400">Ninguno con orden o producción en este turno.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @forelse($lines as $ln)
            <section wire:key="line-{{ $ln['id'] }}" class="flex flex-col gap-2">
                <h2 class="flex items-center gap-2 text-base font-bold text-zinc-800 dark:text-ta-gray-100">
                    <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background-color: {{ $ln['color'] ?: '#71717a' }}"></span>
                    {{ $ln['name'] }}
                    <span class="text-sm font-medium tabular-nums text-zinc-500 dark:text-ta-gray-400">{{ number_format($ln['total']) }}</span>
                </h2>

                <div class="grid grid-cols-1 items-start gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach($ln['stations'] as $st)
                        <article wire:key="wc-{{ $st['id'] }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-ta-gray-800 dark:bg-ta-gray-900">
                            <header class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-ta-gray-800">
                                <a href="{{ route('guest.progress.station', $st['id']) }}" class="lp-wc-link">{{ $st['name'] }}</a>
                                <span class="text-sm font-semibold tabular-nums text-zinc-500 dark:text-ta-gray-400">{{ number_format($st['total']) }}</span>
                            </header>
                            <div class="divide-y divide-zinc-100 dark:divide-ta-gray-800">
                                @foreach($st['parts'] as $pt)
                                    @include('livewire.guest.partials.part-row', ['part' => $pt, 'station' => false, 'key' => 'p-' . $pt['id']])
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-4 py-12 text-center dark:border-ta-gray-700 dark:bg-ta-gray-900">
                <p class="font-semibold text-zinc-700 dark:text-ta-gray-200">Sin producción en este turno</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-ta-gray-400">
                    {{ $search !== '' || $line || $wc ? 'Prueba con otros filtros o un turno anterior.' : 'Aún no hay piezas registradas.' }}
                </p>
            </div>
        @endforelse
    </div>
</div>
