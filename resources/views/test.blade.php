<x-guest-layout>
    <div class="pt-4 bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="py-12">

                <livewire:production-table :work-center="'totam'" :interval="'1 second'" :real-time="true" />

            </div>
        </div>
    </div>
</x-guest-layout>
