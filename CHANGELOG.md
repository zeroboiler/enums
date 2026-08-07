# Changelog

All notable changes to the ZeroBoiler Enums package will be documented in this file.

## [Unreleased]

### Changed
- Enriched README with Internal Components section (EnumMetadataResolver, EnumTestGenerator, Class Structure)
- Added Test Coverage table to README documenting 50+ test files across all categories

## [1.0.0] - 2026-08-06

### Changed
- `EnumCache::setTtl()` now normalizes negative values to 0 (prevents undefined behavior)
- Added `clear()` and `clearClass()` to EnumCache API Reference in README

### Added
- Smart enum trait (`HasEnumMetadata`) with zero-boilerplate metadata resolution
- Attribute-based metadata: `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
- Class-level default attributes: `#[EnumLabel]`, `#[EnumColor]`, `#[EnumIcon]`, `#[EnumDescription]`
- Auto-generated labels from `SCREAMING_SNAKE_CASE` → `Title Case`
- Bulk helpers: `forSelect()`, `forApi()`, `values()`, `labels()`
- Comparison methods: `is()`, `isNot()`, `in()` — instance and string support
- Reverse lookup: `tryFromLabel()`, `tryFromName()`, `fromName()`, `hasCase()`
- TTL-based metadata cache via `EnumCache` singleton (default: 300s)
- Eloquent auto-cast via `EnumCast`
- Validation rule via `EnumRule` (backed + pure enum support)
- Facade (`Enum`) and manager (`EnumManager`) for runtime access
- CLI commands: `zeroboiler:enum-test`, `zeroboiler:enum-inspect`
- Full PHPStan level 9 compliance (no baseline errors)
- Comprehensive Pest test suite with 20+ test files
- Enhanced CLI documentation with generated test output examples

### Requirements
- PHP 8.5+
- Laravel 13+
