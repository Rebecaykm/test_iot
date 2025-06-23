<div>
    <div class="container mx-auto px-4 py-8">
        <div x-data="chart" wire:ignore>
            @foreach($productionRecordData as $areaName => $lines)
            <div class="mb-12 p-6 bg-white rounded-xl border border-gray-200">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b-2 border-blue-200 pb-3">{{ $areaName }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($lines as $lineName => $workCenters)
                        @foreach($workCenters as $workCenterName => $data)
                        <a
                            href="{{ route('guest.production-records', $data['id']) }}"
                            class="block hover:no-underline transition-transform duration-200 hover:scale-[1.02]"
                        >
                            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow border-l-2 border-blue-500 cursor-pointer h-full">
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg mb-3 text-gray-700 flex items-center">
                                        <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                                        {{ $workCenterName }}
                                        <span class="ml-auto flex items-center space-x-1">
                                            @if($data['produced_percentage'] >= 100)
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @elseif($data['produced_percentage'] >= 90)
                                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            @endif
                                            <span class="text-sm font-semibold @if($data['produced_percentage'] >= 100) text-green-600
                                                @elseif($data['produced_percentage'] >= 90) text-yellow-600
                                                @else text-red-600 @endif">
                                                {{ $data['produced_percentage'] }}%
                                            </span>
                                        </span>
                                    </h3>
                                    <div class="chart-container" style="height: 150px;">
                                        <canvas
                                            x-init="createChart($el, '{{ $workCenterName }}', {{ $data['total_planned'] }}, {{ $data['total_produced'] }})"
                                            class="w-full h-full"
                                        ></canvas>
                                    </div>
                                    <div class="flex justify-between mt-4 text-sm font-medium">
                                        <span class="text-blue-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                                            </svg>
                                            Planeado: {{ $data['total_planned'] }}
                                        </span>
                                        <span class="text-green-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Producido: {{ $data['total_produced'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    @endforeach
                </div>
            </div>
            @endforeach

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            @script
            <script>
                Alpine.data('chart', () => {
                    return {
                        createChart(canvasElement, workCenterName, planned, produced) {
                            new Chart(canvasElement, {
                                type: 'bar',
                                data: {
                                    labels: ['Planeado', 'Producido'],
                                    datasets: [{
                                        label: 'Cantidad',
                                        data: [planned, produced],
                                        backgroundColor: [
                                            'rgba(37, 99, 235, 0.2)',
                                            'rgba(22, 163, 74, 0.2)'
                                        ],
                                        borderColor: [
                                            'rgb(29, 78, 216)',
                                            'rgb(21, 128, 61)'
                                        ],
                                        borderWidth: 2,
                                        borderRadius: 8,
                                        borderSkipped: false,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return `${context.label}: ${context.formattedValue}`;
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { drawBorder: false, color: 'rgba(0, 0, 0, 0.05)' },
                                            ticks: { font: { weight: 'bold' }, padding: 10 }
                                        },
                                        x: {
                                            grid: { display: false, drawBorder: false },
                                            ticks: { font: { weight: 'bold', size: 12 } }
                                        }
                                    },
                                    animation: {
                                        duration: 1000,
                                        easing: 'easeOutQuart'
                                    }
                                }
                            });
                        }
                    }
                });
            </script>
            @endscript
        </div>
    </div>
</div>
