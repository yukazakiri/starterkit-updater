<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Exceptions\UpstreamException;

it('creates a missing configuration exception', function () {
    expect(UpstreamException::configurationMissing('upstream_url'))
        ->getMessage()
        ->toBe("Required configuration key 'upstream_url' is missing or empty.");
});

it('creates an invalid strategy exception', function () {
    expect(UpstreamException::invalidStrategy('invalid'))
        ->getMessage()
        ->toBe("Invalid strategy 'invalid'. Must be 'merge' or 'rebase'.");
});

it('creates a git command failure exception', function () {
    $previous = new RuntimeException('Original error');
    $exception = UpstreamException::gitCommandFailed('fetch upstream', 'denied', $previous);

    expect($exception)
        ->getMessage()->toBe('Git command failed: fetch upstream (denied)')
        ->getPrevious()->toBe($previous);
});

it('creates a remote not found exception', function () {
    expect(UpstreamException::remoteNotFound('upstream'))
        ->getMessage()->toBe("Git remote 'upstream' not found.");
});

it('creates a branch not found exception', function () {
    expect(UpstreamException::branchNotFound('main'))
        ->getMessage()->toBe("Git branch 'main' not found.");
});

it('creates a working directory exception', function () {
    expect(UpstreamException::workingDirectoryNotWritable('/tmp'))
        ->getMessage()->toBe("Working directory '/tmp' is not writable.");
});

it('creates a not-a-git-repository exception', function () {
    expect(UpstreamException::notAGitRepository('/tmp'))
        ->getMessage()->toBe("Directory '/tmp' is not a git repository.");
});

it('creates a hook failure exception with the previous exception', function () {
    $previous = new RuntimeException('Command failed');
    $exception = UpstreamException::hookFailed('npm install', $previous);

    expect($exception)
        ->getMessage()->toBe('Pre/post update hook failed: npm install')
        ->getPrevious()->toBe($previous);
});

it('extends RuntimeException', function () {
    expect(UpstreamException::configurationMissing('test'))
        ->toBeInstanceOf(RuntimeException::class);
});
