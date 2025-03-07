<div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($groupedByWorkCenter as $workCenterName => $dates)
                @foreach ($dates as $plannedDate => $shifts)
                    @foreach ($shifts as $shiftName => $records)
                    <div class="bg-white shadow-sm rounded-lg">
                        <div class="text-white p-4 rounded-t-lg">
                            <div class="flex justify-between items-center">
                                <h4 class="text-lg font-semibold">
                                    <span class="bg-blue-600 text-white py-2 px-3 rounded-full text-uppercase">{{ $workCenterName }}</span>
                                </h4>
                                <div class="flex space-x-4">
                                    <h4 class="text-lg font-semibold">
                                        <span class="bg-blue-600 text-white py-2 px-3 rounded-full">{{ $plannedDate }}</span>
                                    </h4>
                                    <h4 class="text-lg font-semibold">
                                        <span class="bg-gray-600 text-white py-2 px-3 rounded-full text-uppercase">{{ $shiftName }}</span>
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($records as $record)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full table-auto border border-gray-300">
                                        <thead class="bg-gray-100">
                                            <tr class="text-lg font-semibold uppercase tracking-wide text-gray-600 border-b">
                                                <th class="px-4 py-3" colspan="2">{{ $record['part_number'] }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr class="text-gray-700 hover:bg-gray-100">
                                                <td class="px-4 py-3 text-sm text-center border">
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-xs text-gray-400">{{ __('Plan') }}</span>
                                                        <span class="font-semibold mt-1">{{ $record['planned_quantity'] }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="text-gray-700 hover:bg-gray-100">
                                                <td class="px-4 py-3 text-sm text-center border">
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-xs text-gray-400">{{ __('Real') }}</span>
                                                        <span class="font-semibold text-md text-md mt-1">{{ $record['produced_quantity'] }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="text-gray-700 hover:bg-gray-100">
                                                <td colspan="2" class="px-4 py-3 text-center border">
                                                    <div class="flex flex-col items-center">
                                                        <span class="text-xs text-gray-400">{{ __('Diferencia') }}</span>
                                                        @if ($record['planned_quantity'] > $record['produced_quantity'])
                                                            <span class="px-2 py-1 font-semibold text-md leading-tight text-red-700 bg-red-100 rounded-full">
                                                                {{ $record['difference'] }}
                                                            </span>
                                                        @elseif ($record['planned_quantity'] < $record['produced_quantity'])
                                                            <span class="px-2 py-1 font-semibold text-md leading-tight text-green-700 bg-green-100 rounded-full">
                                                                {{ $record['difference'] }}
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-1 font-semibold text-md leading-tight text-gray-700 bg-gray-100 rounded-full">
                                                                {{ $record['difference'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                @endforeach
            @endforeach
        </div>
    </div>
</div>
