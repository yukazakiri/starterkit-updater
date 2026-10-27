# LaravelPlus Updater

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravelplus/laravel-updater.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![Total Downloads](https://img.shields.io/packagist/dt/laravelplus/laravel-updater.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![License](https://img.shields.io/packagist/l/laravelplus/laravel-updater.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg?style=flat-square)](https://packagist.org/packages/laravelplus/laravel-updater)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-red.svg?style=flat-square)](https://laravel.com)
[![GitHub Stars](https://img.shields.io/github/stars/yukazakiri/starterkit-updater.svg?style=flat-square)](https://github.com/yukazakiri/starterkit-updater)
[![GitHub Issues](https://img.shields.io/github/issues/yukazakiri/starterkit-updater.svg?style=flat-square)](https://github.com/yukazakiri/starterkit-updater/issues)
[![Tests](https://github.com/yukazakiri/starterkit-updater/actions/workflows/tests.yml/badge.svg)](https://github.com/yukazakiri/starterkit-updater/actions/workflows/tests.yml)
[![Code Quality](https://img.shields.io/badge/code%20quality-PHPStan%20%7C%20Rector%20%7C%20Pint-blue.svg?style=flat-square)](https://github.com/LaravelPlus/laravel-updater)


A professional Laravel package for syncing with upstream repositories (GitHub, GitLab, Bitbucket, etc.) with comprehensive testing, modern architecture, and enterprise-grade features. **This package supports Laravel 12 and 13** and provides a safe, PR-first workflow for keeping your Laravel starter kit projects up to date!


## 🚀 Simple 3-Step Workflow

```bash
# 1. Setup (one-time)
php artisan laravelplus:setup

# 2. Check for updates
php artisan laravelplus:check

# 3. Apply updates (creates PR by default)
php artisan laravelplus:update
```

That's it! No complex commands to remember - just 3 simple steps to keep your Laravel starter kit up to date.

## ✅ Supported Laravel Versions

> **Important**: This package requires **Laravel 12 or 13** and PHP 8.4+. It will not work with older Laravel versions.

### Why Laravel 12/13?
- **Modern Architecture** - Built for the streamlined Laravel 11+ application structure
- **Enhanced Security** - Leverages current Laravel security improvements
- **Better Performance** - Optimized for current Laravel releases
- **Future-Proof** - Tested against every supported Laravel release

## 🚀 LaravelPlus Community

Welcome to the **LaravelPlus** community! We're building tools that make Laravel development more enjoyable and productive. Join our community and help shape the future of Laravel tooling.

- 🌟 **Star us on GitHub** - Help us grow the community
- 🐛 **Report issues** - Help us improve the tools
- 💡 **Suggest features** - Share your ideas
- 🤝 **Contribute** - Make Laravel better for everyone

## ✨ Features

### 🎯 **Simple & Powerful**
- **3 Essential Commands** - `setup`, `check`, `update` - that's it!
- **PR-First Workflow** - Always creates pull requests by default for safety
- **Smart Configuration** - Auto-detects your setup and guides you through configuration

### 🔄 **Core Functionality**
- **Universal Git Support** - GitHub, GitLab, Bitbucket, Azure DevOps, self-hosted
- **Multiple Strategies** - Support for both merge and rebase strategies
- **Smart Branch Management** - Automatic branch detection and creation
- **Safe Operations** - Dry-run mode and comprehensive error handling

### 🧪 **Testing & Quality**
- **Comprehensive Test Suite** - Unit and feature tests covering all functionality
- **Built-in Testing** - Validate configuration and connectivity before syncing
- **Code Quality Tools** - PHPStan, Rector, and Laravel Pint integration
- **Modern Testing** - Pest PHP with Laravel Testbench

### 🎯 **Starter Kit Support**
- **Pre-configured Presets** - Vue, React, Livewire starter kits
- **Auto-detection** - Automatically detect and configure your starter kit
- **Custom Repositories** - Support for any custom Laravel starter kit
- **Easy Setup** - Interactive setup command with beautiful prompts

### 🔀 **Advanced Workflows**
- **PR-First Workflow** - **Creates PRs by default** for safer updates
- **GitHub Integration** - Automatic PR creation with GitHub API
- **Direct Application** - Optional direct merge with `--direct` flag when needed
- **Pre/Post Hooks** - Run custom commands before and after sync
- **Flexible Configuration** - Environment variables and command-line overrides

### 🏗️ **Modern Architecture**
- **PHP 8.4 Features** - Strict types, readonly properties, constructor promotion
- **Interface-based Design** - Clean contracts and abstractions
- **Service Layer** - Separated business logic from commands
- **Exception Handling** - Custom exceptions with detailed error messages
- **Laravel Prompts** - Beautiful command-line interfaces with fallbacks

### 🚀 **Production Ready**
- **Timeout Protection** - Prevents hanging operations
- **Memory Management** - Optimized for large repositories
- **Error Recovery** - Graceful handling of network and Git issues
- **Logging & Debugging** - Comprehensive logging for troubleshooting

## Installation

> **⚠️ Laravel 12/13**: This package requires Laravel 12 or 13 and PHP 8.4+.

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

### Branch Configuration Explained

The package uses two important branch settings:

- **`UPSTREAM_BRANCH`**: The branch in the upstream repository to pull changes from (e.g., `main`, `master`, `develop`)
- **`UPSTREAM_LOCAL_BRANCH`**: Your main project branch where PRs will be created against (e.g., `main`, `master`, `develop`)

**Example scenarios:**
- **Modern Project**: `UPSTREAM_BRANCH=main` (upstream repo) → `UPSTREAM_LOCAL_BRANCH=main` (your repo)
- **Legacy Project**: `UPSTREAM_BRANCH=main` (upstream repo) → `UPSTREAM_LOCAL_BRANCH=master` (your repo)
- **Development Workflow**: `UPSTREAM_BRANCH=main` (upstream repo) → `UPSTREAM_LOCAL_BRANCH=dev` (your repo)
- **Feature Branch Workflow**: `UPSTREAM_BRANCH=develop` (upstream repo) → `UPSTREAM_LOCAL_BRANCH=develop` (your repo)

### Common Branch Naming Conventions

Most projects use these branch naming patterns:

- **`dev`** - Development branch (most common in many projects)
- **`master`** - Traditional main branch (common in older projects)
- **`main`** - Modern main branch (newer projects, GitHub default)
- **`develop`** - GitFlow development branch
- **`staging`** - Staging environment branch

**Setup defaults to `dev`** as it's the most common development branch convention.

### Publish Configuration

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
# Upstream repository configuration
UPSTREAM_URL=https://github.com/your-upstream/repo.git
UPSTREAM_BRANCH=main                    # Branch in upstream repo to pull from
UPSTREAM_LOCAL_BRANCH=dev               # Your main project branch (PRs created against this)
UPSTREAM_STRATEGY=merge
UPSTREAM_COMMIT_MESSAGE="chore(upstream): sync from upstream"
UPSTREAM_ALLOW_UNRELATED=true

# Preset configuration
UPSTREAM_DEFAULT_PRESET=vue
UPSTREAM_AUTO_DETECT=true

# PR configuration
UPSTREAM_CREATE_PR=false
UPSTREAM_PR_BRANCH_PREFIX=upstream-sync-
UPSTREAM_PR_TITLE="chore: sync from upstream"
UPSTREAM_PR_BODY="Automated upstream sync from {upstream_url}"

# GitHub integration
GITHUB_TOKEN=your_github_token
GITHUB_OWNER=your_username
GITHUB_REPO=your_repo

# Git configuration
GIT_BINARY=git
```

## Usage

### Recommended Workflow

The recommended workflow is to check for updates first, then upgrade. **The package creates PRs by default for safer updates:**

```bash
# 1. Check if updates are available
php artisan laravelplus:check

# 2. If updates are available, upgrade your project (creates PR by default)
php artisan laravelplus:update

# 3. For direct application (bypasses PR creation)
php artisan laravelplus:update --direct
```

### Quick Start Examples

#### Vue Starter Kit
```bash
# 1. Setup for Vue starter kit (one-time)
php artisan laravelplus:setup --preset=vue

# 2. Check for updates
php artisan laravelplus:check

# 3. Apply updates (creates PR by default)
php artisan laravelplus:update
```

#### React Starter Kit
```bash
# 1. Setup for React starter kit (one-time)
php artisan laravelplus:setup --preset=react

# 2. Check for updates
php artisan laravelplus:check

# 3. Apply updates (creates PR by default)
php artisan laravelplus:update
```

#### Livewire Starter Kit
```bash
# 1. Setup for Livewire starter kit (one-time)
php artisan laravelplus:setup --preset=livewire

# 2. Check for updates
php artisan laravelplus:check

# 3. Apply updates (creates PR by default)
php artisan laravelplus:update
```

#### Custom Repository
```bash
# 1. Setup for custom repository (one-time)
php artisan laravelplus:setup --preset=custom --url=https://github.com/your-org/your-repo.git

# 2. Check for updates
php artisan laravelplus:check

# 3. Apply updates (creates PR by default)
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

**This package creates pull requests by default** for safer updates in production environments. This ensures all upstream changes go through proper review:

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

# Create a PR with upstream updates (default behavior)
php artisan laravelplus:update

# Test the configuration before creating the PR
php artisan laravelplus:update --test

# Create PR with custom branch name
php artisan laravelplus:update --pr-branch=update-vue-starter-kit

# Create PR with custom title and body
php artisan laravelplus:update --pr-title="Update from Vue starter kit" --pr-body="Sync latest changes from upstream"

# For direct application (bypasses PR creation)
php artisan laravelplus:update --direct
```

### PR Workflow Benefits

- **🔒 Safer Updates** - Review changes before merging
- **📝 Better Documentation** - PR descriptions and commit history
- **👥 Team Collaboration** - Multiple reviewers can approve
- **🔄 Easy Rollback** - Simple to revert if issues arise
- **📊 Change Tracking** - Clear history of what changed and when

### Advanced Usage

#### Apply Changes Directly

```bash
php artisan laravelplus:update --direct
```

#### Test Before Updating

```bash
php artisan laravelplus:update --test
```

#### Dry Run (See What Would Happen)

```bash
php artisan laravelplus:update --dry-run
```

#### Use Rebase Strategy

```bash
php artisan laravelplus:update --strategy=rebase
```

#### Override Upstream URL

```bash
php artisan laravelplus:update --upstream=https://github.com/other/repo.git
```

#### Skip Hooks

```bash
php artisan laravelplus:update --no-pre --no-post
```

#### Custom Remote Name

```bash
php artisan laravelplus:update --remote-name=upstream
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

> **Note:** `post_update` hooks are skipped when creating a pull request, because they would run against unmerged code (for example, migrations). They run with `laravelplus:update --direct`, after the changes have been merged locally. Preset hooks are applied automatically from `config/upstream.php`.

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

### `laravelplus:setup`
Interactive setup wizard for configuring the package. Run this once to get started.

### `laravelplus:check`
Check if there are updates available from upstream repository. Shows detailed information about available updates.

### `laravelplus:update`
Upgrade your project with the latest changes from upstream. **Creates PRs by default** (recommended and safest).

### `laravelplus:update --direct`
Apply changes directly without creating a pull request (use with caution).

## Command Options

| Option | Description |
|--------|-------------|
| `--direct` | Apply changes directly without creating a pull request |
| `--dry-run` | Show what would happen without executing |
| `--test` | Test configuration and connectivity before sync |
| `--no-pre` | Skip pre_update hooks |
| `--no-post` | Skip post_update hooks |
| `--strategy=` | Override strategy (merge\|rebase) |
| `--upstream=` | Override upstream URL |
| `--branch=` | Override upstream branch |
| `--local=` | Override local branch |
| `--remote-name=` | Remote name to use (default: upstream) |
| `--pr-branch=` | Override PR branch name |
| `--pr-title=` | Override PR title |
| `--pr-body=` | Override PR body |
| `--force-push` | Force push the PR branch with `--force-with-lease` |

## 🧪 Testing

This package includes a comprehensive test suite covering all functionality:

### Test Coverage
- **Unit Tests** - Core components, services, and utilities
- **Feature Tests** - Command integration and Laravel functionality
- **Integration Tests** - Service provider and configuration
- **Exception Tests** - Error handling and edge cases

### Running Tests
```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit
composer test:lint
composer test:types

# Run with coverage
vendor/bin/pest --coverage
```

### Code Quality
The package maintains high code quality standards with:
- **PHPStan** - Static analysis (Level 8) with strict type checking
- **Rector** - Code modernization and PHP 8.4 feature adoption
- **Laravel Pint** - Code style enforcement with Laravel standards
- **Pest PHP** - Modern testing framework with Laravel integration

### Architecture Highlights
- **Strict Types** - `declare(strict_types=1)` throughout the codebase
- **Interface-based Design** - Clean contracts for all major components
- **Service Layer** - Separated business logic from command classes
- **Custom Exceptions** - Detailed error handling with specific exception types
- **Trait-based Reusability** - Common functionality extracted into traits
- **Constructor Property Promotion** - Modern PHP 8.4 syntax
- **Readonly Properties** - Immutable data structures where appropriate

## 🛠️ Development

### Prerequisites
- PHP 8.4+
- Composer
- Git

### Development Setup
```bash
# Clone the repository
git clone https://github.com/yukazakiri/starterkit-updater.git
cd starterkit-updater

# Install dependencies
composer install

# Run tests
composer test

# Check code quality
composer test:lint
composer test:types
```

### Contributing
We welcome contributions! Please see our [Development Guide](DEVELOPMENT.md) for detailed information on:
- Code style and standards
- Testing requirements
- Pull request process
- Issue reporting

## Requirements

- **PHP 8.4+** - Latest PHP features and performance
- **Laravel 12.x or 13.x** - Older Laravel versions are not supported
- **Git** - Installed and accessible in PATH
- **Valid Git Repository** - Working directory must be a Git repository
- **GitHub Token** - Required for automatic PR creation (optional for direct updates)

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 📊 Package Statistics

- **Comprehensive Test Suite** - Unit and feature coverage
- **Modern Architecture** - PHP 8.4+ with Laravel 12/13 support
- **PR-First Workflow** - Creates PRs by default for safer updates
- **Code Quality** - PHPStan Level 8 + Rector + Pint
- **Zero Dependencies** - Only Laravel framework dependencies
- **Universal Support** - Works with any Git hosting service

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

### Ways to Contribute
- 🐛 **Report Bugs** - Help us identify and fix issues
- 💡 **Suggest Features** - Share your ideas for improvements
- 📝 **Improve Documentation** - Help others understand the package
- 🧪 **Add Tests** - Increase test coverage and reliability
- 🔧 **Fix Issues** - Submit pull requests for bug fixes

### Development Process
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📈 Roadmap

### Upcoming Features
- 🔄 **GitLab Integration** - Direct GitLab API support
- 📊 **Analytics Dashboard** - Track sync history and statistics
- 🔔 **Notifications** - Slack/Discord integration for sync events
- 🎨 **Custom Themes** - Beautiful command-line interfaces
- 🔐 **Enhanced Security** - GPG signing and verification

## 🏆 Credits

- **[LaravelPlus](https://github.com/laravelplus)** - The LaravelPlus community
- **[All Contributors](../../contributors)** - Thank you to everyone who contributes
- **[Laravel Framework](https://laravel.com)** - Built on the amazing Laravel framework

## 📞 Support

### Getting Help
- 📖 **Documentation** - Check this README and [DEVELOPMENT.md](DEVELOPMENT.md)
- 🐛 **Bug Reports** - [GitHub Issues](https://github.com/yukazakiri/starterkit-updater/issues)
- 💬 **Discussions** - [GitHub Discussions](https://github.com/yukazakiri/starterkit-updater/discussions)
- ⭐ **Star the Project** - Show your support!

### Community
Join the LaravelPlus community and help us build amazing Laravel tools:
- 🌟 **Star us on GitHub** - Help us grow
- 🐦 **Follow us** - Stay updated on new features
- 💡 **Share feedback** - Help us improve

---

**Made with ❤️ by the LaravelPlus community**
