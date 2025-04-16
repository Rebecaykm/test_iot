<div>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-white px-6 py-4 border-b">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h2 class="text-xl font-bold text-blue-600 flex items-center">
                        <i class="fas fa-map-marked-alt mr-2"></i>
                        Mapa de Estaciones
                    </h2>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <select wire:model.live="selectedLine" class="form-select block w-full sm:w-48">
                            <option value="">Todas las líneas</option>
                            @foreach ($lines as $line)
                                <option value="{{ $line->id }}">{{ $line->name }}</option>
                            @endforeach
                        </select>

                        <div class="flex gap-1">
                            <button wire:click="zoomIn" class="px-3 py-2 bg-blue-500 text-white rounded">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button wire:click="zoomOut" class="px-3 py-2 bg-blue-500 text-white rounded">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button wire:click="resetView" class="px-3 py-2 bg-gray-500 text-white rounded">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mapa Container -->
            <div class="relative w-full h-[85vh] min-h-[500px] bg-gray-50 overflow-auto">
                <!-- Grid Background -->
                <div class="absolute w-[3000px] h-[2000px]"
                    style="background-image:
                        linear-gradient(to right, rgba(222, 226, 230, 0.6) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(222, 226, 230, 0.6) 1px, transparent 1px);
                    background-size: 40px 40px;">
                </div>

                <!-- Work Centers -->
                <div class="absolute w-[3000px] h-[2000px] origin-top-left"
                    style="transform: scale({{ $zoomLevel }});">

                    @foreach ($this->workCenters as $workCenter)
                        @php
                            $x = $workCenter->position_x ?? 100 + ($loop->index % 10) * 140;
                            $y = $workCenter->position_y ?? 100 + floor($loop->index / 10) * 120;
                            $color = $workCenter->line->color ?? '#6c757d';
                        @endphp

                        <div class="absolute bg-white rounded shadow cursor-pointer border-l-4 flex flex-col p-2"
                            style="left: {{ $x }}px; top: {{ $y }}px; width: 120px; height: 80px; border-left-color: {{ $color }};"
                            wire:key="workcenter-{{ $workCenter->id }}"
                            ondblclick="window.location.href='{{ route('work-centers.show', $workCenter->id) }}'">

                            <div class="font-bold truncate">{{ $workCenter->name }}</div>
                            <div class="text-gray-500 text-xs">{{ $workCenter->number }}</div>
                            <div class="mt-auto">
                                <span class="text-xs px-2 py-1 rounded-full text-white"
                                    style="background-color: {{ $color }}">
                                    {{ $workCenter->line->name ?? 'Sin línea' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Zoom Indicator -->
                <div class="absolute bottom-4 right-4 bg-white px-3 py-1 rounded shadow text-sm">
                    Zoom: {{ round($zoomLevel * 100) }}%
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const container = document.querySelector('[wire\\:id]');

                // Handle mouse wheel zoom
                container.addEventListener('wheel', (e) => {
                    e.preventDefault();
                    if (e.deltaY < 0) {
                        @this.zoomIn();
                    } else {
                        @this.zoomOut();
                    }
                }, {
                    passive: false
                });

                Livewire.on('resetViewPosition', () => {
                    container.scrollTo({
                        top: 0,
                        left: 0,
                        behavior: 'smooth'
                    });
                });
            });
        </script>
    @endpush
</div>
