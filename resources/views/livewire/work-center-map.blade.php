<div>
    <div class="container-fluid py-4">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header bg-white border-bottom-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h2 class="h5 mb-0 text-uppercase">
                        Estaciones de Trabajo
                    </h2>

                    <div class="d-flex flex-column flex-sm-row gap-2 controls-container">
                        <select wire:model.live="selectedLine" class="form-select shadow-sm">
                            <option value="">Todas las líneas</option>
                            @foreach ($lines as $line)
                                <option value="{{ $line->id }}">{{ $line->name }}</option>
                            @endforeach
                        </select>

                        <div class="btn-group shadow-xs ml-2" role="group">
                            <button wire:click="zoomIn" class="btn btn-primary px-3">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button wire:click="zoomOut" class="btn btn-primary px-3">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button wire:click="resetView" class="btn btn-secondary px-3">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <!-- Canvas -->
                <div id="plant-container" class="position-relative border-top bg-light overflow-auto"
                    style="width: 100%; height: 85vh; min-height: 700px;">

                    <!-- Grid Background con patrón mejorado -->
                    <div class="position-absolute" id="grid-background"
                        style="
                        background-image:
                            linear-gradient(to right, rgba(222, 226, 230, 0.6) 1px, transparent 1px),
                            linear-gradient(to bottom, rgba(222, 226, 230, 0.6) 1px, transparent 1px),
                            linear-gradient(to right, rgba(222, 226, 230, 0.3) 1px, transparent 1px),
                            linear-gradient(to bottom, rgba(222, 226, 230, 0.3) 1px, transparent 1px);
                        background-size: 40px 40px, 40px 40px, 8px 8px, 8px 8px;
                        width: 3000px;
                        height: 2000px;">
                    </div>

                    <!-- Scalable Content -->
                    <div class="position-absolute" id="scalable-content"
                        style="transform: scale({{ $zoomLevel }}); transform-origin: 0 0;
                                width: 3000px; height: 2000px;">
                        @foreach ($this->workCenters as $index => $workCenter)
                            @php
                                $defaultX = $workCenter->position_x ?? ($index % 10) * 140;
                                $defaultY = $workCenter->position_y ?? floor($index / 10) * 120;
                                $width = $workCenter->width ?? 120;
                                $height = $workCenter->height ?? 80;
                                $lineColor = $workCenter->line->color ?? '#6c757d'; // gris
                            @endphp

                            <div class="position-absolute rounded-lg shadow-sm work-center-item"
                                style="
                                    left: {{ $defaultX }}px;
                                    top: {{ $defaultY }}px;
                                    width: {{ $width }}px;
                                    height: {{ $height }}px;
                                    border-left: 4px solid {{ $lineColor }};
                                    background-color: #fff;
                                    cursor: move;
                                    z-index: 1;
                                "
                                data-id="{{ $workCenter->id }}" wire:key="workcenter-{{ $workCenter->id }}"
                                ondblclick="window.location.href = ''">

                                <div class="p-2 h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="fw-bold">{{ $workCenter->name }}</div>
                                        <div class="text-muted small">{{ $workCenter->number }}</div>
                                    </div>
                                    <div class="badge text-white" style="background-color: {{ $lineColor }}">
                                        {{ $workCenter->line->name ?? 'Sin línea' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Indicador de escala con aspecto mejorado -->
                    <div class="scale-indicator shadow-sm">
                        <i class="fas fa-search me-1"></i> {{ number_format($zoomLevel * 100, 0) }}%
                    </div>

                    <!-- Leyenda de líneas -->
                    <div class="map-legend shadow-sm">
                        <div class="small fw-bold mb-1">Líneas</div>
                        @foreach ($lines as $line)
                            <div class="d-flex align-items-center mb-1">
                                <div class="color-dot me-1 mr-2" style="background-color: {{ $line->color ?? '#6c757d' }}"></div>
                                <span class="small">{{ $line->name }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Mensaje de ayuda -->
                    <div class="help-tip shadow-sm">
                        <i class="fas fa-mouse me-1"></i> Arrastra para mover | <i class="fas fa-scroll me-1"></i> Rueda para zoom
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* Estilos para las estaciones de trabajo */
            .work-center-item {
                user-select: none;
                transition: all 0.2s ease;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            }

            .work-center-item:hover {
                transform: scale(1.03);
                z-index: 10;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }

            .work-center-item.dragging {
                opacity: 0.9;
                z-index: 100;
                transform: scale(1.05) !important;
                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
                border-style: dashed;
            }

            /* Estilos para el contenedor */
            #plant-container {
                cursor: grab;
                touch-action: none;
                background-color: #f8f9fa;
                background-image: radial-gradient(circle, #ffffff 1px, transparent 1px);
                background-size: 20px 20px;
            }

            #plant-container:active {
                cursor: grabbing;
            }

            /* Indicadores y leyendas */
            .scale-indicator, .map-legend, .help-tip {
                position: absolute;
                background: rgba(255, 255, 255, 0.9);
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 0.8rem;
                z-index: 10;
            }

            .scale-indicator {
                bottom: 15px;
                right: 15px;
                transition: all 0.2s ease;
            }

            .map-legend {
                top: 15px;
                right: 15px;
                max-width: 200px;
            }

            .help-tip {
                bottom: 15px;
                left: 15px;
                color: #6c757d;
            }

            .color-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                display: inline-block;
            }

            /* Mejoras generales */
            .card {
                border-radius: 0.5rem;
                overflow: hidden;
            }

            .controls-container {
                background-color: rgba(255, 255, 255, 0.95);
                border-radius: 0.5rem;
                padding: 0.75rem;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            }

            .btn-group .btn {
                border-width: 1px;
            }

            /* Animaciones suaves */
            .fade-in {
                animation: fadeIn 0.3s ease-in;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.11/dist/interact.min.js"></script>
        <script>
            document.addEventListener('livewire:init', function() {
                const container = document.getElementById('plant-container');
                const scalableContent = document.getElementById('scalable-content');
                let isPanning = false;
                let isDraggingWorkCenter = false;
                let startX, startY, scrollLeft, scrollTop;

                // Inicializar arrastre de estaciones
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
                            start(event) {
                                const target = event.target;
                                if (!target.getAttribute('data-x')) {
                                    target.setAttribute('data-x', parseFloat(target.style.left) || 0);
                                    target.setAttribute('data-y', parseFloat(target.style.top) || 0);
                                }
                                target.classList.add('dragging');
                                isDraggingWorkCenter = true; // Marcar que estamos arrastrando una estación
                            },
                            move(event) {
                                const target = event.target;
                                const zoom = {{ $zoomLevel }};
                                let x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx / zoom;
                                let y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy / zoom;

                                const maxX = 3000 - target.offsetWidth;
                                const maxY = 2000 - target.offsetHeight;

                                x = Math.max(0, Math.min(x, maxX));
                                y = Math.max(0, Math.min(y, maxY));

                                target.style.left = `${x}px`;
                                target.style.top = `${y}px`;
                                target.setAttribute('data-x', x);
                                target.setAttribute('data-y', y);
                            },
                            end(event) {
                                const target = event.target;
                                target.classList.remove('dragging');
                                isDraggingWorkCenter = false; // Ya no estamos arrastrando

                                // Mostrar indicador de guardado
                                showSaveIndicator();

                                // Enviar posición actualizada a Livewire
                                @this.call('updateWorkCenterPosition',
                                    target.getAttribute('data-id'),
                                    parseFloat(target.getAttribute('data-x')),
                                    parseFloat(target.getAttribute('data-y'))
                                );
                            }
                        }
                    });
                }

                // Función para mostrar un indicador temporal de guardado
                function showSaveIndicator() {
                    // Crear un elemento para mostrar que se ha guardado
                    let saveIndicator = document.createElement('div');
                    saveIndicator.innerHTML = '<i class="fas fa-check-circle me-1"></i>Posición guardada';
                    saveIndicator.classList.add('save-indicator', 'fade-in');
                    saveIndicator.style.cssText = `
                        position: fixed;
                        bottom: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background-color: rgba(25, 135, 84, 0.9);
                        color: white;
                        padding: 8px 15px;
                        border-radius: 4px;
                        font-size: 14px;
                        z-index: 1000;
                        box-shadow: 0 3px 6px rgba(0,0,0,0.16);
                    `;

                    document.body.appendChild(saveIndicator);

                    // Eliminar después de 2 segundos
                    setTimeout(() => {
                        saveIndicator.style.opacity = '0';
                        saveIndicator.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => {
                            document.body.removeChild(saveIndicator);
                        }, 500);
                    }, 2000);
                }

                // Inicializar arrastre
                initDrag();

                // Añadir funcionalidad de desplazamiento (panning)
                container.addEventListener('mousedown', (e) => {
                    // Solo activar panning si no se está interactuando con una estación
                    if (e.target.closest('.work-center-item')) {
                        return; // Si estamos sobre una estación, no iniciamos panning
                    }

                    isPanning = true;
                    container.style.cursor = 'grabbing';
                    startX = e.pageX - container.offsetLeft;
                    startY = e.pageY - container.offsetTop;
                    scrollLeft = container.scrollLeft;
                    scrollTop = container.scrollTop;
                });

                container.addEventListener('mouseleave', () => {
                    if (!isDraggingWorkCenter) {
                        isPanning = false;
                        container.style.cursor = 'grab';
                    }
                });

                container.addEventListener('mouseup', () => {
                    if (!isDraggingWorkCenter) {
                        isPanning = false;
                        container.style.cursor = 'grab';
                    }
                });

                container.addEventListener('mousemove', (e) => {
                    if (!isPanning || isDraggingWorkCenter) return;
                    e.preventDefault();
                    const x = e.pageX - container.offsetLeft;
                    const y = e.pageY - container.offsetTop;
                    const walkX = (x - startX) * 1.5; // Multiplicador para ajustar velocidad
                    const walkY = (y - startY) * 1.5;
                    container.scrollLeft = scrollLeft - walkX;
                    container.scrollTop = scrollTop - walkY;
                });

                // Mejorar el zoom con rueda del ratón
                container.addEventListener('wheel', e => {
                    e.preventDefault();

                    // Obtener posición del cursor
                    const rect = container.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    // Scroll a la posición antes del zoom
                    const beforeZoomX = container.scrollLeft + x;
                    const beforeZoomY = container.scrollTop + y;

                    // Aplicar zoom
                    if (e.deltaY < 0) {
                        @this.call('zoomIn');
                    } else {
                        @this.call('zoomOut');
                    }

                    // Timeout pequeño para permitir que se actualice el DOM
                    setTimeout(() => {
                        // Calcular nueva posición de scroll
                        const zoomLevel = {{ $zoomLevel }}; // Obtener nivel de zoom actualizado
                        const afterZoomX = beforeZoomX * (e.deltaY < 0 ? 1.1 : 0.9);
                        const afterZoomY = beforeZoomY * (e.deltaY < 0 ? 1.1 : 0.9);

                        // Ajustar scroll para mantener posición del cursor
                        container.scrollLeft = afterZoomX - x;
                        container.scrollTop = afterZoomY - y;
                    }, 50);
                }, {
                    passive: false
                });

                // Reiniciar la vista
                Livewire.on('resetViewPosition', () => {
                    container.scrollLeft = 0;
                    container.scrollTop = 0;
                });

                // Re-inicializar arrastrar después de cada actualización
                Livewire.hook('morph.updated', () => {
                    initDrag();
                });
            });
        </script>
    @endpush
</div>
