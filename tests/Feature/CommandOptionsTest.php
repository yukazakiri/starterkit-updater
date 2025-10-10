<?php

declare(strict_types=1);

it('can run sync command with merge strategy', function () {
    $this->artisan('laravelplus:sync --strategy=merge --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command with rebase strategy', function () {
    $this->artisan('laravelplus:sync --strategy=rebase --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command with custom upstream URL', function () {
    $this->artisan('laravelplus:sync --upstream=https://github.com/test/repo.git --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command with custom branch', function () {
    $this->artisan('laravelplus:sync --branch=develop --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command with custom local branch', function () {
    $this->artisan('laravelplus:sync --local=feature-branch --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command with custom remote name', function () {
    $this->artisan('laravelplus:sync --remote-name=origin --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command without pre hooks', function () {
    $this->artisan('laravelplus:sync --no-pre --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run sync command without post hooks', function () {
    $this->artisan('laravelplus:sync --no-post --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run upgrade command with pr option', function () {
    $this->artisan('laravelplus:update --pr --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run pr command with custom branch prefix', function () {
    $this->artisan('laravelplus:pr --pr-branch-prefix=feature/ --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run pr command with custom title', function () {
    $this->artisan('laravelplus:pr --pr-title="Custom PR Title" --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run pr command with custom body', function () {
    $this->artisan('laravelplus:pr --pr-body="Custom PR Body" --dry-run')
        ->expectsOutput('🚀 Laravel Updater - Keep your projects up to date!')
        ->assertExitCode(0);
});

it('can run setup command with force option', function () {
    $this->artisan('laravelplus:setup --preset=vue --force')
        ->expectsOutput('🚀 Laravel Updater Setup')
        ->assertExitCode(0);
});

it('fails with invalid strategy', function () {
    $this->artisan('laravelplus:sync --strategy=invalid --dry-run')
        ->assertExitCode(1);
});

it('fails with missing upstream URL for custom preset', function () {
    $this->artisan('laravelplus:setup --preset=custom')
        ->assertExitCode(1);
});
