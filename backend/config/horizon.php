<?php

use Illuminate\Support\Str;

return [

    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use_with_basic_auth' => false,

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => env('APP_ENV') === 'local' ? [] : ['web'],
    'waits' => [
        'redis:notifications' => 60,
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'metrics' => [
        'trim_days' => 7,
    ],

    'environments' => [
        'production' => [
            // Supervisor dedicado de alta prioridad: solo procesa notificaciones push
            'supervisor-notifications' => [
                'connection'   => 'redis',
                'queue'        => ['notifications'],
                'balance'      => 'auto',
                'max_processes' => 5,
                'min_processes' => 2,
                'tries'        => 3,
                'timeout'      => 60,
                'nice'         => 0,
            ],
            // Supervisor general para el resto de colas
            'supervisor-general' => [
                'connection'   => 'redis',
                'queue'        => ['realtime-events', 'emails', 'default', 'reports', 'analytics'],
                'balance'      => 'auto',
                'max_processes' => 8,
                'min_processes' => 1,
                'tries'        => 3,
                'timeout'      => 90,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection'   => 'redis',
                // Nivel estricto de izquierda a derecha: Primero vacía 'notifications', luego las demás
                'queue'        => ['notifications', 'realtime-events', 'emails', 'default', 'reports', 'analytics'],
                'balance'      => false,
                'max_processes' => 3,
                'tries'        => 1,
            ],
        ],
    ],
];
