<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "reverb", "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over WebSockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Configuration
    |--------------------------------------------------------------------------
    */

    'reverb' => [

        /*
        |--------------------------------------------------------------------------
        | Reverb Server
        |--------------------------------------------------------------------------
        */

        'servers' => [
            'reverb' => [
                'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
                'port' => env('REVERB_SERVER_PORT', 8080),
                'hostname' => env('REVERB_HOST'),
                'options' => [
                    'tls' => [],
                ],
                'max_request_size' => 10_000,
                'scaling' => [
                    'enabled' => env('REVERB_SCALING_ENABLED', false),
                    'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                    'server' => [
                        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
                        'port' => env('REVERB_SERVER_PORT', 8080),
                        'hostname' => env('REVERB_HOST'),
                        'options' => [
                            'tls' => [],
                        ],
                    ],
                ],
                'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
                'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Reverb Applications
        |--------------------------------------------------------------------------
        */

        'apps' => [
            'provider' => 'config',

            'apps' => [
                [
                    'key' => env('REVERB_APP_KEY'),
                    'secret' => env('REVERB_APP_SECRET'),
                    'app_id' => env('REVERB_APP_ID'),
                    'options' => [
                        'host' => env('REVERB_HOST'),
                        'port' => env('REVERB_PORT', 8080),
                        'scheme' => env('REVERB_SCHEME', 'http'),
                        'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
                    ],
                    'allowed_origins' => ['*'],
                    'ping_interval' => env('REVERB_PING_INTERVAL', 10),
                    'activity_timeout' => env('REVERB_ACTIVITY_TIMEOUT', 30),
                    'max_message_size' => env('REVERB_MAX_MESSAGE_SIZE', 10_000),
                ],
            ],
        ],

    ],

];