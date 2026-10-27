<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Upstream Repository Settings
    |--------------------------------------------------------------------------
    |
    | Configure your upstream repository settings. The package supports
    | all Laravel starter kits and custom repositories.
    |
    | Official Laravel Starter Kits:
    | - Vue: https://github.com/laravel/vue-starter-kit.git
    | - React: https://github.com/laravel/react-starter-kit.git
    | - Livewire: https://github.com/laravel/livewire-starter-kit.git
    |
    */
    'upstream_url' => env('UPSTREAM_URL', 'https://github.com/laravel/vue-starter-kit.git'),
    'upstream_branch' => env('UPSTREAM_BRANCH', 'main'),
    'local_branch' => env('UPSTREAM_LOCAL_BRANCH', 'main'),

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

    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    |
    | Set a default preset to use when no specific configuration is provided.
    | Options: vue, react, livewire, custom, or null (no default)
    |
    */
    'default_preset' => env('UPSTREAM_DEFAULT_PRESET', env('UPSTREAM_PRESET', 'vue')),

    /*
    |--------------------------------------------------------------------------
    | Auto-detect Starter Kit
    |--------------------------------------------------------------------------
    |
    | Automatically detect which starter kit you're using based on
    | package.json dependencies and set appropriate defaults.
    |
    */
    'auto_detect' => env('UPSTREAM_AUTO_DETECT', true),

    /*
    |--------------------------------------------------------------------------
    | Pull Request Workflow
    |--------------------------------------------------------------------------
    |
    | Create pull requests instead of direct merges for safer updates.
    | This creates a new branch with updates and opens a PR to the target branch.
    |
    */
    'create_pr' => env('UPSTREAM_CREATE_PR', false),
    'pr_branch_prefix' => env('UPSTREAM_PR_BRANCH_PREFIX', 'laravelplus-updater-'),
    'pr_title' => env('UPSTREAM_PR_TITLE', 'chore: sync from upstream'),
    'pr_body' => env('UPSTREAM_PR_BODY', 'Automated upstream sync from {upstream_url}'),
    'github_token' => env('GITHUB_TOKEN'),
    'github_owner' => env('GITHUB_OWNER'),
    'github_repo' => env('GITHUB_REPO'),

    /*
    |--------------------------------------------------------------------------
    | Starter Kit Presets
    |--------------------------------------------------------------------------
    |
    | Pre-configured settings for official Laravel starter kits.
    | Use these presets by setting UPSTREAM_DEFAULT_PRESET in your .env file.
    |
    */
    'presets' => [
        'vue' => [
            'upstream_url' => 'https://github.com/laravel/vue-starter-kit.git',
            'upstream_branch' => 'main',
            'post_update' => [
                'composer install --no-interaction --prefer-dist --optimize-autoloader',
                'npm ci',
                'npm run build',
                'php artisan migrate --force',
            ],
        ],
        'react' => [
            'upstream_url' => 'https://github.com/laravel/react-starter-kit.git',
            'upstream_branch' => 'main',
            'post_update' => [
                'composer install --no-interaction --prefer-dist --optimize-autoloader',
                'npm ci',
                'npm run build',
                'php artisan migrate --force',
            ],
        ],
        'livewire' => [
            'upstream_url' => 'https://github.com/laravel/livewire-starter-kit.git',
            'upstream_branch' => 'main',
            'post_update' => [
                'composer install --no-interaction --prefer-dist --optimize-autoloader',
                'npm ci',
                'npm run build',
                'php artisan migrate --force',
            ],
        ],
    ],

];
