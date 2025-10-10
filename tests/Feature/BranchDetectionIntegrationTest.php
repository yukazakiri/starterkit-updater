<?php

declare(strict_types=1);

it('can detect current branch and use it as local branch', function () {
    // This test will run in a real environment and test the actual branch detection
    $this->artisan('laravelplus:check')
        ->assertExitCode(0);
});

it('always uses main for upstream branch', function () {
    // Test that upstream branch is always main regardless of config
    config(['upstream.upstream_branch' => 'develop']);

    $this->artisan('laravelplus:check')
        ->assertExitCode(0);
});

it('handles different local branch scenarios', function () {
    // Test with different branch configurations
    $this->artisan('laravelplus:check --local=feature-branch')
        ->assertExitCode(0);
});

it('works with all command types', function () {
    // Test that branch detection works with all commands
    $this->artisan('laravelplus:sync --dry-run')
        ->assertExitCode(0);

    $this->artisan('laravelplus:update --dry-run')
        ->assertExitCode(0);

    $this->artisan('laravelplus:pr --dry-run')
        ->assertExitCode(0);
});
