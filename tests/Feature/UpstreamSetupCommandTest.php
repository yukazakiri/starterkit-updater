<?php

declare(strict_types=1);

it('can run setup command with vue preset', function () {
    $this->artisan('laravelplus:setup --preset=vue')
        ->expectsOutput('🚀 Laravel Updater Setup')
        ->assertExitCode(0);
});

it('can run setup command with react preset', function () {
    $this->artisan('laravelplus:setup --preset=react')
        ->expectsOutput('🚀 Laravel Updater Setup')
        ->assertExitCode(0);
});

it('can run setup command with livewire preset', function () {
    $this->artisan('laravelplus:setup --preset=livewire')
        ->expectsOutput('🚀 Laravel Updater Setup')
        ->assertExitCode(0);
});

it('can run setup command with custom URL', function () {
    $this->artisan('laravelplus:setup --preset=custom --url=https://github.com/test/repo.git')
        ->expectsOutput('🚀 Laravel Updater Setup')
        ->assertExitCode(0);
});

it('can run check command', function () {
    $this->artisan('laravelplus:check')
        ->expectsOutput('🔍 Checking for upstream updates...')
        ->assertExitCode(0);
});

it('can run sync command with dry run', function () {
    $this->artisan('laravelplus:sync --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run upgrade command with dry run', function () {
    $this->artisan('laravelplus:update --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run pr command with dry run', function () {
    $this->artisan('laravelplus:pr --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command with test option', function () {
    $this->artisan('laravelplus:sync --test --dry-run')
        ->expectsOutput('🔍 Testing upstream sync configuration...')
        ->assertExitCode(0);
});

it('can run upgrade command with test option', function () {
    $this->artisan('laravelplus:update --test --dry-run')
        ->expectsOutput('🔍 Testing upstream sync configuration...')
        ->assertExitCode(0);
});

it('can run pr command with test option', function () {
    $this->artisan('laravelplus:pr --test --dry-run')
        ->expectsOutput('🔍 Testing upstream sync configuration...')
        ->assertExitCode(0);
});
