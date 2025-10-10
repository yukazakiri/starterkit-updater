# Laravel Updater

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravelplus/laravel-updater.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![Total Downloads](https://img.shields.io/packagist/dt/laravelplus/laravel-updater.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![License](https://img.shields.io/packagist/l/laravelplus/laravel-updater.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![Laravel Version](https://img.shields.io/badge/Laravel-10.0%2B-red.svg?style=flat-square)](https://laravel.com)
[![GitHub Stars](https://img.shields.io/github/stars/LaravelPlus/laravel-updater.svg?style=flat-square)](https://github.com/LaravelPlus/laravel-updater)
[![GitHub Issues](https://img.shields.io/github/issues/LaravelPlus/laravel-updater.svg?style=flat-square)](https://github.com/LaravelPlus/laravel-updater/issues)

A Laravel package for syncing with upstream repositories (GitHub, GitLab, Bitbucket, etc.) with comprehensive testing and hook support. Perfect for keeping your Laravel starter kit projects up to date!

## Features

- 🔄 **Sync with any Git repository** - GitHub, GitLab, Bitbucket, Azure DevOps, self-hosted
- 🧪 **Built-in testing** - Validate configuration and connectivity before syncing
- 🔀 **Multiple strategies** - Support for both merge and rebase strategies
- 🪝 **Pre/Post hooks** - Run custom commands before and after sync
- 🛡️ **Safe operations** - Dry-run mode and comprehensive error handling
- ⚙️ **Flexible configuration** - Environment variables and command-line overrides
- 🚀 **Production ready** - Timeout protection and proper error handling
- 🎯 **Starter Kit Support** - Pre-configured for all official Laravel starter kits
- 🔧 **Easy Setup** - Interactive setup command for quick configuration
- 🔀 **Pull Request Workflow** - Create PRs instead of direct merges for safer updates
- 🤖 **GitHub Integration** - Automatic PR creation with GitHub API

## Installation

```bash
composer require laravelplus/laravel-updater
```

## Quick Setup

### For Laravel Starter Kits

Use the interactive setup command for quick configuration:

```bash
# Interactive setup
php artisan laravelplus:setup

# Or specify your starter kit directly
php artisan laravelplus:setup --preset=vue
php artisan laravelplus:setup --preset=react
php artisan laravelplus:setup --preset=livewire
```

### For Custom Repositories

```bash
php artisan laravelplus:setup --preset=custom --url=https://github.com/your-org/your-repo.git
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider" --tag="config"
```

This will create `config/upstream.php` with the following options:

```php
return [
    'upstream_url'    => env('UPSTREAM_URL', 'https://github.com/laravel/vue-starter-kit.git'),
    'upstream_branch' => env('UPSTREAM_BRANCH', 'main'),
    'local_branch'    => env('UPSTREAM_LOCAL_BRANCH', 'main'),
    'strategy'        => env('UPSTREAM_STRATEGY', 'merge'),
    'git_binary'      => env('GIT_BINARY', 'git'),
    'working_dir'     => base_path(),
    'commit_message'  => env('UPSTREAM_COMMIT_MESSAGE', 'chore(upstream): sync from upstream'),
    'allow_unrelated_histories' => (bool) env('UPSTREAM_ALLOW_UNRELATED', true),
    
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
```

## Environment Variables

Add these to your `.env` file:

```env
UPSTREAM_URL=https://github.com/your-upstream/repo.git
UPSTREAM_BRANCH=main
UPSTREAM_LOCAL_BRANCH=main
UPSTREAM_STRATEGY=merge
UPSTREAM_COMMIT_MESSAGE="chore(upstream): sync from upstream"
UPSTREAM_ALLOW_UNRELATED=true
UPSTREAM_DEFAULT_PRESET=vue
UPSTREAM_AUTO_DETECT=true
UPSTREAM_CREATE_PR=false
UPSTREAM_PR_BRANCH_PREFIX=upstream-sync-
UPSTREAM_PR_TITLE="chore: sync from upstream"
UPSTREAM_PR_BODY="Automated upstream sync from {upstream_url}"
GITHUB_TOKEN=your_github_token
GITHUB_OWNER=your_username
GITHUB_REPO=your_repo
GIT_BINARY=git
```

## Usage

### Recommended Workflow

The recommended workflow is to check for updates first, then upgrade:

```bash
# 1. Check if updates are available
php artisan laravelplus:check

# 2. If updates are available, upgrade your project
php artisan laravelplus:update

# 3. Or create a pull request for safer updates
php artisan laravelplus:update --pr
```

### Quick Start Examples

#### Vue Starter Kit
```bash
# Setup for Vue starter kit
php artisan laravelplus:setup --preset=vue

# Check for updates
php artisan laravelplus:check

# Upgrade with updates
php artisan laravelplus:update
```

#### React Starter Kit
```bash
# Setup for React starter kit
php artisan laravelplus:setup --preset=react

# Check for updates
php artisan laravelplus:check

# Upgrade with updates
php artisan laravelplus:update
```

#### Livewire Starter Kit
```bash
# Setup for Livewire starter kit
php artisan laravelplus:setup --preset=livewire

# Check for updates
php artisan laravelplus:check

# Upgrade with updates
php artisan laravelplus:update
```

#### Custom Repository
```bash
# Setup for custom repository
php artisan laravelplus:setup --preset=custom --url=https://github.com/your-org/your-repo.git

# Check for updates
php artisan laravelplus:check

# Upgrade with updates
php artisan laravelplus:update
```

### Using Default Presets

You can set a default preset in your `.env` file to automatically use a specific starter kit configuration:

```env
# Set Vue as the default preset
UPSTREAM_DEFAULT_PRESET=vue

# Or React
UPSTREAM_DEFAULT_PRESET=react

# Or Livewire
UPSTREAM_DEFAULT_PRESET=livewire
```

With a default preset configured, you can simply run:

```bash
# Check for updates (uses default preset automatically)
php artisan laravelplus:check

# Upgrade if updates are available
php artisan laravelplus:update
```

## Pull Request Workflow

For safer updates in production environments, you can create pull requests instead of direct merges:

### Setup GitHub Integration

1. **Create a GitHub Personal Access Token:**
   - Go to GitHub Settings → Developer settings → Personal access tokens
   - Generate a new token with `repo` permissions
   - Add it to your `.env` file

2. **Configure GitHub settings:**
```env
GITHUB_TOKEN=ghp_your_token_here
GITHUB_OWNER=your_username
GITHUB_REPO=your_repo_name
```

### Create Pull Requests

```bash
# Check for updates first
php artisan laravelplus:check

# Create a PR with upstream updates
php artisan laravelplus:update --pr

# Or use the direct PR command
php artisan laravelplus:pr --test

# Create PR with custom branch name
php artisan laravelplus:pr --pr-branch=update-vue-starter-kit

# Create PR with custom title and body
php artisan laravelplus:pr --pr-title="Update from Vue starter kit" --pr-body="Sync latest changes from upstream"
```

### PR Workflow Benefits

- **🔒 Safer Updates** - Review changes before merging
- **📝 Better Documentation** - PR descriptions and commit history
- **👥 Team Collaboration** - Multiple reviewers can approve
- **🔄 Easy Rollback** - Simple to revert if issues arise
- **📊 Change Tracking** - Clear history of what changed and when

### Advanced Usage

#### Basic Sync

```bash
php artisan laravelplus:sync
```

#### Test Before Sync

```bash
php artisan laravelplus:sync --test
```

#### Dry Run (See What Would Happen)

```bash
php artisan laravelplus:sync --dry-run
```

#### Use Rebase Strategy

```bash
php artisan laravelplus:sync --strategy=rebase
```

#### Override Upstream URL

```bash
php artisan laravelplus:sync --upstream=https://github.com/other/repo.git
```

#### Skip Hooks

```bash
php artisan laravelplus:sync --no-pre --no-post
```

#### Custom Remote Name

```bash
php artisan laravelplus:sync --remote-name=upstream
```

## Supported Laravel Starter Kits

This package comes with pre-configured support for all official Laravel starter kits:

### Official Laravel Starter Kits

- **Vue Starter Kit** - [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit)
  - Vue 3 + Inertia.js + TypeScript + Tailwind CSS
  - Perfect for modern SPA applications

- **React Starter Kit** - [laravel/react-starter-kit](https://github.com/laravel/react-starter-kit)
  - React 19 + Inertia.js + TypeScript + Tailwind CSS
  - Ideal for React-based applications

- **Livewire Starter Kit** - [laravel/livewire-starter-kit](https://github.com/laravel/livewire-starter-kit)
  - Livewire 3 + Laravel Volt + TypeScript + Tailwind CSS
  - Great for teams preferring PHP over JavaScript

### Custom Starter Kits

The package also supports any custom Laravel starter kit or repository:

- **Custom Repositories** - Any Git repository URL
- **Private Repositories** - GitHub, GitLab, Bitbucket private repos
- **Self-hosted Git** - Your own Git server

## Supported Platforms

This package works with any Git-based repository hosting service:

- **GitHub** - `https://github.com/user/repo.git`
- **GitLab** - `https://gitlab.com/user/repo.git`
- **Bitbucket** - `https://bitbucket.org/user/repo.git`
- **Azure DevOps** - `https://dev.azure.com/org/project/_git/repo`
- **Self-hosted Git** - Any Git server with HTTP/SSH access
- **GitLab Self-hosted** - `https://your-gitlab.com/user/repo.git`
- **Gitea/Gogs** - `https://your-gitea.com/user/repo.git`

## Testing

The package includes comprehensive testing functionality:

- ✅ **Git Binary** - Verifies git is installed and accessible
- ✅ **Working Directory** - Checks if the working directory exists and is writable
- ✅ **Configuration** - Validates all required config values and strategy
- ✅ **Remote Connectivity** - Tests if the upstream URL is reachable
- ✅ **Branch Validation** - Verifies the upstream branch exists
- ✅ **Local Repository** - Checks if current directory is a git repo

## Hooks

Configure pre and post-update hooks in your `config/upstream.php`:

```php
'pre_update' => [
    'php artisan down',
    'php artisan cache:clear',
],

'post_update' => [
    'composer install --no-interaction --prefer-dist --optimize-autoloader',
    'npm ci',
    'npm run build',
    'php artisan migrate --force',
    'php artisan up',
],
```

## Authentication

### HTTPS
Uses your stored credentials or personal access tokens.

### SSH (Recommended)
Uses your SSH keys for authentication. Set up SSH keys for your Git hosting service.

### Personal Access Tokens
For CI/CD environments, use personal access tokens in your repository URL:
```
https://username:token@github.com/user/repo.git
```

## Available Commands

### `laravelplus:check`
Check if there are updates available from upstream repository.

### `laravelplus:update`
Upgrade your project with the latest changes from upstream (recommended).

### `laravelplus:update --pr`
Create a pull request with upstream updates (safer for production).

### `laravelplus:sync`
Direct sync with upstream repository (merges directly to your branch).

### `laravelplus:pr`
Create a pull request with upstream updates (alternative to update --pr).

### `laravelplus:setup`
Interactive setup wizard for configuring the package.

## Command Options

| Option | Description |
|--------|-------------|
| `--dry-run` | Show what would happen without executing |
| `--test` | Test configuration and connectivity before sync |
| `--no-pre` | Skip pre_update hooks |
| `--no-post` | Skip post_update hooks |
| `--strategy=` | Override strategy (merge\|rebase) |
| `--upstream=` | Override upstream URL |
| `--branch=` | Override upstream branch |
| `--local=` | Override local branch |
| `--remote-name=` | Remote name to use (default: upstream) |
| `--pr-branch=` | Override PR branch name (laravelplus:pr only) |
| `--pr-title=` | Override PR title (laravelplus:pr only) |
| `--pr-body=` | Override PR body (laravelplus:pr only) |

## Requirements

- PHP 8.4+
- Laravel 10.0+
- Git installed and accessible
- Valid Git repository

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [LaravelPlus](https://github.com/laravelplus)
- [All Contributors](../../contributors)

## Support

If you discover any issues, please use the [issue tracker](https://github.com/laravelplus/laravel-updater/issues) on GitHub.
