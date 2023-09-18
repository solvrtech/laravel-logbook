# laravel-logbook

[Laravel-logbook](https://github.com/solvrtech/laravel-logbook) package extends Laravel's logging functionality by
adding additional capabilities to send the log messages to the targeted LogBook installation. You can install the
package by running the following command in the root folder of your Laravel application:

```bash
composer require solvrtech/laravel-logbook
```

then publish the **config/logging.php** config file as follows:

```bash
php artisan vendor:publish --tag=logbook
```

Edit the channels stack in the **config/logging.php** file to use “logbook” as follows:

```php
// ...
'channels' => [
    // ...
    
    'logbook' => [
        'driver' => 'logbook',
        'level' => env('LOG_LEVEL', 'debug')
    ],
]
```

**laravel-logbook** also needs appropriate configuration in your application’s **.env** file.  
For example:

```dotenv
LOG_CHANNEL=logbook
LOG_LEVEL=debug
LOGBOOK_API_URL="https://logbook.com"
LOGBOOK_API_KEY="4eaa39a6ff57c4..."

# Instance ID is a unique identifier per instance of your apps
LOGBOOK_INSTANCE_ID="default"
```

- **LOGBOOK_INSTANCE_ID**  
  Define a unique identifier for the app’s deployment. This is useful when you have multiple app instances or
  deployments (e.g. in horizontally-scaled environments). For example, you can set “app-1” for the first instance and
  “app-2” for the second one. The instance ID information will be shown as part of log details in LogBook.
- **LOGBOOK_API_URL**  
  The actual URL of your LogBook installation
- **LOGBOOK_API_KEY**  
  The API key that LogBook had generated for the app
- **LOG_CHANNEL**  
  Must use **logbook** as defined in the **config/logging.php** file
- **LOG_LEVEL**  
  Specify the minimum log level that will be submitted to your LogBook installation. The ordered log levels are (from
  lowest to highest):

1. DEBUG
2. INFO
3. NOTICE
4. WARNING
5. ERROR
6. CRITICAL
7. ALERT
8. EMERGENCY

For example, if you set LOG_LEVEL=WARNING, then only higher priority log levels such as WARNING, ERROR, CRITICAL,
ALERT, and EMERGENCY are going to be submitted to your LogBook installation.

## Submitting logs into LogBook

To submit any log message, you just need to use the Laravel **Log** facade in your controller or service class:

```php
use Illuminate\Support\Facades\Log;

class UserController extends Controller {

public function show($message) {
      Log::emergency($message);
      Log::alert($message);
      Log::critical($message);
      Log::error($message);
      Log::warning($message);
      Log::notice($message);
      Log::info($message);
      Log::debug($message);
      // ...
   }
}
```

More info about Laravel logging can be found in [their documentation page.](https://laravel.com/docs/10.x/logging)

## Submitting logs asynchronously

By default, logs from your application will be submitted synchronously as soon as they are recorded and this might lead
to a performance issue for your application. Fortunately, you can submit the logs asynchronously by queuing the logs (
inside database or Redis) and then create a background task to submit the queue of logs in batch.

### 1. Storage for Queues

Set the following configuration in your application **.env** file:

```dotenv
# "database" or "redis"
LOGBOOK_TRANSPORT="database"
LOGBOOK_BATCH=15
```

- **LOGBOOK_TRANSPORT:**
  specifies the type of storage for queuing mechanisms. Supported values are “database” or “redis”.
- **LOGBOOK_BATCH:**
  maximum number of logs to be sent from your application into LogBook in a batch.

After configuring the storage for queuing of submitted logs, you will need to create a background task that will
run: **php artisan logbook:log:consume** periodically. You can set this by using Systemd or Supervisor.

### 2.a Using Systemd

Create a new service file, for example
**/etc/systemd/system/log-consume.service**, then add the following configurations into the file:

```
[Unit]
Description=Log Consume
After=network.target

[Service]
ExecStart=/usr/bin/php artisan logbook:log:consume
WorkingDirectory=/path/to/your/laravel
User=www-data
Restart=always

[Install]
WantedBy=multi-user.target
```

Start the service and enable it during system reboot:

```bash
sudo systemctl start log-consume && sudo systemctl enable log-consume
```

### 2.b Using Supervisor

Create a new configuration file for the log consume service, for example
**/etc/supervisor/conf.d/log-consume.conf**. Add the following configurations into the file:

```
[program:log-consume]
command=php /path/to/your/laravel/artisan logbook:log:consume
directory=/path/to/your/laravel
autostart=true
autorestart=true
stderr_logfile=/var/log/log-consume.err.log
stdout_logfile=/var/log/log-consume.out.log
user=www-data
```

To start the service, run the following commands:

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start log-consume
```

## Optional: set your application version

Application version is an optional parameter that can also be included inside log submission data into your LogBook
installation. To do so, add the "version" config in **/config/app.php** file:

```php
return [
    // ...

    'version' => "1.0.0"
];
```

It's worth noting that while it's recommended to set the application version, it is an optional step. When the "version"
config is not found, log submission should work normally, but the version information will not be found in the submitted
logs.
