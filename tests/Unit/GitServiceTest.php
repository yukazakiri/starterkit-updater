<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Services\GitService;
use LaravelPlus\LaravelUpdater\Support\VersionControl;

beforeEach(function () {
    $this->versionControl = Mockery::mock(VersionControl::class);
    $this->gitService = new GitService($this->versionControl);
});

afterEach(function () {
    Mockery::close();
});

it('can add remote if it does not exist', function () {
    $this->versionControl->shouldReceive('hasRemote')
        ->with('upstream')
        ->once()
        ->andReturn(false);

    $this->versionControl->shouldReceive('run')
        ->with(['remote', 'add', 'upstream', 'https://github.com/test/repo.git'])
        ->once()
        ->andReturn('');

    $result = $this->gitService->addRemoteIfNotExists('upstream', 'https://github.com/test/repo.git');

    expect($result)->toBeTrue();
});

it('returns true if remote already exists', function () {
    $this->versionControl->shouldReceive('hasRemote')
        ->with('upstream')
        ->once()
        ->andReturn(true);

    $result = $this->gitService->addRemoteIfNotExists('upstream', 'https://github.com/test/repo.git');

    expect($result)->toBeTrue();
});

it('can fetch from remote', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['fetch', 'upstream'])
        ->once()
        ->andReturn('From https://github.com/test/repo.git');

    $result = $this->gitService->fetch('upstream');

    expect($result)->toBe('From https://github.com/test/repo.git');
});

it('can get current branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--abbrev-ref', 'HEAD'])
        ->once()
        ->andReturn('main');

    $result = $this->gitService->getCurrentBranch();

    expect($result)->toBe('main');
});

it('can check if branch exists', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--verify', 'main'], false)
        ->once()
        ->andReturn('abc123');

    $result = $this->gitService->branchExists('main');

    expect($result)->toBeTrue();
});

it('returns false if branch does not exist', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--verify', 'nonexistent'], false)
        ->once()
        ->andThrow(new RuntimeException('fatal: Needed a single revision'));

    $result = $this->gitService->branchExists('nonexistent');

    expect($result)->toBeFalse();
});

it('can create and checkout new branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['checkout', '-b', 'feature-branch'])
        ->once()
        ->andReturn('Switched to a new branch \'feature-branch\'');

    $result = $this->gitService->createAndCheckoutBranch('feature-branch');

    expect($result)->toBe('Switched to a new branch \'feature-branch\'');
});

it('can checkout existing branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['checkout', 'main'])
        ->once()
        ->andReturn('Switched to branch \'main\'');

    $result = $this->gitService->checkoutBranch('main');

    expect($result)->toBe('Switched to branch \'main\'');
});

it('can merge branch with unrelated histories', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['merge', 'upstream/main', '--allow-unrelated-histories'])
        ->once()
        ->andReturn('Merge made by the \'recursive\' strategy.');

    $result = $this->gitService->merge('upstream/main', true);

    expect($result)->toBe('Merge made by the \'recursive\' strategy.');
});

it('can merge branch without unrelated histories', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['merge', 'upstream/main'])
        ->once()
        ->andReturn('Merge made by the \'recursive\' strategy.');

    $result = $this->gitService->merge('upstream/main', false);

    expect($result)->toBe('Merge made by the \'recursive\' strategy.');
});

it('can rebase branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rebase', 'upstream/main'])
        ->once()
        ->andReturn('Successfully rebased and updated refs/heads/main.');

    $result = $this->gitService->rebase('upstream/main');

    expect($result)->toBe('Successfully rebased and updated refs/heads/main.');
});

it('can check for uncommitted changes', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['status', '--porcelain'], false)
        ->once()
        ->andReturn('M  modified-file.php');

    $result = $this->gitService->hasUncommittedChanges();

    expect($result)->toBeTrue();
});

it('returns false when no uncommitted changes', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['status', '--porcelain'], false)
        ->once()
        ->andReturn('');

    $result = $this->gitService->hasUncommittedChanges();

    expect($result)->toBeFalse();
});

it('can commit changes', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['commit', '-m', 'feat: add new feature'])
        ->once()
        ->andReturn('[main abc123] feat: add new feature');

    $result = $this->gitService->commit('feat: add new feature');

    expect($result)->toBe('[main abc123] feat: add new feature');
});

it('can push to remote', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['push', 'origin', 'main'])
        ->once()
        ->andReturn('To https://github.com/test/repo.git');

    $result = $this->gitService->push('origin', 'main');

    expect($result)->toBe('To https://github.com/test/repo.git');
});

it('can get latest commit hash', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'HEAD'])
        ->once()
        ->andReturn('abc123def456');

    $result = $this->gitService->getLatestCommitHash();

    expect($result)->toBe('abc123def456');
});

it('can get remote commit hash', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'upstream/main'])
        ->once()
        ->andReturn('def456ghi789');

    $result = $this->gitService->getRemoteCommitHash('upstream', 'main');

    expect($result)->toBe('def456ghi789');
});

it('can check if branches are up to date', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'HEAD'])
        ->once()
        ->andReturn('abc123def456');

    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'upstream/main'])
        ->once()
        ->andReturn('abc123def456');

    $result = $this->gitService->isUpToDate('upstream', 'main');

    expect($result)->toBeTrue();
});

it('returns false when branches are not up to date', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'HEAD'])
        ->once()
        ->andReturn('abc123def456');

    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'upstream/main'])
        ->once()
        ->andReturn('def456ghi789');

    $result = $this->gitService->isUpToDate('upstream', 'main');

    expect($result)->toBeFalse();
});
