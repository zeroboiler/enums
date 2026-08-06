# ZeroBoiler Enums

[![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4)](https://php.net)
[![Laravel 13+](https://img.shields.io/badge/Laravel-13%2B-FF2D20)](https://laravel.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-blue)](https://phpstan.org)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-yellow)]()

Zero-boilerplate smart enum system for Laravel — attribute-based metadata,
auto-casting, validation, serialization, and CLI tooling.

## Table of Contents

- [Installation](#installation)
- [Type System](#type-system)
  - [Resolution Priority](#resolution-priority)
  - [Architecture](#architecture)
- [Features](#features)
- [Usage](#usage)
  - [Accessors](#accessors)
  - [Bulk Methods](#bulk-methods)
  - [Comparison](#comparison)
  - [Lookup](#lookup)
  - [Eloquent Cast](#eloquent-cast)
  - [Validation](#validation)
  - [CLI Commands](#cli-commands)
  - [Enum Facade / Manager](#enum-facade--manager)
  - [String-Backed Enum Example](#string-backed-enum-example)
  - [Integer-Backed Enum Example](#integer-backed-enum-example)
  - [Pure Enum Example](#pure-enum-example)
- [Advanced](#advanced)
  - [Class-Level Attributes](#class-level-attributes)
  - [Cache Management](#cache-management)
- [Attributes Reference](#attributes-reference)
  - [Per-Case Attributes](#per-case-attributes)
  - [Class-Level Attributes](#class-level-attributes)
- [API Quick Reference](#api-quick-reference)
  - [HasEnumMetadata Trait](#hasenummetadata-trait)
  - [EnumRule](#enumrule)
  - [EnumCache (Singleton)](#enumcache-singleton)
  - [EnumManager (via Facade)](#enummanager-via-facade)
  - [Exception Hierarchy](#exception-hierarchy)
- [Design Principles](#design-principles)
- [Extending](#extending)
  - [Custom Metadata Methods](#custom-metadata-methods)
  - [Registering Custom Attributes](#registering-custom-attributes)
- [Testing](#testing)
- [Contributing](#contributing)

## Installation

```bash
composer require zeroboiler/enums
```

The package auto-registers via Laravel's package discovery. No manual configuration needed.

**Requirements:**
- PHP 8.5+
- Laravel 13+

## Quick Start

Create your first smart enum in under a minute:

```php
// app/Enums/UserStatus.php
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

#[EnumColor(success: ['active'], danger: ['banned'])]
enum UserStatus: string
{
    use HasEnumMetadata;

    #[Color('danger')]
    case BANNED = 'banned';

    case ACTIVE = 'active';

    case PENDING = 'pending';
}

// Usage in controllers
UserStatus::ACTIVE->label();    // "Active" (auto-generated)
UserStatus::ACTIVE->color();    // "success" (class-level)
UserStatus::BANNED->color();    // "danger" (per-case override)

// Select dropdowns
UserStatus::forSelect();
// [['value' => 'banned', 'label' => 'Banned'], ['value' => 'active', 'label' => 'Active'], ...]

// Validation in Form Requests
'status' => ['required', EnumRule::for(UserStatus::class)],

// CLI inspection
php artisan zeroboiler:enum-inspect "App\Enums\UserStatus"

// Auto-generate tests
php artisan zeroboiler:enum-test "App\Enums\UserStatus"
```

## Type System

ZeroBoiler Enums works with **all three** PHP enum types:

| Type | Backing | Use Case | `values()` returns |
|------|---------|----------|-------------------|
| **Backed Enum (string)** | `enum Foo: string` | Database columns, API payloads — most common | `['a', 'b', 'c']` |
| **Backed Enum (int)** | `enum Bar: int` | Status codes, priority levels, flags | `[1, 2, 3]` |
| **Pure Enum** | `enum Baz` | State machines, feature flags without storage | `['CASE_A', 'CASE_B']` |

All metadata features (`label()`, `color()`, `icon()`, `description()`) work identically
across all three enum types. For backed enums, `forSelect()` and `values()` return
the **backed value** (not the case name). For pure enums, the **case name** is used instead.

### Resolution Priority

Metadata is resolved in this order (later wins):

1. **Per-case attribute** — `#[Label('Custom')]`, `#[Color('success')]`, etc.
2. **Class-level attribute** — `#[EnumLabel(...)]`, `#[EnumColor(...)]`, etc.
3. **Auto-generated** — Only for labels: `SCREAMING_SNAKE_CASE → Title Case`

Colors default to `'secondary'` and icons/descriptions default to `null` when not set.

### Architecture

```
┌─────────────────────────────────────┐
│          Your Enum                  │
│  enum UserStatus: string            │
│  {                                  │
│      use HasEnumMetadata;  ◄────────┼── Trait provides all public API
│  }                                  │
└──────────┬──────────────────────────┘
           │ reads via
           ▼
┌─────────────────────────────────────┐
│  EnumMetadataResolver               │  Reads #[Label], #[Color], etc.
│  ├─ Reads class-level attributes    │  via ReflectionEnum
│  ├─ Reads per-case attributes       │
│  └─ Merges & caches result         │
└──────────┬──────────────────────────┘
           │ caches in
           ▼
┌─────────────────────────────────────┐
│  EnumCache (Singleton)              │  TTL-based per-class cache
│  ├─ has($class) → bool              │  Prevents repeated reflection
│  ├─ get($class) → array            │
│  └─ TTL default: 300s (5 min)      │
└─────────────────────────────────────┘
```

## Features

- **Attribute-based metadata** — `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
- **Auto-generated labels** — SCREAMING_SNAKE_CASE → Title Case automatically
- **Class-level defaults** — `#[EnumColor(success: ['active'], danger: ['banned'])]`
- **Eloquent auto-cast** — works with any backed enum
- **Validation rule** — `EnumRule::for(UserStatus::class)`
- **Bulk helpers** — `forSelect()`, `forApi()`, `values()`, `labels()`
- **Reverse lookup** — `tryFromLabel('Active User')`, `tryFromName('ACTIVE')`, `fromName('ACTIVE')`
- **Name lookup** — `hasCase('ACTIVE')`
- **Comparison** — `is()`, `isNot()`, `in()` with instance and string support
- **CLI tools** — `zeroboiler:enum-test`, `zeroboiler:enum-inspect`
- **Singleton cache** — TTL-based metadata cache with flush/reset support

## Usage

### String-Backed Enum Example

```php
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

#[EnumColor(success: ['active'], danger: ['banned'], warning: ['pending', 'suspended'])]
enum UserStatus: string
{
    use HasEnumMetadata;

    #[Label('Active User')]
    #[Icon('heroicon-o-check-circle')]
    #[Description('User can fully access the system')]
    case ACTIVE = 'active';

    case INACTIVE = 'inactive';

    #[Label('Awaiting Verification')]
    case PENDING = 'pending';

    case SUSPENDED = 'suspended';

    #[Color('danger')]
    #[Description('User is permanently banned')]
    case BANNED = 'banned';
}
```

### Accessors

```php
UserStatus::ACTIVE->label();       // "Active User" (per-case attribute)
UserStatus::INACTIVE->label();     // "Inactive" (auto-generated from INACTIVE)
UserStatus::ACTIVE->color();       // "success" (from class-level EnumColor)
UserStatus::BANNED->color();       // "danger" (per-case override)
UserStatus::ACTIVE->icon();        // "heroicon-o-check-circle" (per-case)
UserStatus::INACTIVE->icon();      // null (not defined)
UserStatus::ACTIVE->description(); // "User can fully access the system"
UserStatus::INACTIVE->description(); // null (not defined)
```

### Bulk Methods

```php
UserStatus::forSelect();
// [['value' => 'active', 'label' => 'Active User'], ...]

UserStatus::forApi();
// [['value' => 'active', 'name' => 'ACTIVE', 'label' => 'Active User', 'color' => 'success', 'icon' => '...', 'description' => '...'], ...]

UserStatus::values();    // ['active', 'inactive', 'pending', 'suspended', 'banned']
UserStatus::labels();    // ['Active User', 'Inactive', 'Awaiting Verification', 'Suspended', 'Banned']
```

### Comparison

Compare enum cases using `is()`, `isNot()`, and `in()`. Works with both
enum instances and case name strings:

```php
$status = UserStatus::ACTIVE;

// Instance comparison (strict identity)
$status->is(UserStatus::ACTIVE);     // true
$status->is(UserStatus::BANNED);     // false

// String case name comparison (case-sensitive)
$status->is('ACTIVE');               // true
$status->is('active');               // false (case-sensitive, backed value ≠ case name)

// Negation
$status->isNot(UserStatus::BANNED);  // true

// Group matching — check if in a list of cases (accepts mixed instances and strings)
$status->in([UserStatus::ACTIVE, UserStatus::PENDING]);  // true
$status->in(['ACTIVE', 'PENDING']);                       // true (mixed)
$status->in([UserStatus::BANNED, UserStatus::SUSPENDED]); // false
```

### Lookup

```php
// By label (case-insensitive)
UserStatus::tryFromLabel('Active User'); // UserStatus::ACTIVE
UserStatus::tryFromLabel('inactive');    // UserStatus::INACTIVE (auto-generated label)
UserStatus::tryFromLabel('UNKNOWN');    // null

// By case name (case-sensitive)
UserStatus::tryFromName('ACTIVE');      // UserStatus::ACTIVE
UserStatus::tryFromName('UNKNOWN');    // null
UserStatus::fromName('ACTIVE');         // UserStatus::ACTIVE (throws InvalidEnumException)
UserStatus::fromName('UNKNOWN');       // throws InvalidEnumException

// Check existence
UserStatus::hasCase('ACTIVE');          // true
UserStatus::hasCase('UNKNOWN');         // false
```

### Eloquent Cast

```php
protected $casts = [
    'status' => UserStatus::class,  // works automatically via EnumCast
];
```

The built-in `EnumCast` handles `get()`, `set()`, and `serialize()` transparently.
It validates stored values and rejects type mismatches.

### Validation

```php
use ZeroBoiler\Enums\Rules\EnumRule;

// Required field
'status' => ['required', EnumRule::for(UserStatus::class)],

// Nullable field — null passes, other values are validated
'status' => [EnumRule::for(UserStatus::class)->nullable()],
```

EnumRule supports both backed enums (validates against backed values with type checking)
and pure enums (validates against case names).

### CLI Commands

```bash
# Generate Pest tests for an enum
php artisan zeroboiler:enum-test "App\Enums\UserStatus"
php artisan zeroboiler:enum-test "App\Enums\UserStatus" --dir=tests/Unit/Enums

# Inspect enum metadata (displays table with Name, Value, Label, Color, Icon, Description)
php artisan zeroboiler:enum-inspect "App\Enums\UserStatus"
```

The test generator produces a Pest file with:
- **Case existence** test — verifies the enum has cases
- **forSelect()** test — validates structure (`value` + `label` keys)
- **forApi()** test — validates full metadata structure
- **Unique values** test — ensures no duplicate backed values
- **Per-case label/color** tests — one test per case
- **Comparison tests** — `is()`, `isNot()`, `in()` with instances and strings
- **Lookup tests** — `tryFromName()`, `hasCase()`, `tryFromLabel()`

Generated output example:

```php
<?php

declare(strict_types=1);

use App\Enums\UserStatus;

describe('UserStatus enum', function () {
    it('has cases', function () {
        expect(UserStatus::cases())->not->toBeEmpty();
    });

    it('can generate select options', function () {
        $options = UserStatus::forSelect();
        expect($options)->toBeArray();
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('has a label for case ACTIVE', function () {
        expect(UserStatus::ACTIVE->label())->toBeString()->not->toBeEmpty();
    });

    it('has a color for case ACTIVE', function () {
        expect(UserStatus::ACTIVE->color())->toBeString();
    });
    // ... more cases
});
```

### Enum Facade / Manager

```php
use ZeroBoiler\Enums\Facades\Enum;

// Generate select options
$options = Enum::forSelect(UserStatus::class);

// Full API metadata
$api = Enum::forApi(UserStatus::class);

// Reverse lookup by label
$case = Enum::tryFromLabel(UserStatus::class, 'Active User');
```

The EnumManager can also be injected directly from the container:

```php
use ZeroBoiler\Enums\EnumManager;

$manager = app(EnumManager::class);
$options = $manager->forSelect(UserStatus::class);
```

### Integer-Backed Enum Example

```php
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

#[EnumColor(success: [3, 4], danger: [1], warning: [2])]
enum Priority: int
{
    use HasEnumMetadata;

    #[Color('danger')]
    case CRITICAL = 1;

    #[Color('warning')]
    case HIGH = 2;

    #[Color('success')]
    case LOW = 3;

    case NONE = 4;
}

Priority::HIGH->label();         // "High" (auto-generated)
Priority::HIGH->color();         // "warning" (per-case override)
Priority::CRITICAL->value;       // 1 (int backed)
Priority::values();              // [1, 2, 3, 4]
Priority::forSelect();           // [['value' => 1, 'label' => 'Critical'], ...]
```

### Pure Enum Example

```php
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

enum FeatureFlag
{
    use HasEnumMetadata;

    #[Icon('heroicon-o-shield-check')]
    #[Description('Two-factor authentication for all users')]
    case TWO_FACTOR_AUTH;

    #[Description('Dark mode theme support')]
    case DARK_MODE;
}

FeatureFlag::TWO_FACTOR_AUTH->label();       // "Two Factor Auth"
// FeatureFlag::TWO_FACTOR_AUTH->value;     // NOT AVAILABLE (pure enum)
FeatureFlag::values();                      // ['TWO_FACTOR_AUTH', 'DARK_MODE'] (case names)
FeatureFlag::forSelect();                   // [['value' => 'TWO_FACTOR_AUTH', 'label' => 'Two Factor Auth'], ...]
```

## Advanced

### Class-Level Attributes

In addition to per-case attributes, you can set defaults at the class level:

```php
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumDescription;

#[EnumColor(success: ['active', 'paid'], danger: ['banned'])]
#[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
#[EnumIcon(default: 'heroicon-o-question-mark-circle')]
#[EnumDescription(descriptions: ['active' => 'Fully active user', 'banned' => 'Permanently banned'])]
enum UserStatus: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case BANNED = 'banned';
}
```

### Cache Management

Enum metadata is cached per-class to avoid repeated reflection. In most cases
this is transparent, but you can manage the cache for testing or long-running processes:

```php
use ZeroBoiler\Enums\EnumCache;

// Flush all cached metadata (useful after deployment)
EnumCache::flush();

// Reset the singleton instance (for testing)
EnumCache::resetInstance();

// Configure TTL for dev environments (handled automatically by service provider)
$cache = EnumCache::getInstance();
$cache->setTtl(0);  // disable caching (always fresh)
$cache->setTtl(60); // cache for 1 minute
```

## Attributes Reference

### Per-Case Attributes

| Attribute | Target | Parameters | Description |
|-----------|--------|------------|-------------|
| `#[Label('...')]` | Case | `string $value` | Human-readable label for a single case (overrides class-level) |
| `#[Color('...')]` | Case | `string $value` | UI color for a single case (overrides class-level) |
| `#[Icon('...')]` | Case | `string $value` | Icon identifier for a single case (overrides class-level) |
| `#[Description('...')]` | Case | `string $value` | Description for a single case (overrides class-level) |

### Class-Level Attributes

| Attribute | Target | Parameters | Description |
|-----------|--------|------------|-------------|
| `#[EnumColor(...)]` | Class + Case | `success: list<string>`, `danger: list<string>`, `warning: list<string>`, `info: list<string>`, `secondary: list<string>` | Map case values to UI colors |
| `#[EnumLabel(...)]` | Class + Case | `labels: array<string, string>`, `label: string` | Set labels for multiple cases at once (class-level) or a single case |
| `#[EnumDescription(...)]` | Class + Case | `descriptions: array<string, string>`, `description: string` | Set descriptions for multiple cases at once |
| `#[EnumIcon(...)]` | Class + Case | `default: string` | Set a default icon for all cases |

Valid colors: `success`, `danger`, `warning`, `info`, `secondary`.

## API Quick Reference

### HasEnumMetadata Trait

| Method | Returns | Description |
|--------|---------|-------------|
| `->label()` | `string` | Human-readable label (per-case → class-level → auto-generated) |
| `->description()` | `?string` | Case description (per-case → class-level → null) |
| `->color()` | `string` | UI color (`success`, `danger`, `warning`, `info`, `secondary`) |
| `->icon()` | `?string` | Icon identifier (per-case → class-level → null) |
| `::forSelect()` | `list<array{value: string\|int, label: string}>` | Options for `<select>` elements |
| `::forApi()` | `list<array{value: string\|int, name: string, label: string, description: ?string, color: string, icon: ?string}>` | Full API metadata |
| `::tryFromLabel(string)` | `?static` | Resolve by label (case-insensitive) |
| `::tryFromName(string)` | `?static` | Resolve by case name (case-sensitive) |
| `::fromName(string)` | `static` | Resolve by case name (throws on failure) |
| `::hasCase(string)` | `bool` | Check if a case name exists |
| `->is(self\|string)` | `bool` | Check if this case matches another (instance or name) |
| `->isNot(self\|string)` | `bool` | Check if this case does NOT match another |
| `->in(array<self\|string>)` | `bool` | Check if this case is in a list of cases |
| `::values()` | `list<string\|int>` | All backed values or case names |
| `::labels()` | `list<string>` | All labels in declaration order |

### EnumRule

| Method | Returns | Description |
|--------|---------|-------------|
| `::for(class-string)` | `EnumRule` | Create rule for an enum class |
| `->nullable()` | `EnumRule` | Allow null values to pass |

### EnumCache (Singleton)

| Method | Returns | Description |
|--------|---------|-------------|
| `::getInstance()` | `self` | Get singleton instance |
| `::flush()` | `void` | Clear all cached metadata |
| `::resetInstance()` | `void` | Reset singleton (for testing) |
| `->has(string)` | `bool` | Check if class has cached metadata (respects TTL) |
| `->get(string)` | `array` | Get cached metadata (throws if missing) |
| `->set(string, array)` | `void` | Store cached metadata |
| `->setTtl(int)` | `void` | Set cache TTL in seconds (0 = disabled) |
| `->clear()` | `void` | Clear all cached entries (instance method) |
| `->clearClass(string)` | `void` | Clear cached metadata for a specific class |

### EnumManager (via Facade)

| Facade Method | Description |
|---------------|-------------|
| `Enum::forSelect(string)` | Generate select options for an enum class |
| `Enum::forApi(string)` | Generate full API metadata |
| `Enum::tryFromLabel(string, string)` | Resolve by label |

### Exception Hierarchy

```
InvalidEnumException (extends Exception)
├─ value($enumClass, $value)  — Invalid backed value lookup
└─ forName($enumClass, $name)  — Invalid case name lookup
```

Thrown by:
- `fromName()` when the case name doesn't exist
- `InvalidEnumException::value()` when a backed value is invalid (available for custom error handling)

> **Note:** `EnumRule` does **not** throw `InvalidEnumException`. It uses Laravel's
> `ValidationRule` interface and fails silently via the `$fail` callback,
> producing standard Laravel validation error messages.

## Design Principles

| Principle | Implementation |
|-----------|---------------|
| **Zero config** | No service provider config, no publishable files — works out of the box |
| **Immutable by default** | PHP enums are inherently immutable; metadata is cached in a singleton |
| **Attribute-first** | All metadata declared via PHP 8 attributes, not arrays or config files |
| **Progressive defaults** | Per-case attribute → class-level attribute → auto-generated label |
| **Strict typing** | `declare(strict_types=1)` in every file; PHPStan level 9 clean |
| **No magic strings** | Colors limited to `success`, `danger`, `warning`, `info`, `secondary` |
| **Final classes** | All attributes, services, and resolvers are `final` |

## Extending

### Custom Metadata Methods

You can add custom metadata methods to any enum using the trait. The metadata
resolver provides access to the full metadata array for any key:

```php
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

enum UserStatus: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case BANNED = 'banned';

    /**
     * Check if this case represents an active/allowed state.
     */
    public function isActive(): bool
    {
        return $this->in(['ACTIVE']);
    }

    /**
     * Get the badge HTML for this status (custom rendering).
     */
    public function badgeHtml(): string
    {
        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $this->color(),
            $this->label()
        );
    }
}

// Usage
UserStatus::ACTIVE->isActive();   // true
UserStatus::ACTIVE->badgeHtml(); // '<span class="badge badge-success">Active</span>'
```

### Registering Custom Attributes

To add a new metadata type (e.g., `#[SortOrder]`):

1. Create the attribute class in `App\Attributes\SortOrder`
2. Extend `EnumMetadataResolver::buildMetadata()` or call it from a custom resolver
3. Add a corresponding accessor method to your custom trait (or extend `HasEnumMetadata`)

For most use cases, the built-in attributes (`Label`, `Color`, `Icon`, `Description`)
cover common UI requirements. Custom attributes are typically only needed for
domain-specific metadata that doesn't map to standard UI concerns.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a history of changes.

## Testing

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

All checks must pass before merging. The package targets PHPStan level 9 with a clean baseline (zero suppressed errors).

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Ensure all CI checks pass (`composer ci`)
4. Commit with conventional commits (`feat:`, `fix:`, `refactor:`)
5. Push and open a Pull Request

### Code Standards

- **PHP 8.5 syntax** — use the latest language features
- **Strict types** — every file must have `declare(strict_types=1)`
- **PHPStan level 9** — zero errors, no baseline suppressions
- **Docblocks** — all public methods and properties documented
- **Typed properties** — no `mixed` types in source code
- **Final classes** — all attributes and services are `final`

## License

Proprietary — © ZeroBoiler

## Security

See [SECURITY.md](SECURITY.md) for our security policy.
