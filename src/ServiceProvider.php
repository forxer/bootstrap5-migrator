<?php

namespace Bootstrap5Migrator;

use Bootstrap5Migrator\Commands\AnalyzeBootstrap4Command;
use Bootstrap5Migrator\Commands\GenerateReportCommand;
use Bootstrap5Migrator\Commands\MigrateToBootstrap5Command;
use Bootstrap5Migrator\Commands\ValidateBootstrap5Command;
use Bootstrap5Migrator\Services\CacheService;
use Bootstrap5Migrator\Services\FileProcessorService;
use Bootstrap5Migrator\Services\ProgressService;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/bootstrap5-migrator.php', 'bootstrap5-migrator'
        );

        // Core services
        $this->app->singleton(Bootstrap5Migrator::class);

        // Performance services
        $this->app->singleton(CacheService::class);
        $this->app->singleton(FileProcessorService::class);

        // ProgressService needs output interface so can't be singleton
        $this->app->bind(ProgressService::class, fn ($app): ProgressService => new ProgressService($app[OutputStyle::class] ?? new ConsoleOutput()));
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
