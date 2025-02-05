<x-guest-layout>
    <div class="pt-4 bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="w-full max-w-7xl px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6">
                    <livewire:production-table :work-center="'voluptatibus'" />
                    <livewire:production-graph :work-center="'voluptatibus'" :real-time="true" />
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
