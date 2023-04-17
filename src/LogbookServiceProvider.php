<?php

namespace Solvrtech\Logbook;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Solvrtech\Logbook\Check\CacheCheck;
use Solvrtech\Logbook\Check\CPULoadCheck;
use Solvrtech\Logbook\Check\DataBaseCheck;
use Solvrtech\Logbook\Check\MemoryCheck;
use Solvrtech\Logbook\Check\RedisCheck;
use Solvrtech\Logbook\Check\UsedDiskCheck;
use Solvrtech\Logbook\Middleware\LogbookMiddleware;

class LogbookServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            LoggerInterface::class,
            function ($app) {
                return new Logbook($app);
            }
        );

        $this->app->bind(
            LogbookHealth::class,
            function ($app) {
                return new LogbookHealth([
                    CacheCheck::new(),
                    CPULoadCheck::new(),
                    DataBaseCheck::new(),
                    MemoryCheck::new(),
                    RedisCheck::new(),
                    UsedDiskCheck::new()
                ]);
            }
        );
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        // publish configuration
        $this->publishes([
            __DIR__ . '/../config/logging.php' => config_path('logging.php'),
        ], 'logbook');

        // register middleware
        app('router')->aliasMiddleware('logbook', LogbookMiddleware::class);

        // publish route
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
