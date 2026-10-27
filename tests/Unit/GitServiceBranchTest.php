<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Services\GitService;
use LaravelPlus\LaravelUpdater\Support\VersionControl;

it('can be instantiated with VersionControl', function () {
    $gitService = new GitService(new VersionControl('git', '/tmp'));

    expect($gitService)->toBeInstanceOf(GitService::class);
});

it('exposes all branch operation methods', function () {
    $gitService = new GitService(new VersionControl('git', '/tmp'));

    foreach ([
        'getCurrentBranch',
        'branchExists',
        'getLatestCommitHash',
        'getRemoteCommitHash',
        'isUpToDate',
        'createAndCheckoutBranch',
        'checkoutBranch',
        'ensureBranchExists',
        'merge',
        'rebase',
        'hasUncommittedChanges',
        'commit',
        'push',
    ] as $method) {
        expect(method_exists($gitService, $method))->toBeTrue();
    }
});
