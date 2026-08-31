<x-guest-layout>
    <h1>Hourly Shift Production Tracking</h1>
    <p>Total Labels: {{ $totalLabels }}</p>
    <p>Completed Labels: {{ $completedLabels }}</p>
    <p>Completed Percentage: {{ $completedPercentage }}%</p>
    
    <div
        x-data="productionGrid(@js($result))"
    >
        <div class="mb-4 flex items-center justify-center gap-2">
            <div
                x-ref="grid"
                class="ag-theme-quartz h-[600px] w-full"
            ></div>
        </div>
    </div>
</x-guest-layout>