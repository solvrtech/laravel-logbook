<?php

namespace Solvrtech\Logbook;

use Exception;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Solvrtech\Logbook\Check\CacheCheck;
use Solvrtech\Logbook\Check\CPULoadCheck;
use Solvrtech\Logbook\Check\DataBaseCheck;
use Solvrtech\Logbook\Check\MemoryCheck;
use Solvrtech\Logbook\Check\UsedDiskCheck;
use Solvrtech\Logbook\Command\ConsumeCommand;
use Solvrtech\Logbook\Middleware\LogbookMiddleware;
use Solvrtech\Logbook\Transport\database\DatabaseTransport;
use Solvrtech\Logbook\Transport\redis\RedisTransport;
use Solvrtech\Logbook\Transport\sync\SyncTransport;
use Solvrtech\Logbook\Transport\TransportInterface;

class LogbookServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     * @throws Exception
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/logbook.php', 'logbook');

        $this->app->singleton('log', function ($app) {
            $transport = $this->transport();

            return new Logbook($app, new $transport());
        });

        $this->app->bind(
            LoggerInterface::class,
            function ($app) {
                $transport = $this->transport();

                return new Logbook($app, new $transport());
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
                    UsedDiskCheck::new(),
                ]);
            }
        );

        $this->app->bind(TransportInterface::class, $this->transport());
    }

    /**
     * Get the current configuration for the transport. (redis, database, or sync)
     *
     * @return string
     * @throws Exception
     */
    private function transport(): string
    {
        $config = config('logbook');

        if (!isset($config['transport'])) {
            throw new Exception('Logbook transport not found');
        }

        switch ($config['transport']['driver']) {
            case 'sync':
                return SyncTransport::class;
            case 'redis':
                return RedisTransport::class;
            case 'database':
                return DatabaseTransport::class;
        }

        throw new Exception('Something wrong with transport configuration!');
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        // register artisan command
        if ($this->app->runningInConsole()) {
            $this->commands([
                ConsumeCommand::class,
            ]);
        }

        // publish configuration
        $this->publishes([
            __DIR__.'/../config/logging.php' => config_path('logging.php'),
            __DIR__.'/../config/logbook.php' => config_path('logbook.php'),
        ], 'logbook');

        // register middleware
        app('router')->aliasMiddleware('logbook', LogbookMiddleware::class);

        // publish route
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
