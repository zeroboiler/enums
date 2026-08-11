# Changelog

All notable changes to the ZeroBoiler Enums package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- `EnumLabel` and `EnumDescription` now support case-level single-value parameters (`label`, `description`) for per-case overrides via class-level attributes
- Case-level `EnumLabel` and `EnumDescription` resolve after per-case `#[Label]`/`#[Description]` but before auto-generated labels
- `EnumMetadataResolver::buildMetadata()` now checks for `EnumLabel.label` and `EnumDescription.description` single-value properties on per-case attributes

### Added
- `EnumMetadataTypeShapeAndLifecycleTest` — validates metadata resolver output structure, cache lifecycle, and strict type safety for all enum types
- `EnumFinalProductionAuditV2Test` — 30+ production readiness tests covering strict_types audit, cache TTL edge cases, label generation, single-case enums, zero-value int-backed enums
- `EnumHasEnumMetadataTraitContractTest` — verifies all HasEnumMetadata trait methods exist with correct signatures across all enum flavors
- `composer.json` now includes `support` field with issue tracker and source URLs
- README: Quality Assurance section with PHPStan L9 compliance table, Code Quality Checklist, and Source Code Audit

## [1.0.0] - 2025-08-08

### Added
- Smart enum trait (`HasEnumMetadata`) with attribute-based metadata resolution
  - `label()`, `description()`, `color()`, `icon()` accessors
  - `forSelect()`, `forApi()` bulk data methods
  - `tryFromLabel()`, `tryFromName()`, `fromName()` lookup methods
  - `is()`, `isNot()`, `in()` comparison helpers
  - `values()`, `labels()` collection methods
  - `hasCase()` existence check
- Per-case attributes: `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
- Class-level attributes: `#[EnumLabel]`, `#[EnumColor]`, `#[EnumIcon]`, `#[EnumDescription]`
- `EnumMetadataResolver` — reflection-based metadata resolution with TTL-based caching
- `EnumCache` — singleton cache with configurable TTL and class-level invalidation
- `EnumCast` — universal Eloquent cast for backed enums (`get`/`set`/`serialize`)
- `EnumRule` — Laravel validation rule for both backed and pure enums
- `InvalidEnumException` — named constructors for value/name lookup failures
- `EnumManager` — runtime helper accessible via `Enum` facade
- `Enum` facade — `Enum::forSelect()`, `Enum::forApi()`, `Enum::tryFromLabel()`
- `EnumsServiceProvider` — auto-discovery with Octane cache flush support
- Artisan commands: `zeroboiler:enum-test`, `zeroboiler:enum-inspect`
- PHPStan level 9 compliance across all source files
- Comprehensive test suite with 150+ test files covering all edge cases
- Full README with usage examples, architecture diagrams, and API reference
