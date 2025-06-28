<?php

namespace Bootstrap5Migrator;

use Bootstrap5Migrator\Commands\MigrateToBootstrap5Command;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateToBootstrap5Command::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/bootstrap5-migrator.php' => config_path('bootstrap5-migrator.php'),
        ], 'bootstrap5-migrator-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/bootstrap5-migrator.php', 'bootstrap5-migrator'
        );

        $this->app->singleton(Bootstrap5Migrator::class);
    }
}
