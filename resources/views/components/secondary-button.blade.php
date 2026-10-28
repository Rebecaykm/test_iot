<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white dark:bg-ta-gray-900 border border-zinc-300 dark:border-ta-gray-600 rounded-md font-semibold text-xs text-zinc-700 dark:text-ta-gray-300 uppercase tracking-widest shadow-sm hover:bg-zinc-50 dark:hover:bg-ta-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-ta-gray-900 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
