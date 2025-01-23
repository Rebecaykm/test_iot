<x-app-layout>
    <div class="container mx-auto p-4">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h1 class="text-2xl font-semibold text-gray-800">Números de Parte</h1>
                    </div>

                    <div class="overflow-x-auto">
                        <!-- Tabla -->
                        <table class="min-w-full table-auto">
                            <thead class="bg-gray-100 text-gray-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-medium">Número</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium">Nombre</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium">Estación</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium">Fecha de Creación</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-700">
                                @foreach ($partNumbers as $partNumber)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-6 py-3">{{ $partNumber->number }}</td>
                                    <td class="px-6 py-3">{{ $partNumber->name }}</td>
                                    <td class="px-6 py-3">{{ $partNumber->workCenter->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-3">{{ $partNumber->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex space-x-2">
                                            <a href="#" class="text-blue-500 hover:text-blue-700 flex items-center text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4 mr-1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Editar
                                            </a>
                                            <a href="#" class="text-red-500 hover:text-red-700 flex items-center text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4 mr-1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Eliminar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t">
                        <!-- Paginación -->
                        {{ $partNumbers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
