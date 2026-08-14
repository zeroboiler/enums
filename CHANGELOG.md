# Changelog

All notable changes to the ZeroBoiler Enums package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-08-14

### Added
- `HasEnumMetadata` trait — core public API providing `label()`, `color()`, `icon()`, `description()`, `forSelect()`, `forApi()`, `values()`, `labels()`, `is()`, `isNot()`, `in()`, `notIn()`, `tryFromLabel()`, `tryFromName()`, `fromName()`, `hasCase()`
- `EnumMetadataResolver` — reflection-based metadata resolution from class-level and per-case attributes with TTL-based caching
- `EnumCache` — singleton TTL-based cache for enum metadata, with `setTtl()`, `clear()`, `clearClass()`, `flush()`, `resetInstance()`
- `EnumManager` — runtime helper (injectable/facade) delegating to trait methods
- `Enum` facade — Laravel facade for `EnumManager`
- `EnumsServiceProvider` — auto-discovery service provider with Octane cache flush support
- Per-case attributes: `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
- Class-level attributes: `#[EnumLabel]`, `#[EnumColor]`, `#[EnumIcon]`, `#[EnumDescription]`
- `EnumRule` — universal validation rule for backed and pure enums with `nullable()` support
- `EnumCast` — Eloquent cast for backed enums with `get()`, `set()`, `serialize()`
- `InvalidEnumException` — named constructors `value()` and `forName()`
- `InspectEnumCommand` — `php artisan zeroboiler:enum-inspect` CLI table viewer
- `MakeEnumTestCommand` — `php artisan zeroboiler:enum-test` Pest test generator
- `EnumTestGenerator` — comprehensive test file generation engine
- Auto-generated labels from `SCREAMING_SNAKE_CASE` → `Title Case`
- Support for string-backed, int-backed, and pure enums
- Metadata resolution priority: per-case attribute > class-level attribute > auto-generated

### Quality
- 100% `declare(strict_types=1)` coverage across all 20 source files
- PHPStan Level 9 compatible — zero `mixed` return types in public API
- All classes marked `final` where appropriate
- All attributes use `readonly` promoted constructor properties
- Comprehensive PHPDoc with `@param`, `@return`, `@throws` annotations
- 226 test files (204 unit tests + 22 fixtures)

## [Unreleased]

_No unreleased changes._
