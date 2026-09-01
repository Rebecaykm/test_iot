<x-guest-layout>
    <main style="padding: 20px; font-size: 0.8rem !important">        
        <h1 style="color: #111827 !important;" class="font-bold text-lg">Hourly Shift Production Tracking</h1>
        <br>


        <div class="flex items-center gap-3" style="color: #111827 !important;">
            <div class="flex items-center gap-1">
                <div
                    class="h-3 w-5 rounded-sm"
                    style="background-color: #f2dede;"
                ></div>

                <span class="text-[10px]">
                    Producción Planeada {{ 100 - $completedPercentage }}%
                </span>
            </div>

            <div class="flex items-center gap-1">
                <div
                    class="h-3 w-5 rounded-sm"
                    style="background-color: #ffeb3b;"
                ></div>
                <span class="text-[10px]">
                    Producción Completada {{ $completedPercentage }}%
                </span>
            </div>
        </div>
        
        <div
            x-data="productionGrid(@js($result), '2026-08-31')"
        >
<div class="flex items-center gap-1 rounded-md border border-gray-200 bg-white px-1 py-0.5">

    <button
        type="button"
        @click="previousDay()"
        class="flex h-6 w-6 items-center justify-center rounded text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
        title="Día anterior"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-3.5 w-3.5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 19l-7-7 7-7"
            />
        </svg>
    </button>

    <span
        class="min-w-[80px] text-center text-xs font-medium text-gray-700"
        x-text="date"
    ></span>

    <button
        type="button"
        @click="nextDay()"
        class="flex h-6 w-6 items-center justify-center rounded text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
        title="Día siguiente"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-3.5 w-3.5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 5l7 7-7 7"
            />
        </svg>
    </button>

</div>

            <div class="mb-4 flex items-center justify-center gap-2 text-sm">
                <div
                    x-ref="grid"
                    class="ag-theme-quartz h-[600px] w-full"
                ></div>
            </div>
        </div>
    </main>
</x-guest-layout>