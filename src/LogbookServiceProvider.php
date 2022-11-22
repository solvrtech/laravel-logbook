<?php

namespace Solvrtech\Laravel\Logbook;

use Solvrtech\Laravel\Logbook\Command\HealthStatusCommand;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class LogbookServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/logbook.php', 'logbook');
        $this->app->bind(
            LoggerInterface::class,
            Logbook::class
        );
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
                HealthStatusCommand::class
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/logbook.php' => config_path('logbook.php'),
        ]);
    }
}
