<?php

return [
    'api' => [
        /**
         * The base url of logbook app.
         */
        'url' => env('LOGBOOK_URL'),

        /**
         * The key of logbook client app.
         */
        'key' => env('LOGBOOK_KEY')
    ],
    /**
     * The minimum log level allowed to be stored.
     * DEBUG
     * INFO
     * NOTICE
     * WARNING
     * ERROR
     * CRITICAL
     * ALERT
     * EMERGENCY
     */
    'level' => env('LOGBOOK_LEVEL', 'DEBUG')
];
