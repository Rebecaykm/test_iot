@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 dark:border-indigo-600 text-sm font-medium leading-5 text-zinc-900 dark:text-ta-gray-100 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-zinc-500 dark:text-ta-gray-400 hover:text-zinc-700 dark:hover:text-ta-gray-300 hover:border-zinc-300 dark:hover:border-ta-gray-800 focus:outline-none focus:text-zinc-700 dark:focus:text-ta-gray-300 focus:border-zinc-300 dark:focus:border-ta-gray-800 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
