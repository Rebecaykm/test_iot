<div>
    <div class="w-full h-full">

        <div class="flex justify-between items-center gap-3">
            <!-- Fecha en la esquina izquierda -->
            <div class="inline-flex items-center gap-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-500 dark:border-blue-600 py-2 px-3 rounded-lg shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span id="date" class="font-semibold tracking-wide"></span>
            </div>

            <!-- Hora en la esquina derecha -->
            <div class="inline-flex items-center gap-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-500 dark:border-blue-600 py-2 px-3 rounded-lg shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span id="clock" class="font-semibold tracking-wide"></span>
            </div>
        </div>

    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const meses = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];

        // Fecha: "18 Jun 2026" (día · mes abreviado · año)
        const day = String(now.getDate()).padStart(2, "0");
        const month = meses[now.getMonth()];
        const year = now.getFullYear();
        const dateString = `${day} ${month} ${year}`;

        // Hora: "HH:MM:SS"
        const hours = String(now.getHours()).padStart(2, "0");
        const minutes = String(now.getMinutes()).padStart(2, "0");
        const seconds = String(now.getSeconds()).padStart(2, "0");
        const timeString = `${hours}:${minutes}:${seconds}`;

        // Actualizar elementos
        document.getElementById("date").innerText = dateString;
        document.getElementById("clock").innerText = timeString;
    }

    setInterval(updateClock, 1000);

    // Inicializar inmediatamente
    updateClock();
</script>
