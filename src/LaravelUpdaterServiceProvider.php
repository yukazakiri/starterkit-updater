<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater;

use Illuminate\Support\ServiceProvider;
use LaravelPlus\LaravelUpdater\Console\Commands\UpstreamCheckCommand;
use LaravelPlus\LaravelUpdater\Console\Commands\UpstreamPrCommand;
use LaravelPlus\LaravelUpdater\Console\Commands\UpstreamSetupCommand;
use LaravelPlus\LaravelUpdater\Console\Commands\UpstreamSyncCommand;
use LaravelPlus\LaravelUpdater\Console\Commands\UpstreamUpgradeCommand;

final class LaravelUpdaterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpstreamSyncCommand::class,
                UpstreamSetupCommand::class,
                UpstreamPrCommand::class,
                UpstreamCheckCommand::class,
                UpstreamUpgradeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/Config/upstream.php' => config_path('upstream.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/upstream.php',
            'upstream'
        );
    }
}
