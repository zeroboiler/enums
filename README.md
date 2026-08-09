# ZeroBoiler Enums

[![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4)](https://php.net)
[![Laravel 13+](https://img.shields.io/badge/Laravel-13%2B-FF2D20)](https://laravel.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-blue)](https://phpstan.org)
[![Version 1.0.0](https://img.shields.io/badge/Version-1.0.0-green)](https://github.com/zeroboiler/enums/releases)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-yellow)]()

Zero-boilerplate smart enum system for Laravel — attribute-based metadata,
auto-casting, validation, serialization, and CLI tooling.

## Table of Contents

- [Installation](#installation)
- [Quick Reference Card](#quick-reference-card)
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
- [Full-Stack Integration](#full-stack-integration)
- [Performance Considerations](#performance-considerations)
- [Test Fixtures](#test-fixtures)
- [Testing](#testing)
- [Contributing](#contributing)
- [Migration Guide](#migration-guide)
  - [From Raw Enums](#from-raw-enums)
  - [From Match Expressions](#from-match-expressions)

## Quick Reference Card

A cheat sheet for the most common operations:

```php
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Attributes\{Color, Description, EnumColor, Icon, Label};
use ZeroBoiler\Enums\Rules\EnumRule;

// ── Define ──────────────────────────────────────────────────
#[EnumColor(success: ['active'], danger: ['banned'])]
enum UserStatus: string
{
    use HasEnumMetadata;

    #[Label('Active User'), Icon('heroicon-o-check'), Description('Can login')]
    case ACTIVE = 'active';

    case INACTIVE = 'inactive';  // auto-label: "Inactive"

    #[Color('danger')]           // per-case color override
    case BANNED = 'banned';
}

// ── Access Metadata ──────────────────────────────────────────
UserStatus::ACTIVE->label();        // "Active User"
UserStatus::ACTIVE->color();        // "success"
UserStatus::ACTIVE->icon();         // "heroicon-o-check"
UserStatus::ACTIVE->description();  // "Can login"
UserStatus::INACTIVE->label();      // "Inactive" (auto-generated)
UserStatus::INACTIVE->color();      // "secondary" (default)

// ── Bulk Methods ───────────────────────────────────────────
UserStatus::forSelect();   // [['value' => 'active', 'label' => 'Active User'], ...]
UserStatus::forApi();       // full metadata with value, name, label, color, icon, description
UserStatus::values();       // ['active', 'inactive', 'banned']
UserStatus::labels();       // ['Active User', 'Inactive', 'Banned']

// ── Comparison ────────────────────────────────────────────
$status = UserStatus::ACTIVE;
$status->is(UserStatus::ACTIVE);    // true
$status->is('ACTIVE');              // true (string name)
$status->isNot(UserStatus::BANNED); // true
$status->in(['ACTIVE', 'INACTIVE']); // true

// ── Lookup ────────────────────────────────────────────────
UserStatus::tryFromLabel('Active User');  // UserStatus::ACTIVE
UserStatus::tryFromName('ACTIVE');       // UserStatus::ACTIVE
UserStatus::fromName('ACTIVE');          // UserStatus::ACTIVE (throws if not found)
UserStatus::hasCase('ACTIVE');           // true
UserStatus::hasCase('UNKNOWN');          // false

// ── Validation ─────────────────────────────────────────────
'status' => ['required', EnumRule::for(UserStatus::class)];
'status' => [EnumRule::for(UserStatus::class)->nullable()];

// ── Eloquent Cast ───────────────────────────────────────────
protected $casts = ['status' => UserStatus::class];  // auto works

// ── Facade ────────────────────────────────────────────────
use ZeroBoiler\Enums\Facades\Enum;
Enum::forSelect(UserStatus::class);
Enum::forApi(UserStatus::class);
Enum::tryFromLabel(UserStatus::class, 'Active User');

// ── CLI ─────────────────────────────────────────────────────
php artisan zeroboiler:enum-inspect "App\Enums\UserStatus"
php artisan zeroboiler:enum-test "App\Enums\UserStatus"
```

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

The `Enum` facade provides a runtime interface for enum operations without
requiring the `HasEnumMetadata` trait to be used in the calling context.
Internally it delegates to the `EnumManager` singleton (registered via the
service provider as `zeroboiler.enum`).

```php
use ZeroBoiler\Enums\Facades\Enum;

// Generate select options for dropdown rendering
$options = Enum::forSelect(UserStatus::class);
// [['value' => 'active', 'label' => 'Active User'], ...]

// Full API metadata for frontend consumption
$api = Enum::forApi(UserStatus::class);
// [['value' => 'active', 'name' => 'ACTIVE', 'label' => 'Active User', ...], ...]

// Reverse lookup by label (case-insensitive)
$case = Enum::tryFromLabel(UserStatus::class, 'Active User');
// UserStatus::ACTIVE instance (or null)
```

The EnumManager can also be injected directly from the container:

```php
use ZeroBoiler\Enums\EnumManager;

$manager = app(EnumManager::class);
$options = $manager->forSelect(UserStatus::class);
$api = $manager->forApi(UserStatus::class);
$case = $manager->tryFromLabel(UserStatus::class, 'Active User');

// EnumManager throws BadMethodCallException when the target class
// does not use the HasEnumMetadata trait
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

// Or use the resolver's convenience methods
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
EnumMetadataResolver::invalidate(UserStatus::class);  // single class
EnumMetadataResolver::invalidateAll();                // all classes

// Configure TTL for dev environments (handled automatically by service provider)
$cache = EnumCache::getInstance();
$cache->setTtl(0);  // disable caching (always fresh)
$cache->setTtl(60); // cache for 1 minute
$cache->getTtl();   // returns 0 or 60
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
| `->getTtl()` | `int` | Get current cache TTL in seconds |
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

## Full-Stack Integration

ZeroBoiler Enums works seamlessly with ZeroBoiler DTOs for end-to-end type safety
from database to API response. Here's a complete controller example:

```php
use App\DTOs\UpdateUserDTO;
use App\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController
{
    public function update(Request $request, int $id): JsonResponse
    {
        // 1. Hydrate DTO with validation
        $dto = UpdateUserDTO::fromRequest($request);

        // 2. Enum type safety — no magic strings
        if ($dto->status->is(UserStatus::BANNED)) {
            return response()->json(['error' => 'Cannot update banned users'], 403);
        }

        // 3. Update user...
        $user->status = $dto->status->value;

        // 4. Return API-ready metadata
        return response()->json([
            'user' => $dto->toArray(),
            'available_statuses' => UserStatus::forApi(),
        ]);
    }
}
```

Enum + Form Request validation:

```php
use ZeroBoiler\Enums\Rules\EnumRule;
use App\Enums\UserStatus;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', EnumRule::for(UserStatus::class)],
            'role'   => ['required', EnumRule::for(Role::class)],
        ];
    }
}
```

Enum + Blade select helper:

```blade
<select name="status">
    @foreach(UserStatus::forSelect() as $option)
        <option value="{{ $option['value'] }}"
                @if(old('status') === $option['value']) selected @endif>
            {{ $option['label'] }}
        </option>
    @endforeach
</select>
```

## Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| `Class "X" not found` when using `HasEnumMetadata` | Enum not loaded / missing `use` statement | Ensure the trait is imported: `use ZeroBoiler\Enums\Concerns\HasEnumMetadata;` |
| `->color()` always returns `'secondary'` | No color attributes defined | Add `#[EnumColor(...)]` at class level or `#[Color('...')]` per case |
| `->label()` returns weird casing | Auto-generation from case name | Use `#[Label('Custom Label')]` to override |
| `forSelect()` returns case names instead of values | Using a pure enum | Pure enums use case names as values — this is expected behavior |
| `EnumRule` passes invalid values | Int-backed enum receiving string input | Ensure the input type matches the backing type (e.g., send `1` not `'1'` for int-backed) |
| Stale metadata in Octane | Cache not flushed between requests | The package auto-listens for `octane.terminate` — if using a custom server, call `EnumCache::flush()` manually |
| `fromName('ACTIVE')` returns null | Case-sensitive comparison | Use exact case name (uppercase) |
| `tryFromLabel()` is slow on first call | Reflection overhead on first resolution | Metadata is cached after first call — subsequent calls are fast |

### FAQ

**Q: Can I use this with non-Laravel projects?**
A: The core trait (`HasEnumMetadata`), resolver, and cache work standalone without Laravel. The service provider, facade, EnumRule, and artisan commands require Laravel.

**Q: What happens if two cases have the same label?**
A: `tryFromLabel()` returns the first match in declaration order. Use unique labels to avoid ambiguity.

**Q: Can I add custom metadata types (e.g., `#[SortOrder]`)?**
A: Yes. Create a custom attribute, extend `EnumMetadataResolver::buildMetadata()`, and add an accessor method to your trait. See the [Extending](#extending) section.

**Q: Does this work with PHP enum `match()` expressions?**
A: Yes — the trait methods return scalar values compatible with `match()`. Example: `match($status->color()) { 'success' => '...', ... }`.

**Q: Are backed enums required?**
A: No. Pure enums (without backing types) are fully supported. The `values()` method returns case names, and `forSelect()` uses case names as values.

## Performance Considerations

| Operation | First Call | Subsequent Calls | Notes |
|-----------|-----------|-----------------|-------|
| `label()`, `color()`, `icon()`, `description()` | Reflection + cache build | O(1) hash lookup | Metadata cached per-class in singleton |
| `forSelect()`, `forApi()` | N case lookups | N × O(1) | Delegates to `label()` per case |
| `tryFromLabel()` | N × O(1) string comparison | Same (no cache) | Iterates all cases each call |
| `tryFromName()`, `fromName()`, `hasCase()` | N × O(1) identity check | Same (no cache) | Iterates all cases each call |
| `EnumCache::flush()` | N unset calls | N/A | Reset between Octane requests |

**Tips for high-performance applications:**
- Metadata is cached after the first call per class — no reflection overhead on subsequent calls
- For Octane/Swoole: the package auto-flushes on `octane.terminate`
- For CLI workers: call `EnumCache::flush()` between jobs if enum classes change
- `values()` and `labels()` create new arrays each call — store the result if used in loops
- The cache TTL defaults to 300s (5 min); in `local`/`testing` it's disabled (always fresh)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a history of changes.

## Version History

| Version | Date | Highlights |
|---------|------|------------|
| 1.0.0 | 2025-08 | Initial release — HasEnumMetadata trait, EnumCache, EnumRule, EnumCast, CLI commands |
| 1.0.1 | 2025-08 | Extended test suite, fixture-driven tests, PHPStan level 9 compliance |

## Internal Components

### EnumMetadataResolver

The `EnumMetadataResolver` is the engine behind all metadata access. It reads
reflection attributes from both the enum class and its cases, merges them
according to the resolution priority, and caches the result.

```php
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

// Resolve all metadata for an enum (cached)
$meta = EnumMetadataResolver::resolve(UserStatus::class);
// Returns:
// [
//     'labels'       => ['active' => 'Active User', 'banned' => 'Banned User'],
//     'descriptions' => ['active' => 'Fully active user'],
//     'colors'       => ['active' => 'success', 'banned' => 'danger'],
//     'icons'        => ['active' => 'heroicon-o-check-circle'],
// ]

// Invalidate cached metadata for a specific enum class
EnumMetadataResolver::invalidate(UserStatus::class);

// Invalidate all cached metadata
EnumMetadataResolver::invalidateAll();
```

The resolver is called automatically by all `HasEnumMetadata` trait methods.
Direct usage is typically only needed for custom tooling or extensions.

### EnumTestGenerator

Generates Pest test files for any enum using `HasEnumMetadata`:

```php
use ZeroBoiler\Enums\Support\EnumTestGenerator;

$content = EnumTestGenerator::generate(UserStatus::class);
// Returns a complete Pest test file string
```

Used internally by the `zeroboiler:enum-test` artisan command.

### Class Structure

```
src/
├── Attributes/
│   ├── Label.php          #[Label('Custom')]        — Per-case label override
│   ├── Color.php          #[Color('success')]        — Per-case color override
│   ├── Icon.php           #[Icon('heroicon-o-...')]   — Per-case icon override
│   ├── Description.php    #[Description('...')]      — Per-case description override
│   ├── EnumLabel.php      #[EnumLabel(...)]          — Class-level label map
│   ├── EnumColor.php      #[EnumColor(...)]          — Class-level color map
│   ├── EnumIcon.php       #[EnumIcon(...)]           — Class-level default icon
│   └── EnumDescription.php #[EnumDescription(...)]  — Class-level description map
├── Concerns/
│   └── HasEnumMetadata.php — Trait providing all public API methods
├── Casts/
│   └── EnumCast.php       — Eloquent cast for backed enums
├── Console/Commands/
│   ├── InspectEnumCommand.php  — Metadata inspection CLI
│   └── MakeEnumTestCommand.php — Test generation CLI
├── Exceptions/
│   └── InvalidEnumException.php — Named constructors for enum errors
├── Facades/
│   └── Enum.php           — Laravel facade for EnumManager
├── Rules/
│   └── EnumRule.php       — Validation rule for Form Requests
├── Support/
│   ├── EnumMetadataResolver.php  — Reflection-based metadata resolution
│   └── EnumTestGenerator.php     — Pest test file generation
├── EnumCache.php          — TTL-based singleton metadata cache
├── EnumManager.php        — Runtime enum helper (facade-backed)
└── EnumsServiceProvider.php — Auto-discovery service provider
```

## Test Fixtures

The test suite uses a set of representative enum fixtures covering all supported
enum types and attribute combinations:

| Fixture | Backing | Attributes | Tests |
|---------|---------|------------|-------|
| `UserStatus` | `string` | `EnumColor`, `Label`, `Color`, `Icon`, `Description` | Full per-case + class-level attribute resolution |
| `TicketStatus` | `string` | `EnumLabel`, `EnumDescription`, `EnumIcon` | Class-level bulk metadata (all three) |
| `Priority` | `int` | None | Int-backed enum with auto-generated labels |
| `IntStatusWithColor` | `int` | `EnumColor`, `Color` | Int-backed with color mapping and per-case overrides |
| `PureFeatureFlag` | none (pure) | `Icon` (per-case) | Pure enum — values/forSelect return case names |
| `CamelCaseRole` | `string` | None | camelCase → "Title Case" auto-label generation |
| `AllClassLevelEnum` | `string` | All class-level attributes | Every class-level attribute applied at once |
| `SingleCaseEnum` | `string` | None | Edge case: single-case enum |
| `ZeroPriority` | `int` | None | Edge case: zero as a valid backed value |
| `MixedAttributeStatus` | `string` | Mixed per-case attributes | Mixed attribute combinations on a single enum |
| `RequestState` | `string` | Various | Request lifecycle state machine pattern |

```php
// Each fixture is used in multiple test files. Example:
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

// In tests:
UserStatus::ACTIVE->label();       // 'Active User'
UserStatus::ACTIVE->color();       // 'success' (from class-level EnumColor)
UserStatus::BANNED->color();       // 'danger' (per-case Color override)
UserStatus::forSelect();          // [['value' => 'active', 'label' => 'Active User'], ...]
UserStatus::tryFromLabel('Active User'); // UserStatus::ACTIVE
```

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

### Test Coverage

The test suite includes **100+ test files** covering:

| Category | Tests | What's Covered |
|----------|-------|----------------|
| **Core** | `EnumTest`, `FromNameTest` | Label, color, icon, description resolution; auto-generation |
| **Comparison** | `EnumComparisonMethodsTest`, `EnumComparisonAndClassLevelTest` | `is()`, `isNot()`, `in()` with instances and strings |
| **Lookup** | Various | `tryFromLabel()`, `tryFromName()`, `fromName()`, `hasCase()` |
| **Bulk** | Various | `forSelect()`, `forApi()`, `values()`, `labels()` |
| **Manager** | `EnumManagerTest`, `EnumManagerFacadeTest`, `EnumManagerDetailedTest` | Facade delegation, forSelect/forApi/tryFromLabel via manager, error handling |
| **Cache** | `EnumCacheTest`, `EnumCacheBehaviourTest`, `EnumCacheFlushRebuildTest`, `EnumCacheTtlEdgeCasesTest` | TTL expiration, flush, reset, Octane compatibility, zero/negative TTL, singleton lifecycle |
| **Attributes** | `ClassLevelAttributesTest`, `EnumAttributeConsistencyTest` | Class-level defaults, per-case overrides |
| **Validation** | `EnumRuleTest`, `EnumRuleTypeSafetyTest`, `EnumRuleStanComplianceTest` | Backed enums, pure enums, nullable, type checking |
| **Eloquent** | `EnumCastTest`, `EnumCastEdgeCasesTest` | get/set/serialize with type validation |
| **CLI** | `ConsoleCommandsTest` | `enum-test` and `enum-inspect` commands |
| **Edge Cases** | `EnumEdgeCasesAndStanTest`, `EnumComprehensiveEdgeCaseTest` | Empty enums, single-case, camelCase labels |
| **PHPStan** | `EnumPhpStanComplianceTest`, `EnumStrictComplianceTest` | No mixed types, strict comparisons |
| **Fixtures** | `OrderStatus`, `Priority`, `UserStatus`, `TicketStatus`, etc. | Backed (string/int), pure, camelCase enums |

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

## Compatibility

| Dependency | Minimum Version | Maximum Version | Notes |
|------------|----------------|-----------------|-------|
| PHP | 8.5 | — | Uses `readonly` promoted properties, attributes, backed enums, match expressions |
| Laravel | 13.0 | — | Requires `illuminate/contracts`, `illuminate/support`, `illuminate/validation` |
| Pest (dev) | 3.0 | — | Test framework — not required for production |
| PHPStan (dev) | 2.0 | — | Static analysis — targets level 9 |

**PHP 8.5 Feature Usage:**

| Feature | Where Used | Purpose |
|---------|-----------|---------|
| `readonly` promoted properties | All attribute classes | Immutable attribute parameters |
| `enum` (backed + pure) | `EnumRule`, `EnumMetadataResolver` | Core enum support |
| `Attribute` | All attribute classes | PHP 8 attribute system |
| `match` expressions | `EnumMetadataResolver`, `EnumRule` | Type-safe pattern matching |
| `named arguments` | `EnumRule::for()` | Named constructor pattern |
| `first-class callable syntax` | `HasEnumMetadata::values()`, `labels()` | `array_map(static fn ...)` |
| `#[Override]` | Service providers, facade, `EnumCast` | Explicit interface implementation |
| `str_contains`, `str_starts_with` | Test generator | Built-in string helpers |

## Laravel Compatibility

The package uses only stable Laravel contracts (`CastsAttributes`, `ValidationRule`,
`ServiceProvider`, `Facade`, `Validator`). No bleeding-edge or internal APIs are
used, ensuring forward compatibility with future Laravel releases.

| Laravel API | Usage | Contract |
|-------------|-------|----------|
| `CastsAttributes` | `EnumCast` — Eloquent get/set/serialize | `illuminate/contracts` |
| `ValidationRule` | `EnumRule` — Form Request validation | `illuminate/contracts` |
| `ServiceProvider` | `EnumsServiceProvider` — auto-discovery | `illuminate/support` |
| `Facade` | `Enum` facade — runtime access | `illuminate/support` |
| `Validator` | Not used directly (EnumRule uses `$fail` callback) | `illuminate/validation` |

## License

Proprietary — © ZeroBoiler

## Migration Guide

### From Raw Enums

If you're migrating from plain PHP enums with manual metadata:

**Before (manual approach):**

```php
enum UserStatus: string
{
    case ACTIVE = 'active';
    case BANNED = 'banned';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active User',
            self::BANNED => 'Banned',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::BANNED => 'danger',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
```

**After (ZeroBoiler):**

```php
#[EnumColor(success: ['active'], danger: ['banned'])]
enum UserStatus: string
{
    use HasEnumMetadata;

    #[Label('Active User')]
    case ACTIVE = 'active';

    case BANNED = 'banned';  // auto-label: "Banned", color from EnumColor
}
```

Everything — labels, colors, `forSelect()`, `forApi()`, validation rules,
comparison methods (`is()`, `isNot()`, `in()`), reverse lookup (`tryFromName()`,
`tryFromLabel()`) — is available with zero boilerplate.

### From Match Expressions

```php
// Before
$color = match($status) {
    UserStatus::ACTIVE => 'success',
    UserStatus::BANNED => 'danger',
    default => 'secondary',
};

// After — single method call, reads from attributes
$color = $status->color();
```

## Quality Assurance

### Static Analysis Compliance (PHPStan Level 9)

Every source file in this package passes PHPStan level 9 analysis with zero errors
and no baseline suppressions. The following checklist is maintained manually:

| File | `strict_types` | `final` | Typed Props | Return Types | Docblocks |
|------|:---:|:---:|:---:|:---:|:---:|
| `HasEnumMetadata.php` | ✅ | trait | N/A | ✅ all | ✅ |
| `EnumMetadataResolver.php` | ✅ | ✅ | N/A (static) | ✅ all | ✅ |
| `EnumCache.php` | ✅ | ✅ | ✅ | ✅ all | ✅ |
| `EnumManager.php` | ✅ | ✅ | N/A (methods) | ✅ all | ✅ |
| `EnumRule.php` | ✅ | ✅ | ✅ readonly | ✅ all | ✅ |
| `EnumCast.php` | ✅ | ✅ | ✅ readonly | ✅ all | ✅ |
| `InvalidEnumException.php` | ✅ | ✅ | N/A | ✅ all | ✅ |
| `EnumsServiceProvider.php` | ✅ | ✅ | N/A | ✅ all | ✅ |
| `Label.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `Color.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `Icon.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `Description.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `EnumColor.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `EnumLabel.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `EnumDescription.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `EnumIcon.php` | ✅ | ✅ | ✅ readonly | N/A (ctor) | ✅ |
| `Enum.php` (Facade) | ✅ | ✅ | N/A | ✅ | ✅ |
| `InspectEnumCommand.php` | ✅ | ✅ | N/A | ✅ | ✅ |
| `MakeEnumTestCommand.php` | ✅ | ✅ | N/A | ✅ | ✅ |
| `EnumTestGenerator.php` | ✅ | ✅ | N/A | ✅ | ✅ |

### Code Quality Checklist

- [x] **`declare(strict_types=1)`** — Present in every PHP file
- [x] **No `mixed` types** — All parameters and return types are explicitly typed
- [x] **Strict comparisons** — `===` used everywhere (no `==` for value comparison)
- [x] **`final` classes** — All service classes, attributes, and resolvers are `final`
- [x] **`readonly` properties** — All attribute constructor parameters are `readonly`
- [x] **`#[Override]`** — Applied to all interface/parent method implementations
- [x] **Docblocks** — All public methods, classes, and properties documented
- [x] **`@phpstan-type`** — Complex array shapes documented with PHPStan type aliases
- [x] **Exception safety** — All error paths throw typed exceptions (no silent failures)
- [x] **Singleton safety** — `EnumCache` prevents cloning, unserialization, and `__wakeup()`
- [x] **No framework coupling in core** — Trait + resolver work standalone; Laravel optional

### Design Decisions

| Decision | Rationale |
|----------|-----------|
| Singleton cache instead of static class properties | PHP enums cannot have properties; external cache required |
| TTL-based cache expiration | Long-running processes (Octane) need periodic refresh |
| `strcasecmp` for label lookup | Case-insensitive user input matching |
| `tryFrom` with type checking in EnumRule | Prevents TypeError on int-backed enums receiving strings |
| `NoReturn` on `__clone()`/`__wakeup()` | PHPStan-level enforcement of singleton contract |
| Class-level + per-case attribute merging | Progressive defaults: per-case > class-level > auto-generated |

## Security

See [SECURITY.md](SECURITY.md) for our security policy.
