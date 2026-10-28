<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-zinc-800 dark:bg-ta-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-ta-gray-800 uppercase tracking-widest hover:bg-zinc-700 dark:hover:bg-white focus:bg-zinc-700 dark:focus:bg-white active:bg-zinc-900 dark:active:bg-ta-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-ta-gray-900 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
