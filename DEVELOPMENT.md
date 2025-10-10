# LaravelPlus Updater - Development Guide

## 🚀 Getting Started

This package follows modern PHP 8.4 development practices with strict typing, proper abstractions, and comprehensive testing.

## 📁 Package Structure

```
src/
├── Contracts/           # Interfaces and contracts
├── Console/Commands/    # Artisan commands
├── Exceptions/          # Custom exceptions
├── Services/           # Business logic services
├── Support/            # Utility classes
├── Traits/             # Reusable traits
└── Config/             # Configuration files
```

## 🛠️ Development Commands

### Code Formatting
```bash
# Format all code
composer run format

# Check formatting without fixing
composer run format:test
```

### Testing
```bash
# Run all tests
composer run test

# Run tests with coverage
composer run test:coverage
```

## 🏗️ Architecture

### Design Patterns Used
- **Strategy Pattern** - Git merge/rebase strategies
- **Factory Pattern** - Exception creation
- **Template Method Pattern** - Abstract command structure
- **Dependency Injection** - Service injection

### Key Components

#### Contracts (Interfaces)
- `VersionControlInterface` - Git operations contract
- `UpstreamCommandInterface` - Command contract

#### Services
- `GitService` - High-level Git operations
- `VersionControl` - Low-level Git command wrapper

#### Commands
- `AbstractUpstreamCommand` - Base command with common functionality
- All commands extend the abstract base class

#### Traits
- `HasPrompts` - Laravel Prompts integration with fallback

## 🎯 PHP 8.4 Features Used

- **Strict Types** - `declare(strict_types=1)` everywhere
- **Readonly Properties** - Immutable service classes
- **Constructor Property Promotion** - Clean constructors
- **Proper Return Types** - All methods have explicit return types
- **Generic Type Hints** - `array<string, mixed>` etc.

## 📝 Code Standards

- **PSR-12** - PHP coding standard
- **Laravel Pint** - Code formatting
- **PHPUnit 11** - Testing framework
- **Orchestra Testbench** - Laravel package testing

## 🔧 Adding New Features

1. **Create Interface** - Define contract in `Contracts/`
2. **Implement Service** - Business logic in `Services/`
3. **Add Command** - Extend `AbstractUpstreamCommand`
4. **Write Tests** - Comprehensive test coverage
5. **Format Code** - Run `composer run format`

## 🧪 Testing Strategy

- **Unit Tests** - Individual class testing
- **Feature Tests** - Command integration testing
- **Mocking** - Service dependencies
- **Coverage** - Aim for 100% code coverage

## 📦 Package Development

### Local Development
```bash
# Install dependencies
composer install

# Run formatting
composer run format

# Run tests
composer run test
```

### Publishing
```bash
# Tag version
git tag v1.0.0

# Push to GitHub
git push origin v1.0.0
```

## 🎨 Code Quality

- **DRY Principle** - No code duplication
- **SOLID Principles** - Clean architecture
- **Type Safety** - Full type hints
- **Error Handling** - Custom exceptions
- **Documentation** - PHPDoc blocks

## 🔍 Debugging

- Use `--test` flag for configuration validation
- Check logs in `storage/logs/`
- Use `--dry-run` for safe testing
- Enable verbose output with `-v`

## 📚 Resources

- [Laravel Package Development](https://laravel.com/docs/packages)
- [PHP 8.4 Features](https://php.net/releases/8.4/en.php)
- [Laravel Pint](https://laravel.com/docs/pint)
- [PHPUnit 11](https://phpunit.de/)
