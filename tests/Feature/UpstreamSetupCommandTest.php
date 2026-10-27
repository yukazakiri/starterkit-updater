<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Tests\Support\TestRepository;

it('writes preset configuration to the .env file', function () {
    $repository = TestRepository::create();
    $repository->write('.env', "APP_NAME=Test\n");

    $originalBasePath = app()->basePath();
    app()->setBasePath($repository->path);
    config(['upstream.working_dir' => $repository->path]);

    try {
        $this->artisan('laravelplus:setup --preset=vue --upstream-branch=main --local-branch=main --no-interaction')
            ->expectsOutputToContain('Setup complete for Laravel vue starter kit')
            ->assertExitCode(0);

        $env = (string) file_get_contents($repository->path.'/.env');

        expect($env)
            ->toContain('UPSTREAM_URL=https://github.com/laravel/vue-starter-kit.git')
            ->toContain('UPSTREAM_BRANCH=main')
            ->toContain('UPSTREAM_LOCAL_BRANCH=main')
            ->toContain('UPSTREAM_DEFAULT_PRESET=vue');
    } finally {
        app()->setBasePath($originalBasePath);
    }
});

it('writes custom repository configuration to the .env file', function () {
    $repository = TestRepository::create();
    $repository->write('.env', "APP_NAME=Test\n");

    $originalBasePath = app()->basePath();
    app()->setBasePath($repository->path);
    config(['upstream.working_dir' => $repository->path]);

    try {
        $this->artisan('laravelplus:setup --preset=custom --url=https://github.com/acme/app.git --upstream-branch=main --local-branch=develop --no-interaction')
            ->expectsOutputToContain('Custom setup complete')
            ->assertExitCode(0);

        $env = (string) file_get_contents($repository->path.'/.env');

        expect($env)
            ->toContain('UPSTREAM_URL=https://github.com/acme/app.git')
            ->toContain('UPSTREAM_LOCAL_BRANCH=develop')
            ->toContain('UPSTREAM_DEFAULT_PRESET=custom');
    } finally {
        app()->setBasePath($originalBasePath);
    }
});

it('rejects an unknown preset', function () {
    $this->artisan('laravelplus:setup --preset=unknown --no-interaction')
        ->expectsOutputToContain('Unknown preset: unknown')
        ->assertExitCode(1);
});
