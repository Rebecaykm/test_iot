<div
    x-data="{
        dark: localStorage.getItem('theme')
            ? localStorage.getItem('theme') === 'dark'
            : window.matchMedia('(prefers-color-scheme: dark)').matches,
        toggleTheme() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        }
    }"
    class="fixed bottom-4 right-4 z-50"
>
    <button
        @click="toggleTheme()"
        type="button"
        class="flex items-center justify-center h-11 w-11 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg hover:shadow-xl text-gray-600 dark:text-gray-300 transition-all"
        :aria-label="dark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
        :title="dark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
    >
        {{-- Sol: visible en modo oscuro, un clic pasa a tema claro --}}
        <svg x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m8.25-9H18.75M5.25 12H3.75m14.14-6.36-1.06 1.06M6.166 17.834l-1.06 1.06m12.728 0-1.06-1.06M6.166 6.166 5.106 5.106M12 15a3 3 0 100-6 3 3 0 000 6z" />
        </svg>
        {{-- Luna: visible en modo claro, un clic pasa a tema oscuro --}}
        <svg x-show="!dark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
    </button>
</div>
