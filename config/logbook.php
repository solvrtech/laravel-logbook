<?php

return [
    'api'         => [
        'url' => env('LOGBOOK_API_URL', ''),
        'key' => env('LOGBOOK_API_KEY', ''),
    ],

    /**
     * Instance ID is a unique identifier per instance of your apps.
     * Please use only alphabetic characters, dash, or underscore.
     */
    'instance_id' => env('LOGBOOK_INSTANCE_ID', 'default'),

    /**
     * This configuration defines a logbook transport that can handle logs
     * either synchronously or asynchronously.
     *
     * Driver options: "sync", "redis", and "database"
     *
     */
    'transport'   => [
        'driver' => env('LOGBOOK_TRANSPORT', 'sync'),
    ],

    /**
     * Available driver options
     */
    'options'     => [
        'redis'    => [
            'host'     => env('REDIS_HOST', ''),
            'password' => env('REDIS_PASSWORD', ''),
            'port'     => env('REDIS_PORT', ''),
            'stream'   => env('logs'),
            'batch'    => env('LOGBOOK_BATCH', 15),
        ],
        'database' => [
            'batch' => env('LOGBOOK_BATCH', 15),
        ],
        'sync'     => [],
    ],
];