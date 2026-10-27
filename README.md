# StarterKit Updater

[![Tests](https://github.com/yukazakiri/starterkit-updater/actions/workflows/tests.yml/badge.svg)](https://github.com/yukazakiri/starterkit-updater/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-red.svg)](https://laravel.com)
[![GitHub Stars](https://img.shields.io/github/stars/yukazakiri/starterkit-updater.svg)](https://github.com/yukazakiri/starterkit-updater)
[![GitHub Issues](https://img.shields.io/github/issues/yukazakiri/starterkit-updater.svg)](https://github.com/yukazakiri/starterkit-updater/issues)

A maintained fork of [LaravelPlus Updater](https://github.com/LaravelPlus/laravel-updater) for keeping Laravel starter-kit projects in sync with their upstream repository through a safe, PR-first workflow.

**Supports Laravel 12 and 13 on PHP 8.4+.**

> The fork uses the Composer package name `yukazakiri/starterkit-updater`. Its PHP namespace (`LaravelPlus\LaravelUpdater`) and Artisan command prefix (`laravelplus:`) remain compatible with the upstream package. Install only one of the two packages in a project.

## Requirements

- PHP 8.4 or newer
- Laravel 12.x or 13.x
- Composer 2
- Git available in `PATH`
- A Git repository with an `origin` remote
- Optional: a GitHub token for automatic pull-request creation

## Installation

Until the fork is submitted to Packagist, add its GitHub repository to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yukazakiri/starterkit-updater"
        }
    ],
    "require": {
        "yukazakiri/starterkit-updater": "dev-master"
    }
}
```

Then install it:

```bash
composer update yukazakiri/starterkit-updater
```

After the fork is submitted to Packagist, the VCS repository entry is no longer needed. Install the development branch with `composer require yukazakiri/starterkit-updater:dev-master`, or use `composer require yukazakiri/starterkit-updater` after a stable release has been tagged and indexed. To switch from the upstream package, remove `laravelplus/laravel-updater` before installing the fork.

Laravel package discovery registers the service provider automatically. If package discovery is disabled in your application, register `LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider` manually.

## Quick Start

```bash
# 1. Configure the upstream repository (one-time)
php artisan laravelplus:setup

# 2. Check whether updates are available
php artisan laravelplus:check

# 3. Create a pull request with the upstream changes (default)
php artisan laravelplus:update
```

`laravelplus:update` pushes a new branch and creates a GitHub pull request when a token is configured. Without a token, it pushes the branch and, when the GitHub repository can be determined, prints a compare URL so you can open the PR manually.

To apply the changes directly to your local branch instead, use `--direct`:

```bash
php artisan laravelplus:update --direct
```

## What This Fork Changes

- Supports **Laravel 12 and 13** with Composer constraints `^12.0|^13.0` and Testbench `^10.0|^11.0`.
- Tests every combination of **PHP 8.4/8.5** and **Laravel 12/13** in CI.
- Aborts cleanly on merge/rebase conflicts, restores the original branch, and never commits conflict markers.
- Never force-pushes by default; `--force-push` uses `--force-with-lease`.
- Merges preset hooks with configured hooks and runs them correctly.
- Skips `post_update` hooks in PR mode, where they would run against unmerged code, and runs them in `--direct` mode.
- Derives GitHub owner/repository from the `origin` remote when configuration is missing, so manual compare URLs are valid for GitHub remotes.
- Respects the configured upstream branch instead of forcing `main`.
- Fixes local branch detection used by `GitService::branchExists()`.
- Ships an MIT `LICENSE`, a CI workflow, and a PHPUnit configuration for parallel tests.
- Does not track `vendor/` or `composer.lock` in Git.

## Configuration

### Publish the Config File

```bash
php artisan vendor:publish \
    --provider="LaravelPlus\LaravelUpdater\LaravelUpdaterServiceProvider" \
    --tag=config
```

This creates `config/upstream.php`.

### Branch Settings

Two branch settings control the sync:

- **`UPSTREAM_BRANCH`** — the branch in the upstream repository to pull from, such as `main`, `master`, or `develop`.
- **`UPSTREAM_LOCAL_BRANCH`** — your project branch that receives the PR, such as `main`, `master`, or `develop`.

Examples:

| Scenario | `UPSTREAM_BRANCH` | `UPSTREAM_LOCAL_BRANCH` |
|----------|-------------------|--------------------------|
| Modern project | `main` | `main` |
| Legacy main branch | `main` | `master` |
| Development branch | `main` | `dev` |
| Development upstream | `develop` | `develop` |

During setup, the local branch defaults to the branch currently checked out, or `main` if it cannot be detected.

### Environment Variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `UPSTREAM_URL` | `https://github.com/laravel/vue-starter-kit.git` | Upstream Git repository |
| `UPSTREAM_BRANCH` | `main` | Branch to pull from upstream |
| `UPSTREAM_LOCAL_BRANCH` | `main` | Local branch that receives the PR |
| `UPSTREAM_STRATEGY` | `merge` | `merge` or `rebase` |
| `UPSTREAM_ALLOW_UNRELATED` | `true` | Allow merging unrelated histories |
| `UPSTREAM_DEFAULT_PRESET` | `vue` | Default starter-kit preset |
| `UPSTREAM_PR_BRANCH_PREFIX` | `laravelplus-updater-` | Prefix for generated PR branches |
| `UPSTREAM_PR_TITLE` | `chore: sync from upstream` | Default PR title |
| `UPSTREAM_PR_BODY` | `Automated upstream sync from {upstream_url}` | Default PR body |
| `GITHUB_TOKEN` | unset | Token used to create pull requests |
| `GITHUB_OWNER` | inferred from `origin` | GitHub owner or organization |
| `GITHUB_REPO` | inferred from `origin` | GitHub repository name |
| `GIT_BINARY` | `git` | Git executable |

`working_dir`, hooks, and presets are configured in `config/upstream.php`.

### GitHub Token

Automatic PR creation requires a GitHub token. A classic token needs the `repo` scope. A fine-grained token needs:

- **Pull requests:** Read and write
- **Contents:** Read and write

```env
GITHUB_TOKEN=ghp_your_token_here
GITHUB_OWNER=your_username
GITHUB_REPO=your_repository
```

If `GITHUB_OWNER` or `GITHUB_REPO` is omitted, the updater reads them from the `origin` remote. Automatic PR creation currently supports GitHub only; other Git hosts are synced through Git and the resulting branch can be opened as a merge request manually.

## Usage

### Set Up a Starter Kit

```bash
# Interactive setup
php artisan laravelplus:setup

# Official Laravel starter-kit presets
php artisan laravelplus:setup --preset=vue
php artisan laravelplus:setup --preset=react
php artisan laravelplus:setup --preset=livewire

# Any custom Git repository
php artisan laravelplus:setup --preset=custom --url=https://github.com/your-org/your-repo.git
```

Setup writes the relevant values to `.env`. Presets provide the upstream URL, upstream branch, and default `post_update` hooks. A custom upstream URL is not overwritten by a preset.

### Check for Updates

```bash
php artisan laravelplus:check
```

This shows the local and upstream commits, recent upstream changes, and a file-change summary.

### Check Options

| Option | Description |
|--------|-------------|
| `--upstream=` | Override the upstream URL |
| `--branch=` | Override the upstream branch |
| `--local=` | Override the local branch |
| `--remote-name=` | Remote name to use (default: `upstream`) |

### Create a Pull Request

```bash
# Default: push a branch and create a GitHub PR
php artisan laravelplus:update

# Validate configuration and connectivity first
php artisan laravelplus:update --test

# Custom PR branch, title, or body
php artisan laravelplus:update --pr-branch=update-vue-starter-kit
php artisan laravelplus:update --pr-title="Update from Vue starter kit"
php artisan laravelplus:update --pr-body="Sync the latest upstream changes"

# Preview without changing anything
php artisan laravelplus:update --dry-run
```

Before a real update (without `--dry-run`), the working tree must be clean. On a merge or rebase conflict, the command aborts the operation and restores your original branch; nothing is committed or pushed.

### Apply Changes Directly

```bash
php artisan laravelplus:update --direct
```

This merges or rebases the upstream changes into `UPSTREAM_LOCAL_BRANCH`, then runs `post_update` hooks. Review the result before pushing.

### Update Options

| Option | Description |
|--------|-------------|
| `--direct` | Apply changes directly without creating a pull request |
| `--dry-run` | Show what would happen without executing |
| `--test` | Validate configuration and connectivity before updating |
| `--no-pre` | Skip `pre_update` hooks |
| `--no-post` | Skip `post_update` hooks |
| `--strategy=` | Override the strategy (`merge` or `rebase`) |
| `--upstream=` | Override the upstream URL |
| `--branch=` | Override the upstream branch |
| `--local=` | Override the local target branch |
| `--remote-name=` | Remote name to use (default: `upstream`) |
| `--pr-branch=` | Override the PR branch name |
| `--pr-title=` | Override the PR title |
| `--pr-body=` | Override the PR body |
| `--force-push` | Push the PR branch with `--force-with-lease` |

### Setup Options

| Option | Description |
|--------|-------------|
| `--preset=` | `vue`, `react`, `livewire`, or `custom` |
| `--url=` | Upstream URL for a custom repository |
| `--upstream-branch=` | Branch to pull from upstream |
| `--local-branch=` | Local branch that receives updates |

## Hooks

Configure shell hooks in `config/upstream.php`:

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

`post_update` hooks are skipped in PR mode because the PR has not been merged yet and hooks such as migrations would run against unmerged code. They run with `laravelplus:update --direct`.

## Supported Starter Kits

The package includes presets for the official Laravel starter kits:

- **Vue** — [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit)
- **React** — [laravel/react-starter-kit](https://github.com/laravel/react-starter-kit)
- **Livewire** — [laravel/livewire-starter-kit](https://github.com/laravel/livewire-starter-kit)

Any other Git repository can be used through the `custom` preset.

## Supported Platforms

The sync itself works with any Git-based host:

- GitHub
- GitLab (including self-hosted)
- Bitbucket
- Azure DevOps
- Gitea and Gogs
- Any self-hosted Git server with HTTP or SSH access

Automatic pull-request creation through the GitHub API is GitHub-specific.

## Testing and Code Quality

```bash
# Run all quality gates (Rector, Pint, PHPStan, Pest with coverage)
composer test

# Run individual checks
composer test:refactor   # Rector dry run
composer test:lint       # Pint
composer test:types      # PHPStan level 8
composer test:unit       # Pest with coverage and parallel workers
```

The coverage step requires PCOV or Xdebug. CI enables PCOV. Without a local coverage driver, run the suite directly:

```bash
vendor/bin/pest --parallel
```

CI runs `composer test` for every combination of PHP 8.4/8.5 and Laravel 12/13. See [`.github/workflows/tests.yml`](.github/workflows/tests.yml).

## Development

```bash
git clone https://github.com/yukazakiri/starterkit-updater.git
cd starterkit-updater
composer install
composer test
```

See [DEVELOPMENT.md](DEVELOPMENT.md) for the project structure, architecture notes, and contribution workflow.

## Troubleshooting

### Composer cannot find the fork

Before Packagist submission, make sure the GitHub VCS repository is declared in `composer.json` and require `yukazakiri/starterkit-updater:dev-master` explicitly. After submission, allow time for Packagist to index the desired branch or release tag.

### "Already up to date" when changes are expected

Check that `UPSTREAM_BRANCH` points to the branch you actually want in the upstream repository and that `UPSTREAM_LOCAL_BRANCH` is your current target branch.

### The update refuses to run

The updater requires a clean working tree. Commit or stash local changes first so unrelated files are not included in the sync.

### No pull request is created

Set `GITHUB_TOKEN`, or let `GITHUB_OWNER`/`GITHUB_REPO` be inferred from `origin`. Without a token, the branch is still pushed and a compare URL is printed.

## Contributing

Issues and pull requests are welcome in this fork:

- [Report a bug or request a feature](https://github.com/yukazakiri/starterkit-updater/issues)
- Fork the repository, create a feature branch, add tests, run `composer test`, and open a pull request.

Please keep changes compatible with both Laravel 12 and 13.

## License

This package is open-source software licensed under the [MIT license](LICENSE).

## Credits

- Originally created by [LaravelPlus](https://github.com/LaravelPlus).
- This fork is maintained by [yukazakiri](https://github.com/yukazakiri).
- Built on the [Laravel framework](https://laravel.com).
