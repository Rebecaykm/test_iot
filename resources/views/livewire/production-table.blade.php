<div>
    <div x-data="productionTable" class="w-full h-full">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden h-full flex flex-col">
            <div class="flex-1 p-6">
                @foreach ($data as $workCenter => $dates)
                    @foreach ($dates as $plannedDate => $shifts)
                        @foreach ($shifts as $shift => $records)
                            <div class="mb-6 last:mb-0">
                                <div class="mb-4 pb-3 border-b-2 border-blue-200">
                                    <div class="flex justify-between items-center flex-wrap gap-4">
                                        <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                                            <span class="w-3 h-3 bg-blue-500 rounded-full inline-block"></span>
                                            <span>{{ $workCenter }}</span>
                                        </h2>
                                        <div class="flex space-x-2 text-lg font-semibold text-white">
                                            <span class="bg-blue-600 py-1 px-3 rounded-full">{{ $plannedDate }}</span>
                                            <span class="bg-gray-600 py-1 px-3 rounded-full">{{ $shift }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full table-auto border border-gray-200 text-sm text-gray-700">
                                        <thead class="bg-gray-100 text-left uppercase font-semibold text-gray-600">
                                            <tr class="border-b">
                                                <th class="px-4 py-3">{{ __('Número de Parte') }}</th>
                                                <th class="px-4 py-3 text-center">{{ __('Cantidad Planeada') }}</th>
                                                <th class="px-4 py-3 text-center">{{ __('Cantidad Producida') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($records as $record)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3 border">{{ $record['part_number'] }}</td>
                                                    <td class="px-4 py-3 text-center border text-blue-600 font-semibold">
                                                        {{ $record['planned_quantity'] }}
                                                    </td>
                                                    <td class="px-4 py-3 text-center border text-green-600 font-semibold">
                                                        {{ $record['produced_quantity'] }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    @script
    <script>
        Alpine.data('productionTable', () => {
            return {
                init() {
                    // Solo iniciar auto-refresh si realTime está habilitado
                    if (@json($realTime)) {
                        this.startAutoRefresh();
                    }
                },

                startAutoRefresh() {
                    // Cambiar intervalo a 10 segundos (10000ms)
                    setInterval(() => {
                        $wire.dispatchSelf("refresh-table");
                    }, 10000);
                }
            }
        });
    </script>
    @endscript
</div>
