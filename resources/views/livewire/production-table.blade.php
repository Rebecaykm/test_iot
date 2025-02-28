<div wire:poll.1s="refreshTable">

    <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-4"> -->
    <div class="grid grid-cols-1 gap-4">
        @foreach ($data as $workCenter => $dates)
            @foreach ($dates as $plannedDate => $shifts)
                @foreach ($shifts as $shift => $records)
                <div class="bg-white shadow-sm rounded-lg mb-4">
                    <div class="text-white p-4 rounded-t-lg">
                        <div class="flex justify-between items-center uppercase">
                            <h4 class="text-lg font-semibold">
                                <span
                                    class="bg-blue-600 text-white py-2 px-3 rounded-full">{{ $workCenter }}</span>
                            </h4>
                            <div class="flex space-x-4">
                                <h4 class="text-lg font-semibold">
                                    <span
                                        class="bg-blue-600 text-white py-2 px-3 rounded-full">{{ $plannedDate }}</span>
                                </h4>
                                <h4 class="text-lg font-semibold">
                                    <span
                                        class="bg-gray-600 text-white py-2 px-3 rounded-full">{{ $shift }}</span>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">

                        <div class="overflow-x-auto">
                            <table class="min-w-full table-auto border border-gray-300">
                                <thead class="bg-gray-100">
                                    <tr
                                        class="font-semibold uppercase tracking-wide text-gray-600 border-b">
                                        <th class="px-4 py-3">{{ __('Número de Parte') }}</th>
                                        <th class="px-4 py-3 text-center">{{ __('Cantidad Planeada') }}</th>
                                        <th class="px-4 py-3 text-center">{{ __('Cantidad Producida') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($records as $record)
                                        <tr class="text-xs text-gray-700 hover:bg-gray-100">
                                            <td class="px-4 py-3 text-sm border">
                                                {{ $record['part_number'] }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center border">
                                                {{ $record['planned_quantity'] }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center border">
                                                {{ $record['produced_quantity'] }}
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
    </div>

</div>
