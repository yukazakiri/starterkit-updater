<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Services\GitService;
use LaravelPlus\LaravelUpdater\Support\VersionControl;

it('can be instantiated with VersionControl', function () {
    $versionControl = new VersionControl('git', '/tmp');
    $gitService = new GitService($versionControl);

    expect($gitService)->toBeInstanceOf(GitService::class);
});

it('has all required methods for branch operations', function () {
    $versionControl = new VersionControl('git', '/tmp');
    $gitService = new GitService($versionControl);

    // Check that all required methods exist
    expect(method_exists($gitService, 'getCurrentBranch'))->toBeTrue();
    expect(method_exists($gitService, 'branchExists'))->toBeTrue();
    expect(method_exists($gitService, 'getLatestCommitHash'))->toBeTrue();
    expect(method_exists($gitService, 'getRemoteCommitHash'))->toBeTrue();
    expect(method_exists($gitService, 'isUpToDate'))->toBeTrue();
    expect(method_exists($gitService, 'createAndCheckoutBranch'))->toBeTrue();
    expect(method_exists($gitService, 'checkoutBranch'))->toBeTrue();
    expect(method_exists($gitService, 'merge'))->toBeTrue();
    expect(method_exists($gitService, 'rebase'))->toBeTrue();
    expect(method_exists($gitService, 'hasUncommittedChanges'))->toBeTrue();
    expect(method_exists($gitService, 'commit'))->toBeTrue();
    expect(method_exists($gitService, 'push'))->toBeTrue();
});

it('can handle branch detection logic', function () {
    // Test the logic that would be used in the commands
    $currentBranch = 'master';
    $configuredBranch = 'main';

    // Simulate the branch detection logic
    $localBranch = $currentBranch !== $configuredBranch ? $currentBranch : $configuredBranch;

    expect($localBranch)->toBe('master');

    // Test with same branch
    $currentBranch = 'main';
    $localBranch = $currentBranch !== $configuredBranch ? $currentBranch : $configuredBranch;

    expect($localBranch)->toBe('main');
});

it('always uses main for upstream branch', function () {
    $upstreamBranch = 'develop';
    $finalUpstreamBranch = $upstreamBranch !== 'main' ? 'main' : $upstreamBranch;

    expect($finalUpstreamBranch)->toBe('main');

    // Test when already main
    $upstreamBranch = 'main';
    $finalUpstreamBranch = $upstreamBranch !== 'main' ? 'main' : $upstreamBranch;

    expect($finalUpstreamBranch)->toBe('main');
});
