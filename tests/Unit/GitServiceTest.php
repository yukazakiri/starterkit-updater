<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;
use LaravelPlus\LaravelUpdater\Services\GitService;

beforeEach(function () {
    $this->versionControl = Mockery::mock(VersionControlInterface::class);
    $this->gitService = new GitService($this->versionControl);
});

afterEach(function () {
    Mockery::close();
});

it('adds a remote when it does not exist', function () {
    $this->versionControl->shouldReceive('hasRemote')
        ->with('upstream')
        ->once()
        ->andReturn(false);

    $this->versionControl->shouldReceive('run')
        ->with(['remote', 'add', 'upstream', 'https://github.com/test/repo.git'])
        ->once()
        ->andReturn('');

    expect($this->gitService->addRemoteIfNotExists('upstream', 'https://github.com/test/repo.git'))->toBeTrue();
});

it('does not add a remote that already exists', function () {
    $this->versionControl->shouldReceive('hasRemote')
        ->with('upstream')
        ->once()
        ->andReturn(true);

    expect($this->gitService->addRemoteIfNotExists('upstream', 'https://github.com/test/repo.git'))->toBeTrue();
});

it('fetches from a remote', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['fetch', 'upstream'])
        ->once()
        ->andReturn('From https://github.com/test/repo.git');

    expect($this->gitService->fetch('upstream'))->toBe('From https://github.com/test/repo.git');
});

it('returns the current branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--abbrev-ref', 'HEAD'])
        ->once()
        ->andReturn(" main \n");

    expect($this->gitService->getCurrentBranch())->toBe('main');
});

it('detects an existing branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--verify', 'refs/heads/main'])
        ->once()
        ->andReturn('abc123');

    expect($this->gitService->branchExists('main'))->toBeTrue();
});

it('detects a missing branch when rev-parse fails', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--verify', 'refs/heads/nonexistent'])
        ->once()
        ->andThrow(new RuntimeException('fatal: Needed a single revision'));

    expect($this->gitService->branchExists('nonexistent'))->toBeFalse();
});

it('creates and checks out a new branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['checkout', '-b', 'feature-branch'])
        ->once()
        ->andReturn("Switched to a new branch 'feature-branch'");

    expect($this->gitService->createAndCheckoutBranch('feature-branch'))
        ->toBe("Switched to a new branch 'feature-branch'");
});

it('checks out an existing branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['checkout', 'main'])
        ->once()
        ->andReturn("Switched to branch 'main'");

    expect($this->gitService->checkoutBranch('main'))->toBe("Switched to branch 'main'");
});

it('ensures a missing branch is created', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--verify', 'refs/heads/new-branch'])
        ->once()
        ->andThrow(new RuntimeException('not found'));

    $this->versionControl->shouldReceive('run')
        ->with(['checkout', '-b', 'new-branch'])
        ->once()
        ->andReturn('created');

    expect($this->gitService->ensureBranchExists('new-branch'))->toBe('created');
});

it('ensures an existing branch is checked out', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', '--verify', 'refs/heads/main'])
        ->once()
        ->andReturn('abc123');

    $this->versionControl->shouldReceive('run')
        ->with(['checkout', 'main'])
        ->once()
        ->andReturn('checked out');

    expect($this->gitService->ensureBranchExists('main'))->toBe('checked out');
});

it('merges with unrelated histories', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['merge', 'upstream/main', '--allow-unrelated-histories'])
        ->once()
        ->andReturn('merged');

    expect($this->gitService->merge('upstream/main', true))->toBe('merged');
});

it('merges without unrelated histories', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['merge', 'upstream/main'])
        ->once()
        ->andReturn('merged');

    expect($this->gitService->merge('upstream/main'))->toBe('merged');
});

it('rebases onto a branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rebase', 'upstream/main'])
        ->once()
        ->andReturn('rebased');

    expect($this->gitService->rebase('upstream/main'))->toBe('rebased');
});

it('detects uncommitted changes', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['status', '--porcelain'], false)
        ->once()
        ->andReturn('M  modified-file.php');

    expect($this->gitService->hasUncommittedChanges())->toBeTrue();
});

it('reports a clean working tree', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['status', '--porcelain'], false)
        ->once()
        ->andReturn('');

    expect($this->gitService->hasUncommittedChanges())->toBeFalse();
});

it('commits changes', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['commit', '-m', 'feat: add new feature'])
        ->once()
        ->andReturn('[main abc123] feat: add new feature');

    expect($this->gitService->commit('feat: add new feature'))->toBe('[main abc123] feat: add new feature');
});

it('pushes a branch', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['push', 'origin', 'main'])
        ->once()
        ->andReturn('pushed');

    expect($this->gitService->push('origin', 'main'))->toBe('pushed');
});

it('returns the latest commit hash', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'HEAD'])
        ->once()
        ->andReturn("abc123def456\n");

    expect($this->gitService->getLatestCommitHash())->toBe('abc123def456');
});

it('returns a remote commit hash', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'upstream/main'])
        ->once()
        ->andReturn('def456ghi789');

    expect($this->gitService->getRemoteCommitHash('upstream', 'main'))->toBe('def456ghi789');
});

it('detects when local and remote branches match', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'HEAD'])
        ->once()
        ->andReturn('abc123');

    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'upstream/main'])
        ->once()
        ->andReturn('abc123');

    expect($this->gitService->isUpToDate('upstream', 'main'))->toBeTrue();
});

it('detects when local and remote branches differ', function () {
    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'HEAD'])
        ->once()
        ->andReturn('abc123');

    $this->versionControl->shouldReceive('run')
        ->with(['rev-parse', 'upstream/main'])
        ->once()
        ->andReturn('def456');

    expect($this->gitService->isUpToDate('upstream', 'main'))->toBeFalse();
});
