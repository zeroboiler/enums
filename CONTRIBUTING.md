# Contributing to ZeroBoiler Enums

Thank you for your interest in contributing! This document outlines the standards and workflow for this package.

## Code Standards

- **PHP 8.5+** — all source files must use `declare(strict_types=1)`
- **PHPStan Level 9** — zero warnings, no `mixed` types in public API
- **100% strict types** — every method has return type declarations, every parameter has type hints
- **Typed properties** — all class properties are `public readonly` with explicit types
- **Docblocks** — every class, method, and property has a descriptive PHPDoc block with `@param`, `@return`, `@throws` annotations
- **`#[\Override]`** — use on all interface/parent method implementations

## Architecture

| Component | Responsibility |
|-----------|---------------|
| `HasEnumMetadata` (trait) | Public API: `label()`, `color()`, `icon()`, `description()`, `forSelect()`, `forApi()`, `is()`, `in()`, `tryFromLabel()`, etc. |
| `EnumMetadataResolver` | Reflection-based attribute resolution (internal) |
| `EnumCache` | Singleton TTL-based metadata cache |
| `EnumManager` | Injectable/facade helper, delegates to trait methods |
| `EnumRule` | Laravel validation rule for backed and pure enums |
| `EnumCast` | Eloquent cast attribute for backed enums |

## Resolution Priority

Metadata is resolved in this order (later wins):

1. **Per-case attribute** — `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
2. **Class-level attribute** — `#[EnumLabel]`, `#[EnumColor]`, `#[EnumIcon]`, `#[EnumDescription]`
3. **Auto-generated** — Labels only: `SCREAMING_SNAKE_CASE → Title Case`

## Adding a New Attribute

1. Create `src/Attributes/YourAttribute.php` (final class, `#[Attribute]`)
2. Use `Attribute::TARGET_CLASS_CONSTANT` for per-case, `Attribute::TARGET_CLASS | TARGET_CLASS_CONSTANT` for class-level
3. Register resolution logic in `EnumMetadataResolver::buildMetadata()`
4. Add accessor method in `HasEnumMetadata` trait (if needed)
5. Update `EnumManager` delegation methods (if new public API)
6. Add tests in `tests/`
7. Update this README's Attributes Reference section

## Running Tests

```bash
composer test              # Run full Pest suite
composer test:coverage    # With coverage report
composer stan             # PHPStan Level 9 analysis
composer cs               # Pint code style fixer
composer analyse          # Full QA (stan + cs + test)
```

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add EnumIcon class-level attribute support
fix: resolve label fallback for pure enums
refactor: extract cache logic to EnumCache singleton
docs: update README with pure enum examples
test: add coverage for class-level attribute overrides
```

## Pull Request Checklist

- [ ] `declare(strict_types=1)` in new/modified files
- [ ] Return types on all methods
- [ ] Docblocks with `@param`, `@return`, `@throws`
- [ ] PHPStan Level 9 passes with zero errors
- [ ] New features have test coverage
- [ ] README updated if public API changed
- [ ] No `mixed` types in public API
