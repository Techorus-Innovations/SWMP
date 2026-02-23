<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center gap-6 py-8 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-warning-100">
            <x-heroicon-o-clock class="h-8 w-8 text-warning-500" />
        </div>

        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ __('Request Pending Approval') }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Your registration request is currently under review. We will notify you once it has been approved.') }}
            </p>
        </div>
    </div>
</x-filament-panels::page>
