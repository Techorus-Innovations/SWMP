<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule materials sync from SAP
Schedule::command('materials:sync')
    ->hourly()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Materials sync completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Materials sync failed');
    });

