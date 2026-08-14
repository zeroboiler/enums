# Changelog

All notable changes to the ZeroBoiler Enums package will be documented in this file.

## [1.0.9] - 2026-08-14

### Added
- EnumCast serialization roundtrip contract tests (get/set/serialize type safety, int/string enum roundtrips, PHPStan L9 compliance)
- Updated test count (240 → 241)

## [1.0.8] - 2026-08-14

### Changed
- Updated test count badge (211 → 236 tests, 27 fixtures)
- Updated Package Statistics section with accurate test and fixture counts

## [1.0.7] - 2026-08-14

### Changed
- Enriched README with comprehensive Integer-Backed and Pure Enum usage examples including comparison, lookup, validation, and Eloquent cast patterns
- Updated test count badge (203 tests, 22 fixtures)
- Updated Package Statistics section

## [1.0.0] - 2025-08-14

### Added
- `HasEnumMetadata` trait with full API: `label()`, `color()`, `icon()`, `description()`, `forSelect()`, `forApi()`, `tryFromLabel()`, `tryFromName()`, `fromName()`, `hasCase()`, `is()`, `isNot()`, `in()`, `notIn()`, `values()`, `labels()`
- Per-case attributes: `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
- Class-level attributes: `#[EnumLabel]`, `#[EnumColor]`, `#[EnumIcon]`, `#[EnumDescription]`
- `EnumMetadataResolver` for attribute resolution with reflection-based caching
- `EnumCache` singleton with TTL-based expiration and flush support
- `EnumManager` injectable helper with facade support
- `EnumRule` validation rule for both backed and pure enums
- `EnumCast` Eloquent cast for any backed enum type
- `InvalidEnumException` with named constructors `forName()` and `value()`
- CLI commands: `zeroboiler:enum-inspect` and `zeroboiler:enum-test`
- `EnumTestGenerator` for comprehensive Pest test file generation
- PHPStan Level 9 compliance across all source files
- 225 test files covering all edge cases and contract compliance
