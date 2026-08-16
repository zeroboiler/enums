# Changelog

All notable changes to the ZeroBoiler Enums package will be documented in this file.

## [Unreleased]

### Added
- `EnumV26ComprehensiveProductionAuditTest`: comprehensive production audit — cache lifecycle (singleton, TTL, flush, clearClass, set+get roundtrip), metadata resolver cross-class isolation and stability, HasEnumMetadata accessor consistency (all cases non-empty labels, string colors, nullable icons/descriptions, forSelect/forApi count/structure consistency, unique values), comparison methods strict identity verification (is/isNot exact negation, in/empty/notIn edge cases, mixed instances+strings), reverse lookup completeness (tryFromLabel case-insensitive, tryFromName case-sensitive, fromName exception message verification, hasCase), int-backed enum type safety (int values, forSelect/forApi int values), pure enum type safety (case names, all metadata accessors), EnumCast serialization contract (get null/value/invalid, set enum/raw/null/wrong-type/invalid, serialize enum/raw/null), EnumRule validation contract (string-backed, int-backed type checking, nullable/non-nullable null handling, pure enum case name validation, error message with allowed values), EnumManager delegation (all 8 methods), final/readonly verification, InvalidEnumException factory methods, class-level attribute override priority, EnumsServiceProvider structural contract (~100 test methods)
- `DtoV26ComprehensiveProductionAuditTest`: comprehensive production audit — DTO hydration/serialization roundtrip (fromArray→toArray, with validation disabled, equals, isEmpty/isNotEmpty), serialization methods (toJson, jsonSerialize, toArray hidden exclusion, allValues inclusion, only/except field filtering), fromJson and partial methods (valid JSON, invalid JSON, sequential array rejection, fromPartialArray, with immutable), DtoCollection operations (make, push, append, first, last, map, filter, merge, toArray, jsonSerialize, clone protection, ArrayAccess CRUD, foreach iteration, pluck, items), DTOCast serialization (get null/JSON/invalid, set DTO/array/null/unsupported, serialize DTO/null), DTOManager delegation (make, rules, rulesFor, validate, fromPartialArray), metadata cache management, DTOException contract, interface compliance verification, final class and readonly verification, validation attribute contract completeness, metadata-only attribute verification (~100 test methods)

### Changed
- Fixed README test count badge (249 → 282) and version (1.0.33 → 1.0.34) [enums]
- Fixed README test count badge (274 → 318) and version (1.1.31 → 1.1.32) [dto]
- Version bump to 1.0.34 [enums]
- Version bump to 1.1.32 [dto]

## [1.0.18] - 2026-08-14

### Changed
- Full production audit: All 20 source files manually verified for `declare(strict_types=1)`, complete return type declarations, comprehensive docblocks, typed properties, PHPStan Level 9 compliance, and strict comparisons
- Version bump to 1.0.18

## [1.0.17] - 2026-08-14

### Changed
- Fixed README test count badge (225 → 224) to match actual test file count
- Version bump to 1.0.17

## [1.0.16] - 2026-08-14

### Added
- `EnumProductionStructuralAndContractIntegrityTest`: comprehensive contract verification covering all public API methods, comparison/lookup contract, metadata resolution priority, auto-label generation, zero-backed int edge cases, empty string values, single-case enums, EnumRule validation contract, EnumManager delegation, EnumCache lifecycle, EnumMetadataResolver invalidation, InvalidEnumException factory, EnumCast edge cases, select uniqueness, and type system compliance (~80 test methods)

### Changed
- Added **Error Handling Strategy** section to README — comprehensive table of all method error behaviors, exception types, design principles
- Added **Concurrency & Thread Safety** section to README — per-component thread safety matrix, Octane/Swoole/RoadRunner event listener documentation
- Updated README test count badge (222 → 225) and Package Statistics
- Version bump to 1.0.16

## [1.0.15] - 2026-08-14

### Changed
- Fixed README test count badge (250 → 222) to match actual test file count
- Fixed README Package Statistics (250 → 222 tests)
- Fixed README Test Coverage section (218 → 222)
- Full production-ready audit of all 20 source files: verified `declare(strict_types=1)`, return type declarations, typed properties, PHPStan L9 compliance, complete docblocks
- Version bump to 1.0.15

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
