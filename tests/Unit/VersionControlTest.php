<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Support\VersionControl;
use LaravelPlus\LaravelUpdater\Tests\Support\TestRepository;

it('can be instantiated', function () {
    $versionControl = new VersionControl('git', '/tmp');

    expect($versionControl)->toBeInstanceOf(VersionControl::class);
});

it('returns the configured git binary and working directory', function () {
    $versionControl = new VersionControl('git', '/tmp');

    expect($versionControl->getGitBinary())->toBe('git')
        ->and($versionControl->getWorkingDirectory())->toBe('/tmp');
});

it('runs a real git command', function () {
    $repository = TestRepository::create();
    $versionControl = new VersionControl('git', $repository->path);

    expect($versionControl->run(['--version']))->toContain('git version');
});

it('detects an existing remote', function () {
    $repository = TestRepository::create();
    $repository->git(['remote', 'add', 'origin', 'https://github.com/test/repo.git']);

    $versionControl = new VersionControl('git', $repository->path);

    expect($versionControl->hasRemote('origin'))->toBeTrue()
        ->and($versionControl->hasRemote('upstream'))->toBeFalse();
});

it('throws when a git command fails', function () {
    $repository = TestRepository::create();
    $versionControl = new VersionControl('git', $repository->path);

    expect(fn () => $versionControl->run(['not-a-real-git-command']))
        ->toThrow(RuntimeException::class);
});

it('does not throw when a failed command is allowed to fail', function () {
    $repository = TestRepository::create();
    $versionControl = new VersionControl('git', $repository->path);

    $output = $versionControl->run(['not-a-real-git-command'], throw: false);

    expect($output)->not->toBe('');
});

it('trims command output', function () {
    $repository = TestRepository::create();
    $versionControl = new VersionControl('git', $repository->path);

    expect($versionControl->run(['config', 'user.name']))->toBe('Updater Tests');
});
