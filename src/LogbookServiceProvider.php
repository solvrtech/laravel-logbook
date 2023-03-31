<?php

namespace Solvrtech\Laravel\Logbook;

use Illuminate\Log\LogManager;
use Solvrtech\Laravel\Logbook\Command\HealthStatusCommand;
use Illuminate\Support\ServiceProvider;

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
            LogManager::class,
            function ($app) {
                return new Logbook($app);
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
        // register artisan command
        if ($this->app->runningInConsole()) {
            $this->commands([
                HealthStatusCommand::class
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/logging.php' => config_path('logging.php'),
        ]);
    }
}
