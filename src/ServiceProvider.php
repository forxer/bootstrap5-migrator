<?php

namespace Bootstrap5Migrator;

use Bootstrap5Migrator\Commands\AnalyzeBootstrap4Command;
use Bootstrap5Migrator\Commands\GenerateReportCommand;
use Bootstrap5Migrator\Commands\MigrateToBootstrap5Command;
use Bootstrap5Migrator\Commands\ValidateBootstrap5Command;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/bootstrap5-migrator.php', 'bootstrap5-migrator'
        );

        $this->app->singleton(Bootstrap5Migrator::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bootstrap5-migrator');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateToBootstrap5Command::class,
                AnalyzeBootstrap4Command::class,
                ValidateBootstrap5Command::class,
                GenerateReportCommand::class,
            ]);

            $this->configurePublishing();
        }
    }

    private function configurePublishing(): void
    {
        // config
        $this->publishes([
            __DIR__.'/../config/bootstrap5-migrator.php' => config_path('bootstrap5-migrator.php'),
        ], 'bootstrap5-migrator-config');

        // views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/bootstrap5-migrator'),
        ], 'bootstrap5-migrator-views');
    }
}
