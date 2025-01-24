<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <!-- Reemplazamos <x-welcome /> con el código de las cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4"> <!-- Usamos grid con 1 columna en pantallas pequeñas y 2 en medianas -->
                    @foreach ($groupedByWorkCenter as $workCenterName => $dates)
                        @foreach ($dates as $plannedDate => $shifts)
                            @foreach ($shifts as $shiftName => $records)
                                <div class="bg-white rounded-lg shadow-md mb-4"> <!-- Card con sombra y bordes redondeados -->
                                    <div class="bg-blue-500 text-white p-4 rounded-t-lg"> <!-- Fondo de la cabecera de la card -->
                                        <div class="flex justify-between items-center"> <!-- Usamos flexbox para alinear en una fila -->
                                            <!-- Badge del workCenterName -->
                                            <h4 class="text-lg font-semibold">
                                                <span class="bg-indigo-600 text-white px-3 py-1 rounded-full">{{ $workCenterName }}</span>
                                            </h4>

                                            <!-- Fechas y turnos -->
                                            <div class="flex space-x-4">
                                                <h4>
                                                    <span class="bg-gray-300 text-gray-800 px-3 py-1 rounded-full">{{ $plannedDate }}</span>
                                                </h4>
                                                <h4>
                                                    <span class="bg-gray-600 text-white px-3 py-1 rounded-full">{{ $shiftName }}</span>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4"> <!-- Cuerpo de la card con padding -->
                                        <table class="w-full table-auto border-collapse border border-gray-200 rounded-lg">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">PART NO.</th>
                                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">PLANNED QUANTITY</th>
                                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">PRODUCED QUANTITY</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($records as $record)
                                                    <tr class="odd:bg-gray-50 even:bg-gray-100">
                                                        <td class="px-4 py-2 text-sm text-gray-800">{{ $record['part_number'] }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-800">{{ $record['planned_quantity'] }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-800">{{ $record['produced_quantity'] }}</td>
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
    </div>
</x-app-layout>
