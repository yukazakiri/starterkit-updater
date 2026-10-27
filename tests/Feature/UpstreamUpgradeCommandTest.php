<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Tests\Support\TestRepository;

it('prints a PR plan in dry-run mode', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'upstream');
    $upstream->commit();

    $local = TestRepository::cloneFrom($upstream);
    updaterConfigureRepositories($local, $upstream);

    $this->artisan('laravelplus:update --dry-run')
        ->expectsOutputToContain('Creating pull request with upstream updates')
        ->expectsOutputToContain('Dry run complete - no changes were made.')
        ->assertExitCode(0);
});

it('prints a direct-update plan in dry-run mode', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'upstream');
    $upstream->commit();

    $local = TestRepository::cloneFrom($upstream);
    updaterConfigureRepositories($local, $upstream);

    $this->artisan('laravelplus:update --direct --dry-run')
        ->expectsOutputToContain('Applying upstream updates directly')
        ->expectsOutputToContain('Dry run complete - no changes were made.')
        ->assertExitCode(0);
});

it('runs configuration tests before a dry run', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'upstream');
    $upstream->commit();

    $local = TestRepository::cloneFrom($upstream);
    updaterConfigureRepositories($local, $upstream);

    $this->artisan('laravelplus:update --test --dry-run')
        ->expectsOutputToContain('Running configuration tests')
        ->expectsOutputToContain('Tests passed: 6/6')
        ->assertExitCode(0);
});

it('rejects an invalid strategy', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'upstream');
    $upstream->commit();

    $local = TestRepository::cloneFrom($upstream);
    updaterConfigureRepositories($local, $upstream);

    $this->artisan('laravelplus:update --strategy=invalid --dry-run')
        ->expectsOutputToContain('Invalid strategy')
        ->assertExitCode(1);
});

it('applies updates directly with the direct option', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'version 1');
    $upstream->commit('first commit');

    $local = TestRepository::cloneFrom($upstream);
    updaterConfigureRepositories($local, $upstream);

    $upstream->write('README.md', 'version 2');
    $upstream->commit('second commit');

    $this->artisan('laravelplus:update --direct')
        ->expectsOutputToContain('Upstream changes applied directly')
        ->assertExitCode(0);

    expect(file_get_contents($local->path.'/README.md'))->toBe('version 2')
        ->and($local->git(['status', '--porcelain']))->toBe('');
});

it('aborts on merge conflicts without committing conflict markers', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'base');
    $upstream->commit('base commit');

    $local = TestRepository::cloneFrom($upstream);
    $local->write('README.md', 'local change');
    $local->commit('local commit');

    $upstream->write('README.md', 'upstream change');
    $upstream->commit('upstream commit');

    updaterConfigureRepositories($local, $upstream);

    $this->artisan('laravelplus:update --direct')
        ->expectsOutputToContain('merge failed')
        ->expectsOutputToContain('Aborted the failed merge')
        ->assertExitCode(1);

    $contents = (string) file_get_contents($local->path.'/README.md');

    expect($contents)->toBe('local change')
        ->and($contents)->not->toContain('<<<<<<<')
        ->and($local->git(['status', '--porcelain']))->toBe('')
        ->and($local->git(['rev-parse', '--abbrev-ref', 'HEAD']))->toBe('main');
});

it('skips post_update hooks in PR mode and returns to the original branch', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'version 1');
    $upstream->commit('first commit');

    $origin = TestRepository::createBare();
    $local = TestRepository::cloneFrom($upstream);
    $local->setRemote('origin', $origin->path);
    $local->push('main');

    $upstream->write('README.md', 'version 2');
    $upstream->commit('second commit');

    updaterConfigureRepositories($local, $upstream, [
        'upstream.post_update' => ['touch post-update-hook-ran'],
    ]);

    $this->artisan('laravelplus:update')
        ->expectsOutputToContain('Skipping post_update hooks in PR mode')
        ->assertExitCode(1);

    expect(file_exists($local->path.'/post-update-hook-ran'))->toBeFalse()
        ->and($local->git(['rev-parse', '--abbrev-ref', 'HEAD']))->toBe('main')
        ->and($origin->git(['branch', '--list', 'laravelplus-updater-*']))->toContain('laravelplus-updater-');
});
