<?php

declare(strict_types=1);

use LaravelPlus\LaravelUpdater\Support\GitHubRepository;

it('parses https GitHub remotes', function () {
    expect(GitHubRepository::parse('https://github.com/laravel/vue-starter-kit.git'))
        ->toBe(['owner' => 'laravel', 'repo' => 'vue-starter-kit']);
});

it('parses scp-style GitHub remotes', function () {
    expect(GitHubRepository::parse('git@github.com:LaravelPlus/laravel-updater.git'))
        ->toBe(['owner' => 'LaravelPlus', 'repo' => 'laravel-updater']);
});

it('parses ssh URL GitHub remotes', function () {
    expect(GitHubRepository::parse('ssh://git@github.com/laravel/react-starter-kit'))
        ->toBe(['owner' => 'laravel', 'repo' => 'react-starter-kit']);
});

it('returns null for non-GitHub remotes', function () {
    expect(GitHubRepository::parse('https://gitlab.com/laravel/vue-starter-kit.git'))->toBeNull()
        ->and(GitHubRepository::parse(''))->toBeNull()
        ->and(GitHubRepository::parse(null))->toBeNull();
});

it('builds a compare URL', function () {
    expect(GitHubRepository::compareUrl(['owner' => 'laravel', 'repo' => 'vue-starter-kit'], 'main', 'update-branch'))
        ->toBe('https://github.com/laravel/vue-starter-kit/compare/main...update-branch');
});
