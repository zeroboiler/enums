# Contributing to ZeroBoiler Enums

Thank you for your interest in contributing! This document outlines the process.

## Code Standards

- **PHP 8.5 syntax** — use the latest language features
- **Strict types** — every file must have `declare(strict_types=1)`
- **PHPStan level 9** — zero errors, no baseline suppressions
- **Docblocks** — all public methods and properties documented
- **Typed properties** — no `mixed` types in source code
- **Final classes** — all attributes and services are `final`
- **Immutable** — enum metadata is cached in a singleton; no mutable state in traits

## Development Setup

```bash
composer install
```

## Quality Checks

All checks must pass before merging:

```bash
# Run the full test suite
composer test

# Run PHPStan analysis (level 9, no baseline)
composer analyse

# Run code style checker
composer lint

# Run all quality checks at once
composer ci
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Ensure all CI checks pass (`composer ci`)
4. Commit with [conventional commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`)
5. Push and open a Pull Request

## Architecture Overview

```
src/
├── Attributes/     — PHP 8 attribute classes (Label, Color, Icon, Description, etc.)
├── Concerns/       — HasEnumMetadata trait (all public API)
├── Casts/          — Eloquent cast for backed enums
├── Console/        — Artisan commands (inspect, test generation)
├── Exceptions/     — InvalidEnumException
├── Facades/         — Enum facade
├── Rules/           — EnumRule validation rule
├── Support/         — EnumMetadataResolver, EnumTestGenerator
├── EnumCache.php   — TTL-based singleton metadata cache
├── EnumManager.php — Runtime enum helper (facade-backed)
└── EnumsServiceProvider.php — Auto-discovery service provider
```

## Testing

Tests use [Pest](https://pestphp.com/). Fixtures live in `tests/Fixtures/`.

When adding new enum features:
- Add a fixture enum in `tests/Fixtures/`
- Add tests covering the new behavior
- Ensure PHPStan level 9 compliance
