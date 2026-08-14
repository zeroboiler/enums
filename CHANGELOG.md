# Changelog

All notable changes to the ZeroBoiler Enums package will be documented in this file.

## [1.0.14] - 2026-08-14

### Added
- `EnumCacheTtlEdgeCaseTest`: comprehensive TTL clamping (negative→0), zero-disable behavior, positive TTL caching, clearClass specificity, singleton lifecycle, flush/cover behavior, get() without has() OutOfBoundsException (15 test methods)

### Changed
- Full production-ready audit of all 20 source files: verified `declare(strict_types=1)`, return type declarations, typed properties, PHPStan L9 compliance (no mixed types, strict comparisons), complete docblocks
- Updated README test count badge and Package Statistics (219 → 222 tests)
- Version bump to 1.0.14

## [1.0.13] - 2026-08-14

### Changed
- Updated README test count badge (218 → 220 tests) to match actual test file count
- Production readiness audit: verified all 20 source files for PHPStan Level 9 compliance
- Version bump to 1.0.13

## [1.0.12] - 2026-08-14

### Added
- Production readiness V7 test: comprehensive full attribute resolution (class-level + per-case), empty string edge cases, EnumCache lifecycle (TTL, singleton, flush, reset), InvalidEnumException factory methods, attribute contract verification, EnumRule validation
- Updated test count (217 → 218)

### Changed
- README test count badge and Package Statistics updated to 218 tests
- Version bump to 1.0.12

## [1.0.11] - 2026-08-14

### Changed
- Production readiness audit: verified all 20 source files for PHPStan Level 9 compliance (strict types, typed properties, return type declarations, comprehensive docblocks, strict comparisons)
- Fixed README test count badge (219 → 217 tests) to match actual test file count

## [1.0.10] - 2026-08-14

### Added
- EnumRule type mismatch edge-case tests (int/string/float/bool/array rejection, nullable semantics, pure enum validation, boundary values, structural contract)
- EnumCache singleton lifecycle and TTL edge-case tests (multi-class isolation, TTL expiration, flush/clear, structural contract)
- Updated test count (218 → 219)

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
