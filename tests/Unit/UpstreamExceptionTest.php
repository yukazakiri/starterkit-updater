<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Exceptions\UpstreamException;

it('can create configuration missing exception', function () {
    $exception = UpstreamException::configurationMissing('upstream_url');
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe("Upstream configuration key 'upstream_url' is missing or empty.");
});

it('can create invalid strategy exception', function () {
    $exception = UpstreamException::invalidStrategy('invalid');
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe("Invalid strategy 'invalid'. Must be 'merge' or 'rebase'.");
});

it('can create git command failed exception', function () {
    $exception = UpstreamException::gitCommandFailed('Failed to fetch');
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe('Git command failed: Failed to fetch');
});

it('can create git command failed exception with previous exception', function () {
    $previous = new RuntimeException('Original error');
    $exception = UpstreamException::gitCommandFailed('Failed to fetch', $previous);
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe('Git command failed: Failed to fetch')
        ->getPrevious()
        ->toBe($previous);
});

it('can create remote not found exception', function () {
    $exception = UpstreamException::remoteNotFound('upstream');
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe("Git remote 'upstream' not found.");
});

it('can create branch not found exception', function () {
    $exception = UpstreamException::branchNotFound('main');
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe("Git branch 'main' not found.");
});

it('can create working directory not writable exception', function () {
    $exception = UpstreamException::workingDirectoryNotWritable('/tmp');
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe("Working directory '/tmp' is not writable.");
});

it('can create hook failed exception', function () {
    $exception = UpstreamException::hookFailed('npm install');
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe('Pre/post update hook failed: npm install');
});

it('can create hook failed exception with previous exception', function () {
    $previous = new RuntimeException('Command failed');
    $exception = UpstreamException::hookFailed('npm install', $previous);
    
    expect($exception)
        ->toBeInstanceOf(UpstreamException::class)
        ->getMessage()
        ->toBe('Pre/post update hook failed: npm install')
        ->getPrevious()
        ->toBe($previous);
});

it('extends RuntimeException', function () {
    $exception = UpstreamException::configurationMissing('test');
    
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
