<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider;

it('can be instantiated', function () {
    $provider = new LaravelUpdaterServiceProvider(app());

    expect($provider)->toBeInstanceOf(LaravelUpdaterServiceProvider::class);
});

it('merges the package configuration', function () {
    expect(config('upstream'))->toBeArray()
        ->and(config('upstream.presets'))->toHaveKeys(['vue', 'react', 'livewire']);
});

it('publishes the configuration file', function () {
    $paths = Illuminate\Support\ServiceProvider::pathsToPublish(
        LaravelUpdaterServiceProvider::class,
        'config'
    );

    expect($paths)->not->toBeEmpty()
        ->and(array_key_first($paths))->toContain('upstream.php');
});

it('registers the package commands', function () {
    $registered = app('Illuminate\Contracts\Console\Kernel')->all();

    expect($registered)->toHaveKeys([
        'laravelplus:setup',
        'laravelplus:check',
        'laravelplus:update',
    ]);
});

it('provides the correct service provider class', function () {
    expect(LaravelUpdaterServiceProvider::class)
        ->toBe('LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider');
});
