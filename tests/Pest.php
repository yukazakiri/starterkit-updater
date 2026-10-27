<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Tests\Support\TestRepository;
use LaravelPlus\LaravelUpdater\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

afterEach(function (): void {
    TestRepository::cleanup();
});

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * Point the package configuration at two local test repositories.
 *
 * @param  array<string, mixed>  $overrides
 */
function updaterConfigureRepositories(TestRepository $local, TestRepository $upstream, array $overrides = []): void
{
    config(array_merge([
        'upstream.upstream_url' => $upstream->path,
        'upstream.upstream_branch' => 'main',
        'upstream.local_branch' => 'main',
        'upstream.working_dir' => $local->path,
        'upstream.git_binary' => 'git',
        'upstream.strategy' => 'merge',
        'upstream.remote_name' => 'upstream',
        'upstream.default_preset' => null,
        'upstream.pre_update' => [],
        'upstream.post_update' => [],
        'upstream.github_token' => null,
        'upstream.github_owner' => null,
        'upstream.github_repo' => null,
    ], $overrides));
}
