@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-zinc-700 dark:text-ta-gray-300']) }}>
    {{ $value ?? $slot }}
</label>
