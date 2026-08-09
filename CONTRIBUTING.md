# Contributing to ZeroBoiler Enums

Thank you for your interest in contributing! This document provides guidelines for contributing to this package.

## Development Setup

```bash
# Clone the repository
git clone git@github.com:zeroboiler/enums.git
cd enums

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis (PHPStan level 9)
composer analyse

# Run code style fixer
composer lint

# Run all CI checks
composer ci
```

## Code Standards

- **PHP 8.5+** — All code must target PHP 8.5 or later
- **Strict types** — Every PHP file must have `declare(strict_types=1)`
- **PHPStan Level 9** — All code must pass PHPStan level 9 analysis with zero errors
- **Final classes** — All service classes, attributes, and resolvers must be `final`
- **Readonly properties** — All attribute constructor parameters must be `readonly`
- **`#[Override]`** — Applied to all interface/parent method implementations
- **Docblocks** — All public methods, classes, and properties must have docblocks
- **No `mixed` types** — All parameters and return types must be explicitly typed

## Pull Request Process

1. Create a feature branch from `main` (`feat/your-feature`)
2. Write/update tests for your changes
3. Ensure all CI checks pass (`composer ci`)
4. Submit a PR with a clear description of the change
5. PRs require at least one approval before merge

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add tryFromLabel reverse lookup
fix: resolve cache invalidation in Octane
refactor: simplify EnumColor resolution
docs: update README with pure enum example
test: add edge case tests for int-backed enums
```

## Testing

- Use [Pest](https://pestphp.com/) for all tests
- Place fixtures in `tests/Fixtures/`
- Tests must cover: happy path, edge cases, error paths, type safety
- Generated test command output should be valid Pest syntax

## License

By contributing, you agree that your contributions will be licensed under the proprietary license of this package.
