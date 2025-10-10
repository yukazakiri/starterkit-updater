<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater;

use Illuminate\Support\ServiceProvider;
use LaravelPlus\LaravelUpdater\Console\Commands\UpstreamSyncCommand;

final class LaravelUpdaterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpstreamSyncCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../Config/upstream.php' => config_path('upstream.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/upstream.php',
            'upstream'
        );
    }
}
