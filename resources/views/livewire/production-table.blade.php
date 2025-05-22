<div>
    <div x-data="table" class="container mx-auto">
        @foreach ($data as $workCenter => $dates)
            @foreach ($dates as $plannedDate => $shifts)
                @foreach ($shifts as $shift => $records)
                    <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow">
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

    @script
        <script>
            Alpine.data('table', () => {
                return {
                    lastUpdate: null,
                    init() {
                        this.startAutoRefresh();
                    },
                    startAutoRefresh() {
                        setInterval(() => {
                            this.checkForUpdates();
                        }, 1000);
                    },
                    checkForUpdates() {
                        let currentTime = new Date().getTime();
                        if (!this.lastUpdate || (currentTime - this.lastUpdate) > 1000) {
                            this.refreshTable();
                            this.lastUpdate = currentTime;
                        }
                    },
                    refreshTable() {
                        $wire.dispatchSelf("refresh-table");
                    }
                }
            });
        </script>
    @endscript
</div>
