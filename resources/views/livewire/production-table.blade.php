<div wire:poll.30s="refreshTable">
    <div x-data="productionTable" class="w-full h-full">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden h-full flex flex-col">
            @if(!empty($data))
                @foreach ($data as $workCenter => $dates)
                    @foreach ($dates as $plannedDate => $shifts)
                        @foreach ($shifts as $shift => $records)
                            <div wire:key="header-{{ $workCenter }}-{{ $plannedDate }}-{{ $shift }}">
                                <!-- Header con información -->
                                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-600">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                        <h2 class="text-xl font-bold text-blue-800 dark:text-blue-200 flex items-center">
                                            <span class="w-2 h-2 bg-blue-500 rounded-full inline-block mr-2"></span>
                                            {{ $workCenter }}
                                        </h2>
                                        <div class="flex flex-wrap gap-2 text-sm font-medium">
                                            <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">{{ $plannedDate }}</span>
                                            <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">{{ $shift }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabla sin espacios internos -->
                                <div class="flex-1 overflow-hidden">
                                    <div class="overflow-x-auto h-full">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-200 dark:bg-gray-700 text-xs uppercase text-gray-700 dark:text-gray-300 sticky top-0">
                                                <tr>
                                                    <th class="px-4 py-3 text-left font-semibold">Número de Parte</th>
                                                    <th class="px-4 py-3 text-center font-semibold">Cantidad Planeada</th>
                                                    <th class="px-4 py-3 text-center font-semibold">Cantidad Producida</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                                                @foreach ($records as $index => $record)
                                                    <tr wire:key="record-{{ $workCenter }}-{{ $plannedDate }}-{{ $shift }}-{{ $index }}"
                                                        class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-medium">{{ $record['part_number'] }}</td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="px-2 py-1 rounded-full font-semibold text-xs text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/50">
                                                                {{ $record['planned_quantity'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="px-2 py-1 rounded-full font-semibold text-xs text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/50">
                                                                {{ $record['produced_quantity'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @endforeach
            @else
                <div class="flex items-center justify-center h-full">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>No hay datos de producción disponibles</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @script
    <script>
        Alpine.data('productionTable', () => {
            return {
                init() {
                    if (@json($realTime)) {
                        this.startAutoRefresh();
                    }
                },

                startAutoRefresh() {
                    setInterval(() => {
                        $wire.dispatchSelf("refresh-table");
                    }, 30000);
                }
            }
        });
    </script>
    @endscript
</div>
