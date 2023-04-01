# laravel-logbook

installitation

```bash
composer require solvrtech/laravel-logbook
```

Configuration<br>
You should publish the config/logging.php config file with:
```bash
php artisan vendor:publish --tag=logbook --force
```

```bash
.env

LOGBOOK_API_URL="https://logbook.com"
LOGBOOK_API_KEY="4eaa39a6ff57c4..."
```

Additional: send current project version.
```bash
// config/app.php

'version' => '1.0.0',
```
