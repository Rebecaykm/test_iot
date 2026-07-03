<x-guest-layout>
    <div class="pt-4 pb-4 bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
            <div class="w-full max-w-[1920px] px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4">

                    @include('clock')

                    <livewire:press-production-timeline :work-center="$workCenter" />
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
