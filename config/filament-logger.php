<?php

return [
    'datetime_format' => 'd/m/Y H:i:s',
    'date_format' => 'd/m/Y',

    // 'activity_resource' => \Jacobtims\FilamentLogger\Resources\ActivityResource::class,
    'activity_resource' => \App\Filament\Admin\Resources\CustomActivities\CustomActivityResource::class,
    'scoped_to_tenant' => true,
    'navigation_sort' => null,

    'resources' => [
        'enabled' => true,
        'log_name' => 'Resource',
        'logger' => \Jacobtims\FilamentLogger\Loggers\ResourceLogger::class,
        'color' => 'success',

        'exclude' => [
            App\Filament\Admin\Resources\CustomActivities\CustomActivityResource::class,
            Jacobtims\FilamentLogger\Resources\ActivityResource::class,
        ],
        'cluster' => null,
        'navigation_group' => 'Logs',
    ],

    'access' => [
        'enabled' => true,
        'logger' => \Jacobtims\FilamentLogger\Loggers\AccessLogger::class,
        'color' => 'danger',
        'log_name' => 'Access',
    ],

    'notifications' => [
        'enabled' => true,
        'logger' => \Jacobtims\FilamentLogger\Loggers\NotificationLogger::class,
        'color' => null,
        'log_name' => 'Notification',
    ],

    'models' => [
        'enabled' => true,
        'log_name' => 'Model',
        'color' => 'warning',
        'logger' => \Jacobtims\FilamentLogger\Loggers\ModelLogger::class,
        'register' => [
            // App\Models\User::class,
        ],
    ],

    'custom' => [
        // [
        //     'log_name' => 'Custom',
        //     'color' => 'primary',
        // ]
    ],
];
