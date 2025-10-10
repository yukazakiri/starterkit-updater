<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider;

it('can be instantiated', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    expect($provider)->toBeInstanceOf(LaravelUpdaterServiceProvider::class);
});

it('registers commands', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    // Test that the provider can be booted without errors
    expect(fn () => $provider->boot())->not->toThrow();
});

it('merges configuration', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    // Test that the provider can be booted without errors
    expect(fn () => $provider->boot())->not->toThrow();
    
    // Check that the configuration is available
    expect(config('upstream'))->toBeArray();
});

it('publishes configuration', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    // Test that the provider can be booted without errors
    expect(fn () => $provider->boot())->not->toThrow();
    
    // Check that the configuration can be published
    $published = $provider->publishes();
    
    expect($published)->toBeArray();
    expect($published)->toHaveKey('upstream-config');
});

it('has correct package name', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    expect($provider->package)->toBe('laravelplus/laravel-updater');
});

it('has correct configuration file path', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    $published = $provider->publishes();
    $configPath = $published['upstream-config'][0];
    
    expect($configPath)->toContain('upstream.php');
});

it('registers all required commands', function () {
    $artisan = app('Illuminate\Contracts\Console\Kernel');
    
    // Check that all our commands are registered
    $commands = [
        'laravelplus:setup',
        'laravelplus:check',
        'laravelplus:sync',
        'laravelplus:update',
        'laravelplus:pr',
    ];
    
    foreach ($commands as $command) {
        expect($artisan->all())->toHaveKey($command);
    }
});

it('provides correct service provider class', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    expect($provider)->toBeInstanceOf(\Illuminate\Support\ServiceProvider::class);
});

it('can be registered in application', function () {
    $app = app();
    
    // Test that we can register the provider
    expect(fn () => $app->register(LaravelUpdaterServiceProvider::class))->not->toThrow();
});

it('has correct namespace', function () {
    $provider = new LaravelUpdaterServiceProvider(app());
    
    expect($provider::class)->toBe('LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider');
});
