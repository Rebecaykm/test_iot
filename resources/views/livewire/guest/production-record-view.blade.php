<div>
    <div x-data="table" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Columna izquierda - Turno anterior -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-4">
                @if($previousData && count($previousData) > 0)
                    @foreach($previousData as $workCenterName => $dates)
                        @foreach($dates as $plannedDate => $shifts)
                            @foreach($shifts as $shiftName => $records)
                                <div class="mb-6">
                                    <div class="bg-white rounded-lg shadow-sm">
                                        <div class="text-white p-4 rounded-t-lg">
                                            <div class="flex justify-between items-center">
                                                <h4 class="text-lg font-semibold">
                                                    <span class="bg-blue-600 text-white py-2 px-3 rounded-full uppercase">
                                                        {{ $workCenterName }}
                                                    </span>
                                                </h4>
                                                <div class="flex space-x-1">
                                                    <h4 class="text-lg font-semibold">
                                                        <span class="bg-gray-500 text-white py-2 px-3 rounded-full">
                                                            {{ $plannedDate }}
                                                        </span>
                                                    </h4>
                                                    <h4 class="text-lg font-semibold">
                                                        <span class="bg-gray-500 text-white py-2 px-3 rounded-full">
                                                            {{ $shiftName }}
                                                        </span>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="overflow-x-auto">
                                            <table class="min-w-full border border-gray-200">
                                                <thead class="bg-gray-100">
                                                <tr class="text-xs font-semibold uppercase text-gray-600">
                                                    <th class="px-3 py-2 text-left">Número de Parte</th>
                                                    <th class="px-3 py-2 text-center">Planeado</th>
                                                    <th class="px-3 py-2 text-center">Producido</th>
                                                    <th class="px-3 py-2 text-center">Diferencia</th>
                                                    <th class="px-3 py-2 text-center">Estado</th>
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                @foreach($records as $record)
                                                    <tr class="text-xs text-gray-700 hover:bg-gray-50">
                                                        <td class="px-3 py-2 border">{{ $record['part_number'] }}</td>
                                                        <td class="px-3 py-2 text-center border">{{ $record['planned_quantity'] }}</td>
                                                        <td class="px-3 py-2 text-center border">{{ $record['produced_quantity'] }}</td>
                                                        <td class="px-3 py-2 text-center border">
                                                            @if($record['difference'] < 0)
                                                                <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                                                    {{ $record['difference'] }}
                                                                </span>
                                                            @elseif($record['difference'] > 0)
                                                                <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                                                    +{{ $record['difference'] }}
                                                                </span>
                                                            @else
                                                                <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">
                                                                    {{ $record['difference'] }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-center border">
                                                            @if(strtolower($record['status']) == 'pendiente')
                                                                <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                                                    {{ $record['status'] }}
                                                                </span>
                                                            @elseif(strtolower($record['status']) == 'en proceso')
                                                                <span class="px-2 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-full">
                                                                    {{ $record['status'] }}
                                                                </span>
                                                            @elseif(strtolower($record['status']) == 'completado')
                                                                <span class="px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">
                                                                    {{ $record['status'] }}
                                                                </span>
                                                            @else
                                                                <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">
                                                                    {{ $record['status'] }}
                                                                </span>
                                                            @endif
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
                    <div class="text-center py-4 text-gray-500">
                        No hay datos disponibles para el turno anterior
                    </div>
                @endif
            </div>
        </div>

        <!-- Columna derecha - Turno actual -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-4">
                @if($currentData && count($currentData) > 0)
                    @foreach($currentData as $workCenterName => $dates)
                        @foreach($dates as $plannedDate => $shifts)
                            @foreach($shifts as $shiftName => $records)
                                <div class="mb-6">
                                    <div class="text-white p-4 rounded-t-lg">
                                        <div class="flex justify-between items-center">
                                            <h4 class="text-lg font-semibold">
                                                <span class="bg-blue-600 text-white py-2 px-3 rounded-full uppercase">
                                                    {{ $workCenterName }}
                                                </span>
                                            </h4>
                                            <div class="flex space-x-1">
                                                <h4 class="text-lg font-semibold">
                                                    <span class="bg-gray-500 text-white py-2 px-3 rounded-full">
                                                        {{ $plannedDate }}
                                                    </span>
                                                </h4>
                                                <h4 class="text-lg font-semibold">
                                                    <span class="bg-gray-500 text-white py-2 px-3 rounded-full">
                                                        {{ $shiftName }}
                                                    </span>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border border-gray-200">
                                            <thead class="bg-gray-100">
                                            <tr class="text-xs font-semibold uppercase text-gray-600">
                                                <th class="px-3 py-2 text-left">Número de Parte</th>
                                                <th class="px-3 py-2 text-center">Planeado</th>
                                                <th class="px-3 py-2 text-center">Producido</th>
                                                <th class="px-3 py-2 text-center">Diferencia</th>
                                                <th class="px-3 py-2 text-center">Estado</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                            @foreach($records as $record)
                                                <tr class="text-xs text-gray-700 hover:bg-gray-50">
                                                    <td class="px-3 py-2 border">{{ $record['part_number'] }}</td>
                                                    <td class="px-3 py-2 text-center border">{{ $record['planned_quantity'] }}</td>
                                                    <td class="px-3 py-2 text-center border">{{ $record['produced_quantity'] }}</td>
                                                    <td class="px-3 py-2 text-center border">
                                                        @if($record['difference'] < 0)
                                                            <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                                                {{ $record['difference'] }}
                                                            </span>
                                                        @elseif($record['difference'] > 0)
                                                            <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                                                +{{ $record['difference'] }}
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">
                                                                {{ $record['difference'] }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-center border">
                                                        @if(strtolower($record['status']) == 'pendiente')
                                                            <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                                                {{ $record['status'] }}
                                                            </span>
                                                        @elseif(strtolower($record['status']) == 'en proceso')
                                                            <span class="px-2 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-full">
                                                                {{ $record['status'] }}
                                                            </span>
                                                        @elseif(strtolower($record['status']) == 'completado')
                                                            <span class="px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">
                                                                {{ $record['status'] }}
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">
                                                                {{ $record['status'] }}
                                                            </span>
                                                        @endif
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
                @else
                    <div class="text-center py-4 text-gray-500">
                        No hay datos disponibles para el turno actual
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data("chart", () => {
        return {
            init() {
                setInterval(() => {
                    $wire.dispatchSelf("refresh")
                }, 30000)
            }
        }
    })
</script>
@endscript
