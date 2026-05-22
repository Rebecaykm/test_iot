<div>
    <div x-data="table" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Columna izquierda - Turno anterior -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-600 bg-blue-50 dark:bg-blue-900/50">
                @if($previousData && $previousData->isNotEmpty())
                    @foreach($previousData as $plannedDate => $shifts)
                        @foreach($shifts as $shiftName => $records)
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                <h2 class="text-xl font-bold text-blue-800 dark:text-blue-200">Turno Anterior</h2>
                                <div class="flex flex-wrap gap-2 text-sm font-medium">
                                    <span class="bg-blue-600 dark:bg-blue-500 text-white px-3 py-1 rounded-full uppercase">{{ $workCenter->name }}</span>
                                    <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">{{ $plannedDate }}</span>
                                    <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">{{ $shiftName }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @else
                    <h2 class="text-xl font-bold text-blue-800 dark:text-blue-200">Turno Anterior</h2>
                @endif
            </div>
            <div class="p-4">
                @if($previousData && $previousData->isNotEmpty())
                    @foreach($previousData as $plannedDate => $shifts)
                        @foreach($shifts as $shiftName => $records)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-200 dark:bg-gray-700 text-xs uppercase text-gray-700 dark:text-gray-300">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Número de Parte</th>
                                            <th class="px-4 py-3 text-center font-semibold">Planeado</th>
                                            <th class="px-4 py-3 text-center font-semibold">Producido</th>
                                            <th class="px-4 py-3 text-center font-semibold">Diferencia</th>
                                            <th class="px-4 py-3 text-center font-semibold">Estado</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                                        @foreach($records as $record)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-medium">{{ $record['part_number'] }}</td>
                                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record['planned_quantity'] }}</td>
                                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record['produced_quantity'] }}</td>
                                                <td class="px-4 py-3 text-center">
                                                    @php $diff = $record['difference']; @endphp
                                                    <span class="px-2 py-1 rounded-full font-semibold text-xs
                                                    {{ $diff < 0 ? 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/50' : ($diff > 0 ? 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/50' : 'text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-600') }}">
                                                    {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                                                </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
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
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p>No hay datos disponibles para el turno anterior</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Columna derecha - Turno actual -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-600 bg-blue-50 dark:bg-blue-900/50">
                @if($currentData && $currentData->isNotEmpty())
                    @foreach($currentData as $plannedDate => $shifts)
                        @foreach($shifts as $shiftName => $records)
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                <h2 class="text-xl font-bold text-blue-800 dark:text-blue-200">Turno Actual</h2>
                                <div class="flex flex-wrap gap-2 text-sm font-medium">
                                    <span class="bg-blue-600 dark:bg-blue-500 text-white px-3 py-1 rounded-full uppercase">{{ $workCenter->name }}</span>
                                    <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">{{ $plannedDate }}</span>
                                    <span class="bg-gray-600 dark:bg-gray-500 text-white px-3 py-1 rounded-full">{{ $shiftName }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @else
                    <h2 class="text-xl font-bold text-blue-800 dark:text-blue-200">Turno Actual</h2>
                @endif
            </div>
            <div class="p-4">
                @if($currentData && $currentData->isNotEmpty())
                    @foreach($currentData as $plannedDate => $shifts)
                        @foreach($shifts as $shiftName => $records)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-200 dark:bg-gray-700 text-xs uppercase text-gray-700 dark:text-gray-300">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Número de Parte</th>
                                            <th class="px-4 py-3 text-center font-semibold">Planeado</th>
                                            <th class="px-4 py-3 text-center font-semibold">Producido</th>
                                            <th class="px-4 py-3 text-center font-semibold">Diferencia</th>
                                            <th class="px-4 py-3 text-center font-semibold">Estado</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                                            @foreach($records as $record)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-medium">{{ $record['part_number'] }}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record['planned_quantity'] }}</td>
                                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record['produced_quantity'] }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        @php $diff = $record['difference']; @endphp
                                                        <span class="px-2 py-1 rounded-full font-semibold text-xs
                                                        {{ $diff < 0 ? 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/50' : ($diff > 0 ? 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/50' : 'text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-600') }}">
                                                        {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                                                    </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
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
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p>No hay datos disponibles para el turno actual</p>
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
            setInterval(() => $wire.dispatchSelf("refresh"), 10000);
        }
    }));
</script>
@endscript
