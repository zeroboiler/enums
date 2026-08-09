# Changelog

All notable changes to the ZeroBoiler Enums package will be documented in this file.

## [Unreleased]

### Added
- `EnumFinalProductionAuditV2Test` — 30+ production readiness tests covering: strict_types audit, EnumCache TTL=0 edge case, generateLabel (SCREAMING_SNAKE_CASE, camelCase, pure enum), single-case enum, zero-value int-backed enum, type consistency across enum flavors, forSelect/forApi structure contracts, class-level EnumIcon/EnumDescription/EnumLabel propagation, mixed attribute priority, tryFromLabel case-insensitivity, InvalidEnumException factory methods, cache invalidation flow, values/labels type contracts

## [1.0.0] - 2025-08-08

### Added
- Smart enum trait (`HasEnumMetadata`) with attribute-based metadata resolution
- Per-case attributes: `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
- Class-level attributes: `#[EnumColor]`, `#[EnumLabel]`, `#[EnumIcon]`, `#[EnumDescription]`
- Auto-generated labels from SCREAMING_SNAKE_CASE and camelCase
- Bulk methods: `forSelect()`, `forApi()`, `values()`, `labels()`
- Comparison methods: `is()`, `isNot()`, `in()`
- Reverse lookup: `tryFromLabel()`, `tryFromName()`, `fromName()`, `hasCase()`
- `EnumRule` validation rule for Form Requests (backed + pure enums)
- `EnumCast` Eloquent cast attribute
- `EnumCache` singleton with TTL-based expiration
- `EnumManager` for runtime access via facade
- `Enum` facade for convenient enum operations
- CLI commands: `zeroboiler:enum-inspect`, `zeroboiler:enum-test`
- PHPStan level 9 compliance across all source files

### Design
- Zero-boilerplate: add `use HasEnumMetadata` and attributes, done
- Progressive defaults: per-case > class-level > auto-generated
- Type-safe: strict types, typed properties, return type declarations
- Immutable attributes: all `final` with `readonly` promoted properties

[1.0.0]: https://github.com/zeroboiler/enums/releases/tag/v1.0.0

## [Unreleased]

### Added
- `EnumLabel` and `EnumDescription` now support case-level single-value parameters (`label`, `description`) for per-case overrides via class-level attributes
- Case-level `EnumLabel` and `EnumDescription` resolve after per-case `#[Label]`/`#[Description]` but before auto-generated labels
- `EnumMetadataTypeShapeAndLifecycleTest` — validates metadata resolver output structure (PHPStan `@phpstan-type EnumMetadataShape` contract), cache lifecycle, and strict type safety for all enum types (string-backed, int-backed, pure)

### Changed
- `EnumMetadataResolver::buildMetadata()` now checks for `EnumLabel.label` and `EnumDescription.description` single-value properties on per-case attributes
