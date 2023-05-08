# laravel-logbook

Installitation

```bash
composer require solvrtech/laravel-logbook
```

Configuration<br>
You should publish the config/logging.php config file with:

```bash
php artisan vendor:publish --tag=logbook --force
```

If you do not want to change the existing logging.php configuration,
add the following configuration:

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

Additional: send current project version.

```bash
// config/app.php

'version' => '1.0.0',
```
