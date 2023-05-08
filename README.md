# laravel-logbook

Installitation

```bash
composer require solvrtech/laravel-logbook
```

Configuration<br>
You should publish the laravel-logbook config file with:

```bash
php artisan vendor:publish --tag=logbook
```

```bash
// config/logging.php

// ...
'channels' => [
    // ...
    
    'logbook' => [
        'driver' => 'logbook',
        'level' => env('LOG_LEVEL', 'debug')
    ],
]
```

```bash
// .env

LOG_CHANNEL=logbook
LOG_LEVEL=debug
LOGBOOK_API_URL="https://logbook.com"
LOGBOOK_API_KEY="4eaa39a6ff57c4..."

// Instance ID is a unique identifier per instance of your apps
LOGBOOK_INSTANCE_ID="default_server"
```

```bash
// config/app.php

'version' => '1.0.0',
```
