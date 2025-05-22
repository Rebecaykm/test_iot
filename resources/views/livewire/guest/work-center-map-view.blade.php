<div class="w-full h-full bg-gray-100">
    <div class="flex flex-col w-full h-full">
        <!-- Contenedor con scroll -->
        <div id="map-container" class="flex-1 relative overflow-auto cursor-grab">
            <!-- Controles -->
            <div class="absolute top-4 right-4 z-20 bg-white rounded shadow p-4 flex flex-col sm:flex-row gap-3">
                <!-- Controles (select y botones) -->
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

            <!-- Fondo de grid -->
            <div class="absolute w-[3000px] h-[2000px]"
                 style="background-image:
                    linear-gradient(to right, rgba(222, 226, 230, 0.6) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(222, 226, 230, 0.6) 1px, transparent 1px);
                    background-size: 40px 40px;">
            </div>

            <!-- Contenido escalable -->
            <div id="map-canvas" class="absolute w-[3000px] h-[2000px] origin-top-left"
                 style="transform: scale({{ $zoomLevel }});">
                @foreach ($this->workCenters as $workCenter)
                    @php
                        $x = $workCenter->position_x ?? 100 + ($loop->index % 10) * 140;
                        $y = $workCenter->position_y ?? 100 + floor($loop->index / 10) * 120;
                        $color = $workCenter->line->color ?? '#6c757d';
                    @endphp

                    <div class="absolute bg-white rounded shadow border-l-4 p-2 text-xs"
                         style="left: {{ $x }}px; top: {{ $y }}px; width: 120px; height: 80px; border-left-color: {{ $color }};"
                         wire:key="workcenter-{{ $workCenter->id }}"
                         ondblclick="window.location.href='{{ route('guest.production-records', $workCenter->id) }}'">

                        <div class="font-bold truncate my-1">{{ $workCenter->name }}</div>
                        <div class="text-gray-500 my-1">{{ $workCenter->number }}</div>
                        <div class="mt-auto">
                            <span class="text-white text-xs px-2 py-1 rounded-full" style="background-color: {{ $color }}">
                                {{ $workCenter->line->name ?? 'Sin línea' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Indicador de Zoom -->
            <div class="absolute bottom-4 right-4 bg-white px-3 py-1 rounded shadow text-sm z-20">
                Zoom: {{ round($zoomLevel * 100) }}%
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const container = document.getElementById('map-container');
                let isPanning = false;
                let startX, startY, scrollLeft, scrollTop;

                function isMapViewActive() {
                    const mapView = document.getElementById('map-view');
                    return mapView && !mapView.classList.contains('hidden');
                }

                // Zoom con scroll - SOLO cuando el mapa esté activo
                container.addEventListener('wheel', (e) => {
                    if (!isMapViewActive()) return; // No interferir si el mapa no está activo

                    e.preventDefault();
                    if (e.deltaY < 0) {
                        @this.zoomIn();
                    } else {
                        @this.zoomOut();
                    }
                }, { passive: false });

                // Panning (arrastrar) - SOLO cuando el mapa esté activo
                container.addEventListener('mousedown', (e) => {
                    if (!isMapViewActive()) return; // No interferir si el mapa no está activo
                    if (e.target.closest('select') || e.target.closest('button')) return;

                    isPanning = true;
                    startX = e.pageX;
                    startY = e.pageY;
                    scrollLeft = container.scrollLeft;
                    scrollTop = container.scrollTop;
                    container.style.cursor = 'grabbing';
                });

                container.addEventListener('mouseup', () => {
                    if (!isMapViewActive()) return;
                    isPanning = false;
                    container.style.cursor = 'grab';
                });

                container.addEventListener('mouseleave', () => {
                    if (!isMapViewActive()) return;
                    isPanning = false;
                    container.style.cursor = 'grab';
                });

                container.addEventListener('mousemove', (e) => {
                    if (!isMapViewActive() || !isPanning) return;
                    const x = e.pageX - startX;
                    const y = e.pageY - startY;
                    container.scrollLeft = scrollLeft - x;
                    container.scrollTop = scrollTop - y;
                });

                Livewire.on('resetViewPosition', () => {
                    if (!isMapViewActive()) return;
                    container.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
                });
            });
        </script>
    @endpush

</div>
