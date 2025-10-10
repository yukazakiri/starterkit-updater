<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Upstream Repository Settings
    |--------------------------------------------------------------------------
    */
    'upstream_url'    => env('UPSTREAM_URL', 'https://github.com/laravel/vue-starter-kit.git'),
    'upstream_branch' => env('UPSTREAM_BRANCH', 'main'),
    'local_branch'    => env('UPSTREAM_LOCAL_BRANCH', 'main'),

    /*
    |--------------------------------------------------------------------------
    | Strategy: merge | rebase
    |--------------------------------------------------------------------------
    */
    'strategy' => env('UPSTREAM_STRATEGY', 'merge'),

    /*
    |--------------------------------------------------------------------------
    | Git Options
    |--------------------------------------------------------------------------
    */
    'git_binary' => env('GIT_BINARY', 'git'),
    'working_dir' => base_path(),
    'commit_message' => env('UPSTREAM_COMMIT_MESSAGE', 'chore(upstream): sync from upstream'),
    'allow_unrelated_histories' => (bool) env('UPSTREAM_ALLOW_UNRELATED', true),

    /*
    |--------------------------------------------------------------------------
    | Hooks (arrays of shell commands executed in project root)
    |--------------------------------------------------------------------------
    */
    'pre_update' => [
        // 'php artisan down',
        // 'php artisan cache:clear',
    ],

    'post_update' => [
        // 'composer install --no-interaction --prefer-dist --optimize-autoloader',
        // 'npm ci',
        // 'npm run build',
        // 'php artisan migrate --force',
        // 'php artisan up',
    ],
];
