<div>
    <div class="w-full h-full">

        <div class="flex justify-between items-center uppercase">
            <!-- Fecha en la esquina izquierda -->
            <h4 class="text-lg font-semibold">
                <span id="date" class="bg-gray-600 text-white py-2 px-3 rounded-full">
                </span>
            </h4>

            <!-- Hora en la esquina derecha -->
            <h4 class="text-lg font-semibold">
                <span id="clock" class="bg-gray-600 text-white py-2 px-3 rounded-full">
                </span>
            </h4>
        </div>


    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();

        // Fecha
        const day = String(now.getDate()).padStart(2, "0");
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const year = now.getFullYear();
        const dateString = `${day}/${month}/${year}`;

        // Hora
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
