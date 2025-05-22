<x-guest-layout>
    <div class="bg-gray-200 dark:bg-gray-900 min-h-screen">
        <div class="container mx-auto px-6 lg:px-8 py-6">
            <div class="flex flex-col w-full">
                <div class="flex justify-end">
                    @include('clock')
                </div>

                <div class="w-full mt-4">
                    <livewire:guest.production-record-view :work-center-id="$workCenterId" />
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
