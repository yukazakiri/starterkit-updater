# StarterKit Updater - Development Guide

This is the development guide for the [yukazakiri/starterkit-updater](https://github.com/yukazakiri/starterkit-updater) fork. The package follows modern PHP 8.4 practices with strict typing, explicit contracts, and a Pest test suite.

> The Composer package name (`laravelplus/laravel-updater`), PHP namespace, and `laravelplus:*` Artisan commands are kept for compatibility with the upstream package.

## Getting Started

### Requirements

- PHP 8.4+
- Composer 2
- Git
- PCOV or Xdebug for coverage runs (optional)

```bash
git clone https://github.com/yukazakiri/starterkit-updater.git
cd starterkit-updater
composer install
```

## Package Structure

```text
src/
├── Config/              # Publishable package configuration
├── Console/Commands/    # Artisan commands
├── Contracts/           # Interfaces and contracts
├── Exceptions/          # Custom exceptions
├── Services/            # Business logic services
├── Support/             # Git, GitHub, and version-control helpers
└── Traits/              # Reusable traits
```

## Development Commands

```bash
# Format code
composer lint

# Apply automated refactors
composer refactor

# Quality gates
composer test:refactor   # Rector dry run
composer test:lint       # Pint
composer test:types      # PHPStan level 8
composer test:unit       # Pest with coverage and parallel workers

# Run every quality gate
composer test
```

`composer test:unit` and `composer test` require PCOV or Xdebug for coverage. Without a coverage driver, run Pest directly:

```bash
vendor/bin/pest
vendor/bin/pest --parallel
vendor/bin/pest --filter=UpstreamUpgradeCommand
```

CI runs `composer test` on PHP 8.4/8.5 with Laravel 12/13.

## Architecture

### Design Patterns

- **Strategy pattern** — merge and rebase sync strategies
- **Factory pattern** — exception creation
- **Template method pattern** — shared command lifecycle in `AbstractUpstreamCommand`
- **Dependency injection** — services and version-control implementations

### Key Components

#### Contracts

- `VersionControlInterface` — Git operation contract
- `UpstreamCommandInterface` — command contract

#### Services and Support

- `GitService` — high-level Git operations
- `VersionControl` — low-level Git command wrapper
- `GitHubRepository` — parses GitHub remotes and builds compare URLs

#### Commands

- `AbstractUpstreamCommand` — shared preset, hook, and connectivity logic
- `UpstreamSetupCommand` — interactive configuration
- `UpstreamCheckCommand` — update detection and reporting
- `UpstreamUpgradeCommand` — PR-first and direct update flows

#### Traits

- `HasPrompts` — Laravel Prompts integration with console fallbacks

## PHP 8.4 Features

- `declare(strict_types=1)` throughout the codebase
- Readonly properties and readonly classes where appropriate
- Constructor property promotion
- Explicit parameter and return types
- Generic array shapes via PHPDoc (`array<string, mixed>`)

## Code Standards

- **PSR-12** coding style, enforced by Laravel Pint
- **PHPStan level 8** static analysis
- **Rector** for automated modernization
- **Pest 3** on PHPUnit 11
- **Orchestra Testbench** for Laravel package testing

## Testing Strategy

- **Unit tests** cover services, support classes, exceptions, configuration, and command helpers.
- **Feature tests** exercise the Artisan commands against temporary local Git repositories created by `Tests\Support\TestRepository`.
- **Parallel tests** use `phpunit.xml.dist`; keep it in sync with the Pest bootstrap.
- Add tests for every behavior change and bug fix. Run `composer test` before opening a pull request.

## Adding New Features

1. Define a contract in `Contracts/` when a new abstraction is needed.
2. Put Git or domain logic in `Services/` or `Support/`, not in the command.
3. Extend `AbstractUpstreamCommand` for new Artisan commands.
4. Add unit and feature coverage, including failure paths.
5. Run `composer test` and update the README when behavior or options change.

## Debugging

- Use `--dry-run` to preview an update without changing the repository.
- Use `--test` to validate configuration, Git connectivity, and branch settings.
- Add `-vvv` to an Artisan command for verbose console output.
- Feature tests use temporary repositories, so they do not touch your working copy.

## Resources

- [Laravel package development](https://laravel.com/docs/packages)
- [PHP 8.4 release notes](https://www.php.net/releases/8.4/en.php)
- [Laravel Pint](https://laravel.com/docs/pint)
- [PHPStan](https://phpstan.org/)
- [Pest](https://pestphp.com/)
