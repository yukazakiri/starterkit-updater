<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Tests\Support\TestRepository;

it('reports when updates are available', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'version 1');
    $upstream->commit('first commit');

    $local = TestRepository::cloneFrom($upstream);
    updaterConfigureRepositories($local, $upstream);

    $upstream->write('README.md', 'version 2');
    $upstream->commit('second commit');

    $this->artisan('laravelplus:check')
        ->expectsOutputToContain('Updates available!')
        ->assertExitCode(0);
});

it('reports when the project is up to date', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'version 1');
    $upstream->commit('first commit');

    $local = TestRepository::cloneFrom($upstream);
    updaterConfigureRepositories($local, $upstream);

    $this->artisan('laravelplus:check')
        ->expectsOutputToContain('You are up to date!')
        ->assertExitCode(0);
});

it('respects a custom upstream branch instead of forcing main', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'main');
    $upstream->commit('main commit');

    $local = TestRepository::cloneFrom($upstream);

    $upstream->createBranch('develop');
    $upstream->write('DEVELOP.md', 'develop only');
    $upstream->commit('develop commit');
    $upstream->checkout('main');

    updaterConfigureRepositories($local, $upstream, [
        'upstream.upstream_branch' => 'develop',
    ]);

    $this->artisan('laravelplus:check')
        ->expectsOutputToContain('Updates available!')
        ->assertExitCode(0);
});

it('allows overriding the upstream branch from the command line', function () {
    $upstream = TestRepository::create();
    $upstream->write('README.md', 'main');
    $upstream->commit('main commit');

    $local = TestRepository::cloneFrom($upstream);

    $upstream->createBranch('develop');
    $upstream->write('DEVELOP.md', 'develop only');
    $upstream->commit('develop commit');
    $upstream->checkout('main');

    updaterConfigureRepositories($local, $upstream, [
        'upstream.upstream_branch' => 'main',
    ]);

    $this->artisan('laravelplus:check --branch=develop')
        ->expectsOutputToContain('Updates available!')
        ->assertExitCode(0);
});

it('fails cleanly when the upstream repository cannot be reached', function (string $upstreamUrl) {
    $local = TestRepository::create();
    $local->write('README.md', 'local');
    $local->commit();

    updaterConfigureRepositories($local, $local, [
        'upstream.upstream_url' => $upstreamUrl,
    ]);

    $this->artisan('laravelplus:check')
        ->expectsOutputToContain('Error checking for updates')
        ->assertExitCode(1);
})->with([
    'missing local path' => '/nonexistent/upstream/repository',
    'invalid URL' => 'not-a-valid-url',
]);
