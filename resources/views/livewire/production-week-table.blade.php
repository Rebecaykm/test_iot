<div x-data="weekTable" data-live="{{ $isCurrentWeek ? '1' : '0' }}" class="w-full h-full">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">

        {{-- Encabezado --}}
        <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-600 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between flex-shrink-0">
            {{-- Buscador de número de parte --}}
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar número de parte..."
                    class="w-full sm:w-64 pl-8 pr-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            </div>

            {{-- Navegación de semana --}}
            <div class="flex items-center justify-end gap-1.5">
                <button wire:click="prevWeek" type="button"
                    class="p-1 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap min-w-[8rem] text-center">{{ $weekLabel }}</span>
                <button wire:click="nextWeek" type="button" @disabled($isCurrentWeek)
                    class="p-1 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="overflow-auto max-h-[545px]">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 top-0 z-30 w-36 whitespace-nowrap bg-gray-100 dark:bg-gray-700 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                            Número de Parte
                        </th>
                        <th class="sticky left-36 top-0 z-30 bg-gray-100 dark:bg-gray-700 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 border-b border-r border-gray-200 dark:border-gray-600">
                            Turno
                        </th>
                        @foreach($days as $day)
                            <th class="sticky top-0 z-20 px-3 py-2 text-center border-b border-l border-gray-200 dark:border-gray-600 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-blue-900/60' : 'bg-gray-100 dark:bg-gray-700' }}">
                                <div class="text-xs font-bold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-200' }}">{{ $day['name'] }}</div>
                                <div class="text-[11px] font-normal {{ $day['isToday'] ? 'text-blue-500 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $day['label'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($parts as $part)
                        @foreach($part['shifts'] as $i => $row)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                {{-- Número de parte (agrupa los turnos con rowspan) --}}
                                @if($i === 0)
                                    <td rowspan="{{ count($part['shifts']) }}"
                                        class="sticky left-0 z-10 w-36 bg-white dark:bg-gray-800 px-4 py-1.5 align-middle text-xs font-semibold text-gray-800 dark:text-gray-100 tracking-wide border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                        {{ $part['part_number'] }}
                                    </td>
                                @endif

                                {{-- Turno --}}
                                <td class="sticky left-36 z-10 bg-white dark:bg-gray-800 px-3 py-1.5 text-center align-middle border-r border-gray-200 dark:border-gray-700">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                        {{ $row['shift'] === 'D'
                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                            : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' }}">
                                        {{ $row['shift_name'] }}
                                    </span>
                                </td>

                                {{-- Celdas por día --}}
                                @foreach($days as $day)
                                    @php $cell = $row['cells'][$day['date']] ?? []; @endphp
                                    <td class="text-center align-middle border-l border-gray-100 dark:border-gray-700/60 {{ empty($cell) && $day['isToday'] ? 'bg-blue-50/40 dark:bg-blue-900/10' : '' }}">
                                        @forelse($cell as $rec)
                                            @php
                                                $rcolor = $rec['real'] < $rec['plan']
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : ($rec['real'] > $rec['plan']
                                                        ? 'text-yellow-600 dark:text-yellow-400'
                                                        : 'text-green-600 dark:text-green-400');
                                                // Relleno suave del renglón según su propia cantidad real
                                                $rbg = $rec['real'] < $rec['plan']
                                                    ? 'bg-red-50 dark:bg-red-900/20'
                                                    : ($rec['real'] > $rec['plan']
                                                        ? 'bg-yellow-50 dark:bg-yellow-900/20'
                                                        : 'bg-green-50 dark:bg-green-900/20');
                                            @endphp
                                            <div class="px-1 py-1.5 whitespace-nowrap leading-snug text-[11px] {{ $rbg }} {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                                <span class="text-gray-500 dark:text-gray-400 font-medium">P: {{ number_format($rec['plan']) }}</span>
                                                <span class="mx-0.5 text-gray-300 dark:text-gray-600">·</span>
                                                <span class="font-bold {{ $rcolor }}">R: {{ number_format($rec['real']) }}</span>
                                            </div>
                                        @empty
                                            <span class="inline-block px-1 py-1.5 text-gray-300 dark:text-gray-600 text-[11px]">–</span>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ count($days) + 2 }}" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                No hay producción registrada en esta semana.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie: leyenda (izquierda) y última actualización (derecha) --}}
        <div class="px-6 py-2.5 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">
            {{-- Leyenda de colores --}}
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Faltante</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>Completado</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>Excedente</span>
            </div>

            {{-- Última actualización --}}
            <div class="flex items-center gap-1.5">
                Última actualización {{ $lastUpdated }}
            </div>
        </div>
    </div>

    @script
    <script>
        Alpine.data('weekTable', () => ({
            init() {
                // Auto-actualización cada 30 s, solo en la semana actual (igual que la línea de tiempo)
                setInterval(() => {
                    if (this.$root.dataset.live === '1') {
                        $wire.$refresh();
                    }
                }, 30000);
            }
        }));
    </script>
    @endscript
</div>
