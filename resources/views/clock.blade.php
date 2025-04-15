<div class="flex justify-end items-center uppercase">
    <h4 class="text-lg font-semibold">
                            <span id="clock" class="bg-gray-600 text-white py-2 px-3 rounded-full">
                            </span>
    </h4>
</div>

<script>
    function updateClock() {
        const now = new Date();

        // const day = String(now.getDate()).padStart(2, "0");
        // const month = String(now.getMonth() + 1).padStart(2, "0");
        // const year = now.getFullYear();

        const hours = String(now.getHours()).padStart(2, "0");
        const minutes = String(now.getMinutes()).padStart(2, "0");
        const seconds = String(now.getSeconds()).padStart(2, "0");

        // const timeString = `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        const timeString = `${hours}:${minutes}:${seconds}`;

        document.getElementById("clock").innerText = timeString;
    }

    setInterval(updateClock, 1000);
</script>
