{{-- poll(): si los datos no cambiaron, el servidor no re-renderiza nada --}}
<div wire:poll.30s.visible="poll" class="flex flex-col min-h-0">
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col min-h-0">

        {{-- Encabezado: número de parte y navegación de semana --}}
        <div class="px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700/70 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between flex-shrink-0">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 min-w-0">
                <form wire:submit="applyPart" class="flex items-center gap-2">
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="text" wire:model="partInput" placeholder="Número de parte..." autocomplete="off"
                            class="w-full sm:w-64 pl-8 pr-3 py-1.5 text-sm uppercase border border-zinc-300 dark:border-zinc-700/70 rounded-lg bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 placeholder-zinc-500 placeholder:normal-case focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    </div>
                    <button type="submit"
                        class="inline-flex h-[34px] items-center rounded-lg bg-[#2563eb] px-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                        Buscar
                    </button>
                    @if($partNumber !== '')
                        <button type="button" wire:click="clearPart"
                            class="inline-flex h-[34px] items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            Limpiar
                        </button>
                    @endif
                </form>

                @if($notFound)
                    <p class="text-xs font-medium text-red-600 dark:text-red-400">No existe el número de parte {{ $partNumber }}.</p>
                @endif
            </div>

            <div class="flex items-center justify-end gap-1.5">
                {{-- Cargando (cambio de semana o búsqueda; el refresco automático no lo muestra) --}}
                <svg wire:loading.delay wire:target="prevWeek,nextWeek,currentWeek,applyPart,clearPart" class="mr-1 h-4 w-4 animate-spin text-[#2563eb] dark:text-[#60a5fa]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                @unless($isCurrentWeek)
                    <button wire:click="currentWeek" type="button"
                        class="px-2.5 py-1 mr-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        Semana actual
                    </button>
                @endunless
                <button wire:click="prevWeek" type="button" title="Semana anterior"
                    class="p-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200 whitespace-nowrap min-w-[10rem] text-center">{{ $weekLabel }}</span>
                <button wire:click="nextWeek" type="button" title="Semana siguiente"
                    class="p-1 rounded-lg border border-zinc-300 dark:border-zinc-700/70 text-zinc-500 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>

        {{-- Tabla: días (divididos por turno) fijos arriba; estación y número de parte fijos a la izquierda --}}
        {{--
            wire:replace.self: cuando los datos cambian, la tabla se reemplaza completa en vez de
            compararse nodo por nodo (con ~12,000 celdas el morph de Livewire congelaba la pantalla).
            El scroll lo lleva el div, así que no se pierde la posición.
        --}}
        <div class="min-h-0 overflow-auto transition-opacity" wire:loading.delay.class="opacity-50" wire:target="prevWeek,nextWeek,currentWeek,applyPart,clearPart">
            <table wire:replace.self class="w-full min-w-[1700px] table-fixed border-collapse text-sm">
                <colgroup>
                    <col class="w-28">
                    <col class="w-36">
                    <col class="w-16">
                    @foreach($days as $day)
                        @foreach($shifts as $shift)
                            <col>
                        @endforeach
                    @endforeach
                    <col class="w-24">
                </colgroup>
                <thead class="sticky top-0 z-20">
                    <tr>
                        <th rowspan="2" class="sticky left-0 z-30 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700/70">
                            Estación
                        </th>
                        <th rowspan="2" class="sticky left-28 z-30 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-zinc-200 dark:border-zinc-700/70">
                            Número de Parte
                        </th>
                        <th rowspan="2" class="sticky left-64 z-30 bg-zinc-50 dark:bg-zinc-800 px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-r border-zinc-200 dark:border-zinc-700/70">
                            Clase
                        </th>
                        @foreach($days as $day)
                            <th colspan="{{ count($shifts) }}" class="px-2 pt-1.5 pb-1 text-center border-l border-zinc-200 dark:border-zinc-700/70 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-[#1c2331]' : 'bg-zinc-50 dark:bg-zinc-800' }}">
                                <span class="text-xs font-bold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-200' }}">{{ $day['name'] }}</span>
                                <span class="text-[11px] font-normal {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $day['label'] }}</span>
                            </th>
                        @endforeach
                        <th rowspan="2" class="bg-zinc-50 dark:bg-zinc-800 px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300 border-b border-l border-zinc-200 dark:border-zinc-700/70">
                            Cant. Sin Orden
                        </th>
                    </tr>
                    <tr>
                        @foreach($days as $day)
                            @foreach($shifts as $si => $shift)
                                {{-- Turno: el recuadro completo lleva el color del turno (ámbar = diurno, índigo = nocturno) --}}
                                <th class="pp-shift-th {{ $shift['abbr'] === 'D' ? 'pp-shift-D' : 'pp-shift-N' }}">{{ $shift['name'] }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>

                @include('livewire.guest.part-progress-body')
            </table>
        </div>

        {{-- Pie: leyenda y última actualización --}}
        <div class="pl-4 pr-16 py-2 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400 flex-shrink-0">
            <div class="flex flex-wrap items-center gap-3 font-medium text-zinc-600 dark:text-zinc-300">
                <span class="flex items-center gap-1.5"><span class="w-4 h-2 rounded-sm border border-zinc-400 bg-[rgba(161,161,170,0.15)] dark:border-zinc-500 dark:bg-[rgba(113,113,122,0.20)]"></span>Plan</span>
                <span class="flex items-center gap-1.5"><span class="w-4 h-2 rounded-sm border border-[#059669] bg-[rgba(16,185,129,0.15)] dark:bg-[rgba(16,185,129,0.22)]"></span>Real</span>
                <span class="flex items-center gap-1.5"><span class="w-4 h-2 rounded-sm border border-[#ea580c] bg-[rgba(234,88,12,0.15)] dark:bg-[rgba(234,88,12,0.22)]"></span>Sin orden</span>
                <span class="text-zinc-500 dark:text-zinc-400">Real / Plan por turno</span>
            </div>
            {{--
                La hora se actualiza también cuando poll() se salta el render. Si solo cambiaron algunos
                renglones, poll() los manda en part-progress-patch y aquí se reemplazan por su id.
            --}}
            <div class="flex items-center gap-1.5" x-data="{ time: @js($lastUpdated) }"
                @part-progress-checked.window="time = $event.detail.time"
                @part-progress-patch.window="
                    time = $event.detail.time;
                    for (const [id, html] of Object.entries($event.detail.rows)) {
                        const row = document.getElementById(id);
                        if (row) row.outerHTML = html;
                    }
                ">
                <span class="w-2 h-2 rounded-full bg-[#059669] animate-pulse"></span>
                Actualización automática · <span x-text="time">{{ $lastUpdated }}</span>
            </div>
        </div>
    </div>
</div>
