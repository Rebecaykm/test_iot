<x-guest-layout>
    <div class="h-screen flex flex-col gap-3 p-3 sm:p-4 bg-gray-100 dark:bg-gray-900">
        <div class="flex items-center justify-between flex-shrink-0">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white flex items-center">
                <span class="w-2.5 h-2.5 bg-blue-500 rounded-full inline-block mr-2.5"></span>
                Avance de Producción · {{ $workCenter }}
            </h1>

            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                Inicio
            </a>
        </div>

        <livewire:guest.production-progress :work-center="$workCenter" />
    </div>
</x-guest-layout>
