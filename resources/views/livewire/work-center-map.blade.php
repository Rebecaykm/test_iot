<div>
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <h2 class="h4">Mapa de Estaciones de Trabajo</h2>

                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <select wire:model="selectedLine" class="form-select">
                            <option value="">Todas las líneas</option>
                            @foreach($lines as $line)
                                <option value="{{ $line->id }}">{{ $line->name }}</option>
                            @endforeach
                        </select>

                        <div class="btn-group" role="group">
                            <button wire:click="zoomIn" class="btn btn-primary">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button wire:click="zoomOut" class="btn btn-primary">
                                <i class="fas fa-search-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Canvas -->
                <div id="plant-container" class="position-relative border bg-light rounded overflow-hidden"
                     style="width: 100%; height: 70vh; min-height: 500px;">
                    <!-- Grid Background -->
                    <div class="position-absolute w-100 h-100" style="
                        background-image: linear-gradient(to right, #dee2e6 1px, transparent 1px),
                                          linear-gradient(to bottom, #dee2e6 1px, transparent 1px);
                        background-size: 40px 40px;
                        opacity: 0.3;"></div>

                    <!-- Scalable Content -->
                    <div class="position-absolute"
                         style="transform: scale({{ $zoomLevel }}); transform-origin: 0 0;
                                width: calc(100% / {{ $zoomLevel }}); height: calc(100% / {{ $zoomLevel }});">
                        @foreach($this->workCenters as $index => $workCenter)
                            @php
                                $defaultX = $workCenter->position_x ?? ($index % 10) * 140;
                                $defaultY = $workCenter->position_y ?? floor($index / 10) * 120;
                                $width = $workCenter->width ?? 120;
                                $height = $workCenter->height ?? 80;
                                $lineColor = $workCenter->line->color ?? '#6c757d'; // gris
                            @endphp

                            <div class="position-absolute border border-2 rounded text-center text-dark shadow-sm work-center-item"
                                 style="
                                     left: {{ $defaultX }}px;
                                     top: {{ $defaultY }}px;
                                     width: {{ $width }}px;
                                     height: {{ $height }}px;
                                     border-color: {{ $lineColor }};
                                     background-color: #fff;
                                     cursor: move;
                                     z-index: 1;
                                 "
                                 data-id="{{ $workCenter->id }}"
                                 wire:key="workcenter-{{ $workCenter->id }}"
                                 ondblclick="// TODO: redirigir a vista de detalle de estación">

                                 <div class="fw-bold small">{{ $workCenter->name }}</div>
                                 <div class="text-muted small">{{ $workCenter->number }}</div>
                                 <div class="badge text-white mt-1" style="background-color: {{ $lineColor }}">
                                     {{ $workCenter->line->name ?? 'Sin línea' }}
                                 </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .work-center-item {
                user-select: none;
                transition: transform 0.1s ease, box-shadow 0.2s ease;
            }

            .work-center-item:hover {
                transform: scale(1.02);
                z-index: 10;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            }

            .work-center-item.dragging {
                opacity: 0.9;
                z-index: 100;
                border-style: dashed;
                transform: scale(1.05) !important;
            }

            #plant-container {
                overflow: hidden;
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
                        listeners: {
                            start (event) {
                                const target = event.target;
                                if (!target.getAttribute('data-x')) {
                                    target.setAttribute('data-x', parseFloat(target.style.left) || 0);
                                    target.setAttribute('data-y', parseFloat(target.style.top) || 0);
                                }
                                target.classList.add('dragging');
                            },
                            move (event) {
                                const target = event.target;
                                const zoom = {{ $zoomLevel }};
                                let x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx / zoom;
                                let y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy / zoom;

                                const container = document.getElementById('plant-container');
                                const maxX = (container.offsetWidth / zoom) - target.offsetWidth;
                                const maxY = (container.offsetHeight / zoom) - target.offsetHeight;

                                x = Math.max(0, Math.min(x, maxX));
                                y = Math.max(0, Math.min(y, maxY));

                                target.style.left = `${x}px`;
                                target.style.top = `${y}px`;
                                target.setAttribute('data-x', x);
                                target.setAttribute('data-y', y);
                            },
                            end (event) {
                                const target = event.target;
                                target.classList.remove('dragging');
                                Livewire.dispatch('workCenterMoved', {
                                    workCenterId: target.getAttribute('data-id'),
                                    x: parseFloat(target.getAttribute('data-x')),
                                    y: parseFloat(target.getAttribute('data-y')),
                                });
                            }
                        }
                    });
                }

                initDrag();

                Livewire.hook('morph.updated', () => initDrag());

                // Zoom con scroll del mouse
                const plant = document.getElementById('plant-container');
                plant.addEventListener('wheel', e => {
                    e.preventDefault();
                    if (e.deltaY < 0) {
                        Livewire.dispatch('zoomIn');
                    } else {
                        Livewire.dispatch('zoomOut');
                    }
                }, { passive: false });
            });
        </script>
    @endpush
</div>
