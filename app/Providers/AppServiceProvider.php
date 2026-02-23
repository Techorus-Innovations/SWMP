<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy('Spatie\Activitylog\Models\Activity', ActivityPolicy::class);
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->circular(true)
                ->visible(outsidePanels: true)
                ->outsidePanelRoutes([
                    'auth.email-verification',
                    'auth.login',
                    'auth.register',
                    'filament.partner.pages.pending-approval',
                    'filament.partner.pages.rejected-request',
                ])
                ->locales(['ar','en']);
        });
    }
}
