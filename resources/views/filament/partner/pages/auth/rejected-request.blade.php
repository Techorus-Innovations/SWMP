<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center gap-6 py-8 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-danger-100">
            <x-heroicon-o-x-circle class="h-8 w-8 text-danger-500" />
        </div>

        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ __('Request Rejected') }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Your registration request has been rejected. For more information, please contact support.') }}
            </p>
        </div>
    </div>
</x-filament-panels::page>
