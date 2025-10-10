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

it('handles different local branch names correctly', function () {
    // Test with master branch (default in many repos)
    $this->artisan('laravelplus:check')
        ->expectsOutput('📍 Using current branch: master (configured: main)')
        ->assertExitCode(0);
});

it('always uses main for upstream branch', function () {
    // Test that upstream branch is always main regardless of config
    config(['upstream.upstream_branch' => 'develop']);

    $this->artisan('laravelplus:check')
        ->expectsOutput('📍 Using upstream branch: main (configured: develop)')
        ->assertExitCode(0);
});

it('handles missing upstream remote', function () {
    // This test would need to mock the git commands to simulate missing remote
    $this->artisan('laravelplus:check')
        ->assertExitCode(0);
});

it('respects command line branch overrides', function () {
    $this->artisan('laravelplus:check --local=feature-branch --branch=develop')
        ->expectsOutput('📍 Using current branch: master (configured: feature-branch)')
        ->expectsOutput('📍 Using upstream branch: main (configured: develop)')
        ->assertExitCode(0);
});
