<x-guest-layout>
    <style>
        
.normal .gantt_task_content,
.normal {
    background: #22c55e;
}

.overflow .gantt_task_content,
.overflow {
    background: #ef4444;
}
    </style>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-950 text-[11px] leading-tight antialiased">
        <div class="w-full px-2 py-2" x-data="productionDashboard()" x-init="init()">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-md p-6 space-y-6 transition-colors duration-200">
                <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="formattedDate" class="font-mono"></span>
                    <span x-text="time" class="font-mono"></span>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                        Gráfica de Producción
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Seguimiento de producción por fecha y turno
                    </p>
                </div>
                <div class="flex justify-between gap-6">
                    <div class="flex items-center justify-start gap-3">
                        <button type="button"
                            class="p-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg transition"
                            aria-label="Anterior" @click="subtractDay()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>

                        <div
                            class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2 font-mono">
                            <span class="font-medium" x-text="searchDate"></span>
                            <span class="text-gray-400 dark:text-gray-600">|</span>
                            <span>D</span>
                        </div>

                        <button type="button"
                            class="p-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg transition"
                            aria-label="Siguiente" @click="addDay()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        {{-- <div
                            class="max-w-6xl mx-auto mt-10 p-6 bg-gray-50 rounded-xl border border-gray-200 shadow-sm font-sans">
                            <!-- Título del contenedor de filtros -->
                            <h2 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filtros de Búsqueda
                            </h2>

                            <!-- Grid Contenedor de los 5 Filtros -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

                                <!-- Filtro 1: País -->
                                <div class="relative">
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">País</label>
                                    <div class="relative">
                                        <input type="text" placeholder="Buscar país..."
                                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-9 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filtro 2: Categoría -->
                                <div class="relative">
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Categoría</label>
                                    <div class="relative">
                                        <input type="text" placeholder="Buscar categoría..."
                                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-9 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filtro 3: Estado / Status -->
                                <div class="relative">
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Estado</label>
                                    <div class="relative">
                                        <input type="text" placeholder="Buscar estado..."
                                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-9 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filtro 4: Idioma -->
                                <div class="relative">
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Idioma</label>
                                    <div class="relative">
                                        <input type="text" placeholder="Buscar idioma..."
                                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-9 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filtro 5: Etiqueta / Tag -->
                                <div class="relative">
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Etiqueta</label>
                                    <div class="relative">
                                        <input type="text" placeholder="Buscar etiqueta..."
                                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-9 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>

            <div class="flex justify-center items-center mt-6 pb-3 border-b border-gray-200">
                <div class="flex gap-8 text-xs font-medium">
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full" style="background-color: #ffeb3b;"></span>
                        <span>Plan de Producción %</span>
                        <span class="w-12 text-right font-bolder text-lg">XX</span>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full" style="background-color: #f2dede;"></span>
                        <span>Producción Real %</span>
                        <span class="w-12 text-right font-bold text-lg">XX</span>
                    </div>
                </div>
            </div>
        </div>

       <div x-data="ganttComponent()" x-init="init()" style="width: 100%; display: flex; flex-direction: column;">
    
    <div id="gantt_here" style="width: 100%; height: auto; flex: 1; min-height: 600px;"></div>

</div>

    </div>

    <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
    <script src="https://export.dhtmlx.com/gantt/api.js"></script>
    <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css">
    <script>
        gantt.plugins({
            tooltip: true
        });

        function ganttComponent() {
            return {
                tasks: @json($data ?? []),

                init() {
                    console.log('Gantt tasks data:', this.tasks); // Debugging line to check the data
                    gantt.config.smart_rendering = false;
                    gantt.config.scroll_size = 15;
                    gantt.config.autoscale_height = true; // Permite que el gantt se expanda según el contenido
                    gantt.config.date_format = "%Y-%m-%d %H:%i";
                    gantt.config.duration_unit = "hour";

                    const today8 = new Date();
                    today8.setHours(7, 0, 0, 0);

                    const tomorrow8 = new Date(today8);
                    tomorrow8.setDate(tomorrow8.getDate() + 1);

                    gantt.config.start_date = today8;
                    gantt.config.end_date = tomorrow8;

                    gantt.config.min_column_width = 50;
                    gantt.config.scale_height = 54;

                    gantt.config.scales = [
                        { unit: "day", format: "%d/%m/%Y" },
                        { unit: "hour", step: 1, format: "%H:%i" }
                    ];

                   
gantt.config.columns = [
    { name: "text", label: "Producto", width: 120 },
    { name: "snp", label: "SNP", width: 80, align: "center" },
    { name: "plannedPieces", label: "Plan", width: 80, align: "right" },
    { name: "completedPieces", label: "Real", width: 80, align: "right" },
    { name: "difference", label: "Dif", width: 80, align: "right", 
template: function(task) {
        return task.difference > 0
            ? `<span style="color:red">+${task.difference}</span>`
            : task.difference;
    }
 }
];


                    gantt.config.readonly = true;

                    
gantt.templates.task_class = function(start, end, task) {
    return task.status;
};


                    gantt.init("gantt_here");
                    gantt.parse(this.tasks);
                }
            }
        }

        function productionDashboard() {
            return {
                time: '',
                date: '',
                searchDate: '',
                init() {
                    this.update();
                    setInterval(() => {
                        this.update();
                    }, 1000);
                    this.searchDate = new Date().toLocaleDateString('es-MX', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                },
                get formattedDate() {
                    return this.date;
                },
                addDay() {
                    const [day, month, year] = this.searchDate.split('/');
                    const currentDate = new Date(year, month - 1, day);
                    currentDate.setDate(currentDate.getDate() + 1);
                    this.searchDate = currentDate.toLocaleDateString('es-MX', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                },
                subtractDay() {
                    const [day, month, year] = this.searchDate.split('/');
                    const currentDate = new Date(year, month - 1, day);
                    currentDate.setDate(currentDate.getDate() - 1);
                    this.searchDate = currentDate.toLocaleDateString('es-MX', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                },
                update() {
                    const now = new Date();
                    this.time = now.toLocaleTimeString('es-MX', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    });
                    this.date = now.toLocaleDateString('es-MX', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                }
            }
        }

    </script>
</x-guest-layout>