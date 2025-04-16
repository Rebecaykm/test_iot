<div>
    <div class="container mx-auto p-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-800">Mapa de Estaciones de Trabajo</h1>

                <div class="flex flex-col sm:flex-row gap-4">
                    <select wire:model="selectedLine"
                            class="border rounded px-4 py-2 pr-10 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas las líneas</option>
                        @foreach($lines as $line)
                            <option value="{{ $line->id }}">{{ $line->name }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button wire:click="zoomIn"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition">
                            <i class="fas fa-search-plus mr-1"></i> Zoom +
                        </button>
                        <button wire:click="zoomOut"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition">
                            <i class="fas fa-search-minus mr-1"></i> Zoom -
                        </button>
                    </div>
                </div>
            </div>

            <div class="relative border-2 border-gray-300 bg-gray-50 overflow-hidden rounded-lg"
                 style="width: 100%; height: 70vh; min-height: 500px;"
                 id="plant-container">
                <!-- Grid de fondo -->
                <div class="absolute inset-0 bg-grid" style="
                    background-image: linear-gradient(to right, #e5e7eb 1px, transparent 1px),
                                      linear-gradient(to bottom, #e5e7eb 1px, transparent 1px);
                    background-size: 40px 40px;
                    opacity: 0.3;
                "></div>

                <!-- Contenedor escalable -->
                <div class="absolute"
                     style="transform: scale({{ $zoomLevel }}); transform-origin: 0 0; width: calc(100% / {{ $zoomLevel }}); height: calc(100% / {{ $zoomLevel }});">
                    @foreach($this->workCenters as $workCenter)
                        <div
                            class="absolute bg-white border-2 rounded-lg p-2 shadow-md cursor-move work-center-item transition-all duration-100 hover:border-blue-600 hover:shadow-lg"
                            style="left: {{ $workCenter->position_x ?? ($loop->index * 130 % 1000) }}px;
                                   top: {{ $workCenter->position_y ?? (20 + floor($loop->index / 8) * 100) }}px;
                                   border-color: {{ $workCenter->line->color ?? '#3b82f6' }};
                                   transform: translate(0px, 0px);"
                            data-id="{{ $workCenter->id }}"
                            wire:key="workcenter-{{ $workCenter->id }}"
                        >
                            <div class="font-bold text-md text-center text-gray-800">{{ $workCenter->name }}</div>
                            <div class="text-xs text-bold text-gray-600 truncate">{{ $workCenter->number }}</div>
                            @if($workCenter->line)
                                <div class="text-xs mt-1 px-1 py-0.5 rounded text-white text-center"
                                     style="background-color: {{ $workCenter->line->color ?? '#6b7280' }}">
                                    {{ $workCenter->line->name }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .work-center-item {
                width: 120px;
                min-height: 60px;
                transition: transform 0.1s ease, box-shadow 0.2s ease;
                user-select: none;
                z-index: 1;
                touch-action: none;
                position: absolute;
            }

            .work-center-item:hover {
                z-index: 10;
                transform: scale(1.02);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .work-center-item.dragging {
                cursor: grabbing;
                z-index: 100;
                transform: scale(1.05) !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                opacity: 0.95;
                border-style: dashed;
            }

            #plant-container {
                touch-action: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.11/dist/interact.min.js"></script>
        <script>
            document.addEventListener('livewire:init', function () {
                function initDrag() {
                    interact('.work-center-item').draggable({
                        inertia: false,
                        modifiers: [
                            interact.modifiers.restrictRect({
                                restriction: 'parent',
                                endOnly: false
                            })
                        ],
                        autoScroll: true,
                        listeners: {
                            start: function (event) {
                                const target = event.target;
                                if (!target.getAttribute('data-x')) {
                                    const x = parseFloat(target.style.left) || 0;
                                    const y = parseFloat(target.style.top) || 0;
                                    target.setAttribute('data-x', x);
                                    target.setAttribute('data-y', y);
                                }
                                target.classList.add('dragging', 'z-50');
                            },
                            move: function (event) {
                                const target = event.target;
                                const container = document.getElementById('plant-container');
                                const zoomLevel = {{ $zoomLevel }};

                                let x = (parseFloat(target.getAttribute('data-x')) || 0) + (event.dx / zoomLevel);
                                let y = (parseFloat(target.getAttribute('data-y')) || 0) + (event.dy / zoomLevel);

                                // Ajustar para el zoom
                                const maxX = (container.offsetWidth / zoomLevel) - target.offsetWidth;
                                const maxY = (container.offsetHeight / zoomLevel) - target.offsetHeight;

                                x = Math.max(0, Math.min(x, maxX));
                                y = Math.max(0, Math.min(y, maxY));

                                target.style.left = x + 'px';
                                target.style.top = y + 'px';

                                target.setAttribute('data-x', x);
                                target.setAttribute('data-y', y);
                            },
                            end: function (event) {
                                const target = event.target;
                                target.classList.remove('dragging', 'z-50');

                                const id = target.getAttribute('data-id');
                                const x = parseFloat(target.getAttribute('data-x')) || 0;
                                const y = parseFloat(target.getAttribute('data-y')) || 0;

                                Livewire.dispatch('workCenterMoved', {
                                    workCenterId: id,
                                    x: x,
                                    y: y
                                });
                            }
                        }
                    });
                }

                initDrag();

                Livewire.hook('morph.updated', () => {
                    initDrag();
                });
            });
        </script>
    @endpush
</div>
