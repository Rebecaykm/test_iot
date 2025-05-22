<div>
    <div x-data="table" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Columna izquierda - Turno anterior -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-blue-50">
                <h2 class="text-xl font-bold text-blue-800">Turno Anterior</h2>
            </div>
            <div class="p-4 space-y-6">
                @if($previousData && $previousData->isNotEmpty())
                    @foreach($previousData as $plannedDate => $shifts)
                        @foreach($shifts as $shiftName => $records)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-base font-semibold text-gray-800">
                                    Detalles de Producción
                                </h4>
                                <div class="flex space-x-2 text-sm font-medium">
                                    <span class="bg-blue-600 text-white px-3 py-1 rounded-full uppercase">{{ $workCenter->name }}</span>
                                    <span class="bg-gray-500 text-white px-3 py-1 rounded-full">{{ $plannedDate }}</span>
                                    <span class="bg-gray-500 text-white px-3 py-1 rounded-full">{{ $shiftName }}</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-gray-700 border border-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Número de Parte</th>
                                            <th class="px-3 py-2 text-center">Planeado</th>
                                            <th class="px-3 py-2 text-center">Producido</th>
                                            <th class="px-3 py-2 text-center">Diferencia</th>
                                            <th class="px-3 py-2 text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($records as $record)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2">{{ $record['part_number'] }}</td>
                                            <td class="px-3 py-2 text-center">{{ $record['planned_quantity'] }}</td>
                                            <td class="px-3 py-2 text-center">{{ $record['produced_quantity'] }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @php $diff = $record['difference']; @endphp
                                                <span class="px-2 py-1 rounded-full font-semibold text-xs
                                                    {{ $diff < 0 ? 'text-red-700 bg-red-100' : ($diff > 0 ? 'text-green-700 bg-green-100' : 'text-gray-700 bg-gray-100') }}">
                                                    {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 rounded-full font-semibold text-xs {{ $record['status_class'] }}">
                                                    {{ $record['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-blue-50">
                <h2 class="text-xl font-bold text-blue-800">Turno Actual</h2>
            </div>
            <div class="p-4 space-y-6">
                @if($currentData && $currentData->isNotEmpty())
                    @foreach($currentData as $plannedDate => $shifts)
                        @foreach($shifts as $shiftName => $records)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-base font-semibold text-gray-800">
                                    Detalles de Producción
                                </h4>
                                <div class="flex space-x-2 text-sm font-medium">
                                    <span class="bg-blue-600 text-white px-3 py-1 rounded-full uppercase">{{ $workCenter->name }}</span>
                                    <span class="bg-gray-500 text-white px-3 py-1 rounded-full">{{ $plannedDate }}</span>
                                    <span class="bg-gray-500 text-white px-3 py-1 rounded-full">{{ $shiftName }}</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-gray-700 border border-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Número de Parte</th>
                                            <th class="px-3 py-2 text-center">Planeado</th>
                                            <th class="px-3 py-2 text-center">Producido</th>
                                            <th class="px-3 py-2 text-center">Diferencia</th>
                                            <th class="px-3 py-2 text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($records as $record)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2">{{ $record['part_number'] }}</td>
                                            <td class="px-3 py-2 text-center">{{ $record['planned_quantity'] }}</td>
                                            <td class="px-3 py-2 text-center">{{ $record['produced_quantity'] }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @php $diff = $record['difference']; @endphp
                                                <span class="px-2 py-1 rounded-full font-semibold text-xs
                                                    {{ $diff < 0 ? 'text-red-700 bg-red-100' : ($diff > 0 ? 'text-green-700 bg-green-100' : 'text-gray-700 bg-gray-100') }}">
                                                    {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 rounded-full font-semibold text-xs {{ $record['status_class'] }}">
                                                    {{ $record['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
    Alpine.data("table", () => ({
        init() {
            setInterval(() => $wire.dispatchSelf("refresh"), 1000); // 30 segundos
        }
    }));
</script>
@endscript
