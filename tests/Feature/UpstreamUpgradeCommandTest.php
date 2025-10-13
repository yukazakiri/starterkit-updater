<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Tests\TestCase;

uses(TestCase::class);

describe('UpstreamUpgradeCommand PR Creation', function () {
    it('defaults to creating PR when no options are provided', function () {
        $this->artisan('laravelplus:update --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('creates PR when --direct option is not used', function () {
        $this->artisan('laravelplus:update --dry-run --test')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('applies changes directly when --direct option is used', function () {
        $this->artisan('laravelplus:update --direct --dry-run')
            ->assertExitCode(0);
    });

    it('shows correct help text for new --direct option', function () {
        $this->artisan('laravelplus:update --help')
            ->expectsOutput('      --direct                     Apply changes directly without creating a pull request')
            ->assertExitCode(0);
    });

    it('shows updated description mentioning PR by default', function () {
        $this->artisan('laravelplus:update --help')
            ->expectsOutput('  Upgrade your project with the latest changes from upstream repository (creates PR by default).')
            ->assertExitCode(0);
    });

    it('passes all options correctly to PR command when creating PR', function () {
        $this->artisan('laravelplus:update --dry-run --strategy=rebase --pr-title="Custom PR Title" --pr-body="Custom PR Body"')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('handles test option correctly with PR creation', function () {
        $this->artisan('laravelplus:update --test --dry-run')
            ->expectsOutput('🔍 Testing upstream sync configuration...')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('handles no-pre and no-post options with PR creation', function () {
        $this->artisan('laravelplus:update --no-pre --no-post --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('handles custom upstream and branch options with PR creation', function () {
        $this->artisan('laravelplus:update --upstream=https://github.com/test/repo.git --branch=develop --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('handles custom PR options correctly', function () {
        $this->artisan('laravelplus:update --pr-branch=custom-branch --pr-title="My Custom Title" --pr-body="My Custom Body" --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });
});

describe('UpstreamUpgradeCommand Integration', function () {
    it('can run check command and shows updated help text', function () {
        $this->artisan('laravelplus:check')
            ->expectsOutput('🔍 Checking for upstream updates...')
            ->assertExitCode(0);
    });


    it('respects custom remote name option', function () {
        $this->artisan('laravelplus:update --remote-name=origin --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('handles local branch override correctly', function () {
        $this->artisan('laravelplus:update --local=feature-branch --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });
});

describe('Command Options Validation', function () {
    it('accepts valid strategy options', function () {
        $this->artisan('laravelplus:update --strategy=merge --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);

        $this->artisan('laravelplus:update --strategy=rebase --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('handles empty string options gracefully', function () {
        $this->artisan('laravelplus:update --upstream="" --branch="" --dry-run')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });

    it('processes multiple options together correctly', function () {
        $this->artisan('laravelplus:update --dry-run --test --no-pre --no-post --strategy=rebase --pr-title="Test PR"')
            ->expectsOutput('🔍 Testing upstream sync configuration...')
            ->expectsOutput('🚀 Creating pull request with upstream updates...')
            ->assertExitCode(0);
    });
});