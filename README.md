# laravel-logbook

installitation

```bash
composer require solvrtech/laravel-logbook
```

Configuration
You should publish the config/logging.php config file with:
```bash
php artisan vendor:publish --provider=Solvrtech\Laravel\Logbook\LogbookServiceProvider --force
```

```bash
.env

LOGBOOK_URL="https://logbook.solvrtech.id"
LOGBOOK_KEY="4eaa39a6ff57c4d5b2cd0a01297e219e323380ea43ef2565b4774d710f727dd243a48aa9ae32f10757d19246f5167e945d4d521b2dbc0f5119bbb1c2b493ef70"
```

```bash
// config/app.php

'version' => '1.0.0',
```
