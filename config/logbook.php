<?php

return [
    'api' => [
        'url' => env('LOGBOOK_API_URL', ''),
        'key' => env('LOGBOOK_API_KEY', ''),
    ],

    /**
     * Instance ID is a unique identifier per instance of your apps.
     * Please use only alphabetic characters, dash, or underscore.
     */
    'instance_id' => env('LOGBOOK_INSTANCE_ID', 'default'),
];