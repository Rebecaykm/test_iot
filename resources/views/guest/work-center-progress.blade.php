<x-guest-layout :floating-theme-toggle="false">
    <div class="h-screen flex flex-col bg-zinc-100 dark:bg-zinc-950">
        <x-guest-header />

        <main class="flex-1 min-h-0 flex flex-col gap-3 p-3 sm:p-4">
            <div class="flex items-center justify-between gap-4 flex-shrink-0">
                <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-50 flex items-center">
                    <span class="w-2.5 h-2.5 bg-[#2563eb] dark:bg-[#3b82f6] rounded-full inline-block mr-2.5"></span>
                    {{ $workCenter }}
                </h1>

                <x-clock />
            </div>

            <livewire:guest.work-center-progress :work-center="$workCenter" />
        </main>
    </div>
</x-guest-layout>
