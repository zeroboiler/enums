# ZeroBoiler Enums

[![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4)](https://php.net)
[![Laravel 13+](https://img.shields.io/badge/Laravel-13%2B-FF2D20)](https://laravel.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-blue)](https://phpstan.org)
[![Tests: 341 files](https://img.shields.io/badge/Tests-341%20files-brightgreen)](tests)
[![Version 1.0.79](https://img.shields.io/badge/Version-1.0.79-green)](https://github.com/zeroboiler/enums/releases)
[![Source: 20 files](https://img.shields.io/badge/Source-20%20files-informational)](src)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-yellow)]()

Zero-boilerplate enum metadata, serialization, and helper traits for Laravel.

Works with all three PHP 8.5+ enum types:

- **String-backed enum** — `case ACTIVE = 'active';` → `toValue()` returns `'active'`
- **Int-backed enum** — `case ACTIVE = 1;` → `toValue()` returns `1`
- **Pure enum** — `case ACTIVE;` → `toValue()` returns `'ACTIVE'` (case name)

---

## Why ZeroBoiler Enums?

| Problem | ZeroBoiler Solution |
|---------|-------------------|
| Manual label/color/icon mapping for each case | **Attribute-driven metadata** — `#[Label('...')]`, `#[Color('...')]`, `#[Icon('...')]` directly on cases |
| Repetitive `match()` blocks for human-readable names | **`label()` auto-generation** — SCREAMING_SNAKE_CASE → "Title Case" out of the box |
| No easy way to generate `<select>` options from enums | **`forSelect()`** — returns `[{value, label}]` pairs in one call |
| No full metadata API response for frontends | **`forApi()`** — returns value, name, label, description, color, icon for every case |
| Manual `in_array()` / `===` checks for enum states | **`is()`, `isNot()`, `in()`, `notIn()`** — fluent comparison with instances or strings |
| No reverse lookup by label | **`tryFromLabel()`** — case-insensitive label → enum case resolution |
| No per-class bulk metadata definition | **Class-level attributes** — `#[EnumLabel(...)]`, `#[EnumColor(...)]` map all cases at once |
| Enum metadata not cached in long-running processes | **`EnumCache`** — TTL-based singleton cache, auto-flushed on Octane/Swoole terminate |
| No CLI tool for inspecting enum metadata | **`zeroboiler:enum-inspect`** — table output of all metadata per case |
| Writing enum tests manually every time | **`zeroboiler:enum-test`** — generates comprehensive Pest tests automatically |
| No Eloquent cast with extended validation | **`EnumCast`** — validates stored values, null-safe get/set |
| No custom validation rule for form requests | **`EnumRule`** — works with backed AND pure enums, better error messages |

**Zero ceremony. Zero boilerplate. Production-grade from day one.**

---

## Table of Contents

- [Installation](#installation)
- [Why ZeroBoiler Enums?](#why-zeroboiler-enums)
- [Quick Start](#quick-start)
- [Quick Reference Card](#quick-reference-card)
- [Architecture](#architecture)
  - [Type System](#type-system)
  - [Metadata Resolution Order](#metadata-resolution-order)
  - [Metadata Cache Shape](#metadata-cache-shape)
- [Attributes Reference](#attributes-reference)
- [API Reference](#api-reference)
- [Artisan Commands](#artisan-commands)
- [Advanced Usage](#advanced-usage)
  - [Pure Enums (no backing type)](#pure-enums-no-backing-type)
  - [Int-Backed Enums](#int-backed-enums)
  - [Class-Level Bulk Metadata](#class-level-bulk-metadata)
  - [Blade View Rendering](#blade-view-rendering)
  - [API Resource Output](#api-resource-output)
  - [Livewire Component Integration](#livewire-component-integration)
  - [Conditional Business Logic](#conditional-business-logic)
  - [Database Query Scopes](#database-query-scopes)
  - [Eloquent Model Integration](#eloquent-model-integration)
  - [Cache Configuration](#cache-configuration)
- [Troubleshooting](#troubleshooting)
- [Performance & Caching](#performance--caching)
- [Source Code Structure](#source-code-structure)
- [Changelog](#changelog)
- [License](#license)

---

## Quick Reference Card

| Operation | Method | Returns |
|-----------|--------|---------|
| Get label | `$case->label()` | `string` |
| Get description | `$case->description()` | `\?string` |
| Get color | `$case->color()` | `string` (default: `'secondary'`) |
| Get icon | `$case->icon()` | `\?string` |
| Get backed value | `$case->toValue()` | `int|string` |
| Compare (instance or name) | `$case->is(...)` | `bool` |
| Negate comparison | `$case->isNot(...)` | `bool` |
| Group match | `$case->in([...])` | `bool` |
| Group exclude | `$case->notIn([...])` | `bool` |
| Dropdown options | `Enum::forSelect()` | `list<array{value, label}>` |
| Full API metadata | `Enum::forApi()` | `list<array{value, name, label, description, color, icon}>` |
| All values | `Enum::values()` | `list<string\int>` |
| All labels | `Enum::labels()` | `list<string>` |
| Find by name | `Enum::tryFromName('ACTIVE')` | `\?static` |
| Find by name (throws) | `Enum::fromName('ACTIVE')` | `static` |
| Check name exists | `Enum::hasCase('ACTIVE')` | `bool` |
| Find by label (ci) | `Enum::tryFromLabel('Active')` | `\?static` |
| Eloquent cast | `EnumCast::of(MyEnum::class)` | `EnumCast` |
| Validation rule | `EnumRule::for(MyEnum::class)` | `EnumRule` |
| Facade access | `Enum::forSelect(MyEnum::class)` | `array` |

**Attributes at a glance:**

| Attribute | Target | Purpose |
|-----------|--------|---------|
| `#[Label('...')]` | Case | Per-case label override |
| `#[Color('...')]` | Case | Per-case color override |
| `#[Icon('...')]` | Case | Per-case icon override |
| `#[Description('...')]` | Case | Per-case description override |
| `#[EnumLabel(...)]` | Class or Case | Bulk label mapping |
| `#[EnumColor(...)]` | Class | Bulk color mapping by category |
| `#[EnumIcon(...)]` | Class or Case | Icon mapping + default |
| `#[EnumDescription(...)]` | Class or Case | Bulk description mapping |

---

## Installation

```bash
composer require zeroboiler/enums
```

The service provider is auto-discovered via Laravel's package discovery.

**Requirements:**
- PHP 8.5+
- Laravel 13+

**Package Statistics:**
- 20 source files in `src/` (8 attributes, 1 trait, 4 infrastructure, 2 console commands, 1 cast, 1 rule, 1 exception, 1 facade, 1 service provider)
- 337 test files in `tests/` (33 fixtures)
- PHPStan Level 9 (`phpstan.neon`)
- 100% `declare(strict_types=1)` coverage
- Zero `mixed` return types in public API

---

## Quick Start

### 1. Define your enum with the trait

```php
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Description;

enum UserStatus: string
{
    use HasEnumMetadata;

    #[Label('Active User')]
    #[Color('success')]
    #[Icon('heroicon-o-check-circle')]
    #[Description('User can fully access the system')]
    case ACTIVE = 'active';

    #[Label('Pending Verification')]
    #[Color('warning')]
    case PENDING = 'pending';

    #[Label('Banned User')]
    #[Color('danger')]
    case BANNED = 'banned';
}
```

### 2. Use it everywhere

```php
// Instance methods
UserStatus::ACTIVE->label();       // 'Active User'
UserStatus::ACTIVE->color();       // 'success'
UserStatus::ACTIVE->icon();        // 'heroicon-o-check-circle'
UserStatus::ACTIVE->description(); // 'User can fully access the system'
UserStatus::ACTIVE->toValue();     // 'active'

// Comparison helpers
UserStatus::ACTIVE->is(UserStatus::PENDING);    // false
UserStatus::ACTIVE->is('ACTIVE');                // true
UserStatus::ACTIVE->isNot('BANNED');             // true
UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']);  // true
UserStatus::ACTIVE->notIn(['BANNED']);           // true

// Static bulk methods
UserStatus::forSelect();
// [['value' => 'active', 'label' => 'Active User'], ...]

UserStatus::forApi();
// [['value' => 'active', 'name' => 'ACTIVE', 'label' => 'Active User', 
//   'description' => '...', 'color' => 'success', 'icon' => 'heroicon-o-check-circle'], ...]

UserStatus::values();  // ['active', 'pending', 'banned']
UserStatus::labels();  // ['Active User', 'Pending Verification', 'Banned User']

// Reverse lookups
UserStatus::tryFromName('ACTIVE');        // UserStatus::ACTIVE
UserStatus::fromName('ACTIVE');           // UserStatus::ACTIVE
UserStatus::hasCase('ACTIVE');            // true
UserStatus::tryFromLabel('Active User');  // UserStatus::ACTIVE
UserStatus::tryFromLabel('active user');  // UserStatus::ACTIVE (case-insensitive)
```

### 3. Use the Facade (no trait required on calling side)

```php
use ZeroBoiler\Enums\Facades\Enum;

Enum::forSelect(UserStatus::class);
Enum::forApi(UserStatus::class);
Enum::tryFromLabel(UserStatus::class, 'Active User');
Enum::tryFromName(UserStatus::class, 'ACTIVE');
Enum::fromName(UserStatus::class, 'ACTIVE');
Enum::hasCase(UserStatus::class, 'ACTIVE');
Enum::values(UserStatus::class);
Enum::labels(UserStatus::class);
```

> **Note:** The target enum must use `HasEnumMetadata` trait. If not, a
> `BadMethodCallException` is thrown.

---

## Architecture

### Type System

```
┌──────────────────────────────────────────────────────┐
│                    Your Enum                          │
│  use HasEnumMetadata;                                │
├──────────────────────────────────────────────────────┤
│  Instance Methods         │  Static Methods          │
│  ─────────────────        │  ─────────────           │
│  label(): string          │  forSelect(): array      │
│  description(): ?string   │  forApi(): array         │
│  color(): string          │  values(): array         │
│  icon(): ?string          │  labels(): array         │
│  toValue(): int|string    │  tryFromLabel(): ?static │
│  is(): bool               │  tryFromName(): ?static  │
│  isNot(): bool            │  fromName(): static      │
│  in(): bool               │  hasCase(): bool         │
│  notIn(): bool            │                          │
├───────────────────────────┴──────────────────────────┤
│            HasEnumMetadata (trait)                    │
├──────────────────────────────────────────────────────┤
│         EnumMetadataResolver (internal)               │
│  - Reflection-based attribute resolution             │
│  - Resolution order: per-case > class-level > auto    │
├──────────────────────────────────────────────────────┤
│              EnumCache (singleton)                    │
│  - TTL-based cache (default 300s)                     │
│  - Per-class invalidation supported                  │
└──────────────────────────────────────────────────────┘
```

### Metadata Resolution Order

For each metadata type (label, color, icon, description), resolution follows
this priority (highest wins):

1. **Per-case attribute** — `#[Label('...')]`, `#[Color('...')]`, etc.
2. **Class-level attribute** — `#[EnumLabel(...)]`, `#[EnumColor(...)]`, etc.
3. **Auto-generated / default** — Case name → "Title Case" for labels,
   `'secondary'` for colors, `null` for descriptions and icons.

### Metadata Cache Shape

The cached metadata resolved by `EnumMetadataResolver` has the following shape.
This is the same structure stored in `EnumCache` for each enum class:

```php
// @phpstan-type EnumMetadataShape
[
    'labels'      => [int|string => string],  // case value → human-readable label
    'descriptions' => [int|string => string],  // case value → description (sparse)
    'colors'      => [int|string => string],  // case value → color name
    'icons'       => [int|string => string],  // case value → icon identifier (sparse)
]
```

For backed enums, keys are the backed values (`'active'`, `1`, `0`).
For pure enums, keys are the case names (`'ACTIVE'`, `'PENDING'`).

Arrays may be sparse — only cases with explicitly defined metadata
(via attributes or class-level mappings) have entries. The `label()`
accessor falls back to auto-generation; `color()` falls back to `'secondary'`;
`description()` and `icon()` return `null` for missing entries.

---

## Attributes Reference

### Per-Case Attributes

Used on individual enum cases. These **always override** class-level definitions.

---

#### `#[Label(string $value)]`

**Target:** `TARGET_CLASS_CONSTANT`

Sets the human-readable label for a single case.

```php
#[Label('Active User')]
case ACTIVE = 'active';
```

- **`$value`** (`string`) — Human-readable label text

---

#### `#[Color(string $value)]`

**Target:** `TARGET_CLASS_CONSTANT`

Sets the UI color for a single case.

```php
#[Color('success')]
case ACTIVE = 'active';
```

- **`$value`** (`string`) — Color name: `'success'`, `'danger'`, `'warning'`, `'info'`, or `'secondary'`

---

#### `#[Description(string $value)]`

**Target:** `TARGET_CLASS_CONSTANT`

Sets the description for a single case.

```php
#[Description('User can fully access the system')]
case ACTIVE = 'active';
```

- **`$value`** (`string`) — Human-readable description text

---

#### `#[Icon(string $value)]`

**Target:** `TARGET_CLASS_CONSTANT`

Sets the icon identifier for a single case.

```php
#[Icon('heroicon-o-check-circle')]
case ACTIVE = 'active';
```

- **`$value`** (`string`) — Icon identifier (e.g. `'heroicon-o-check-circle'`, `'fa-user'`)

---

### Class-Level Attributes

Used on the enum class itself. Define metadata for multiple cases at once.

---

#### `#[EnumLabel(array&#124;null $labels, string&#124;null $label)]`

**Target:** `TARGET_CLASS &#124; TARGET_CLASS_CONSTANT`

Maps multiple case values to labels at once. Can also be used at case level
for a single-case override via the `$label` parameter.

```php
use ZeroBoiler\Enums\Attributes\EnumLabel;

#[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
enum UserStatus: string
{
    use HasEnumMetadata;
    case ACTIVE = 'active';
    case PENDING = 'pending';  // auto-generated: "Pending"
    case BANNED = 'banned';
}
```

- **`$labels`** (`array<int&#124;string, string>&#124;null`, default `null`) — Map of case value → label (class-level)
- **`$label`** (`string&#124;null`, default `null`) — Single label override (case-level)

---

#### `#[EnumColor(list $success, list $danger, list $warning, list $info, list $secondary)]`

**Target:** `TARGET_CLASS`

Maps case values to color categories. Groups cases by their semantic color.

```php
use ZeroBoiler\Enums\Attributes\EnumColor;

#[EnumColor(
    success: ['active'],
    danger: ['banned'],
    warning: ['pending'],
)]
enum UserStatus: string
{
    use HasEnumMetadata;
    case ACTIVE = 'active';   // color: 'success'
    case PENDING = 'pending'; // color: 'warning'
    case BANNED = 'banned';   // color: 'danger'
}
```

- **`$success`** (`list<int&#124;string>`, default `[]`) — Case values colored as success
- **`$danger`** (`list<int&#124;string>`, default `[]`) — Case values colored as danger
- **`$warning`** (`list<int&#124;string>`, default `[]`) — Case values colored as warning
- **`$info`** (`list<int&#124;string>`, default `[]`) — Case values colored as info
- **`$secondary`** (`list<int&#124;string>`, default `[]`) — Case values colored as secondary

---

#### `#[EnumIcon(array $icons, string&#124;null $default)]`

**Target:** `TARGET_CLASS | TARGET_CLASS_CONSTANT`

Maps case values to icon identifiers with an optional default fallback.
Can also be used at case level where `$default` acts as the override value.

```php
use ZeroBoiler\Enums\Attributes\EnumIcon;

#[EnumIcon(
    icons: ['active' => 'heroicon-o-check-circle', 'banned' => 'heroicon-o-x-circle'],
    default: 'heroicon-o-question-mark-circle',
)]
enum UserStatus: string
{
    use HasEnumMetadata;
    case ACTIVE = 'active';   // icon: 'heroicon-o-check-circle'
    case PENDING = 'pending'; // icon: 'heroicon-o-question-mark-circle' (default)
    case BANNED = 'banned';   // icon: 'heroicon-o-x-circle'
}
```

Case-level usage (alternative to `#[Icon]`):

```php
#[EnumIcon(default: 'heroicon-o-check-circle')]
case ACTIVE = 'active'; // icon: 'heroicon-o-check-circle'
```

- **`$icons`** (`array<int&#124;string, string>`, default `[]`) — Map of case value → icon identifier
- **`$default`** (`string&#124;null`, default `null`) — Default icon for cases without specific icon (class-level), or per-case override (case-level)

---

#### `#[EnumDescription(array&#124;null $descriptions, string&#124;null $description)]`

**Target:** `TARGET_CLASS &#124; TARGET_CLASS_CONSTANT`

Maps multiple case values to descriptions. Can also be used at case level
for a single-case override.

```php
use ZeroBoiler\Enums\Attributes\EnumDescription;

#[EnumDescription(descriptions: ['active' => 'Fully accessible', 'banned' => 'Account suspended'])]
enum UserStatus: string
{
    use HasEnumMetadata;
    case ACTIVE = 'active';
    case PENDING = 'pending';  // description: null
    case BANNED = 'banned';
}
```

- **`$descriptions`** (`array<int&#124;string, string>&#124;null`, default `null`) — Map of case value → description (class-level)
- **`$description`** (`string&#124;null`, default `null`) — Single description override (case-level)

---

## API Reference

### Trait: `HasEnumMetadata`

**Namespace:** `ZeroBoiler\Enums\Concerns`

The core trait that provides all enum metadata functionality. Add `use HasEnumMetadata;`
inside your enum class.

#### Instance Methods

- **`label(): string`** — Human-readable label. Resolved from per-case `#[Label]` > class-level `#[EnumLabel]` > auto-generated from case name.

- **`description(): ?string`** — Description text, or `null` if not defined.

- **`color(): string`** — UI color name. Defaults to `'secondary'`.

- **`icon(): ?string`** — Icon identifier, or `null` if not defined.

- **`toValue(): int&#124;string`** — Backed value for backed enums, case name for pure enums.

- **`is(self&#124;string $case): bool`** — Strict identity match. Accepts enum instance or case name string.

- **`isNot(self&#124;string $case): bool`** — Negation of `is()`.

- **`in(array<self&#124;string> $cases): bool`** — True if this case matches any in the list.

- **`notIn(array<self&#124;string> $cases): bool`** — Negation of `in()`.

#### Static Methods

- **`forSelect(): list<array{value: int&#124;string, label: string}>`** — Dropdown-ready value/label pairs.

- **`forApi(): list<array{value: int&#124;string, name: string, label: string, description: ?string, color: string, icon: ?string}>`** — Full metadata for API responses.

- **`values(): list<string&#124;int>`** — All backed values or case names.

- **`labels(): list<string>`** — All labels in declaration order.

- **`tryFromName(string $name): ?static`** — Find case by name, or `null`.

- **`fromName(string $name): static`** — Find case by name, throws `InvalidEnumException` if not found.

- **`tryFromLabel(string $label): ?static`** — Find case by label (case-insensitive), or `null`.

- **`hasCase(string $name): bool`** — Check if a case name exists.

---

### Class: `EnumManager`

**Namespace:** `ZeroBoiler\Enums`
**Type:** `final readonly class`

Stateless proxy that delegates to enum classes using `HasEnumMetadata`.
Accessed via the `Enum` facade or dependency injection.

```php
// Via Facade
use ZeroBoiler\Enums\Facades\Enum;
Enum::forSelect(UserStatus::class);

// Via DI
public function __construct(
    private readonly \ZeroBoiler\Enums\EnumManager $enumManager,
) {}
$this->enumManager->forSelect(UserStatus::class);
```

All methods accept `class-string<UnitEnum>` as the first parameter and
delegate to the corresponding static method on the enum class:

- `forSelect(string $enumClass): array`
- `forApi(string $enumClass): array`
- `tryFromLabel(string $enumClass, string $label): ?UnitEnum`
- `tryFromName(string $enumClass, string $name): ?UnitEnum`
- `fromName(string $enumClass, string $name): UnitEnum`
- `hasCase(string $enumClass, string $name): bool`
- `values(string $enumClass): array`
- `labels(string $enumClass): array`

---

### Class: `EnumCache`

**Namespace:** `ZeroBoiler\Enums`
**Type:** `final class` (singleton)

TTL-based cache for enum metadata. Automatically used by
`EnumMetadataResolver` — you rarely need to interact with it directly.

- **`getInstance(): self`** — Get the singleton instance
- **`has(string $enumClass): bool`** — Check if valid cached metadata exists (respects TTL)
- **`get(string $enumClass): array`** — Get cached metadata (throws `OutOfBoundsException` if not cached)
- **`set(string $enumClass, array $metadata): void`** — Store metadata with timestamp
- **`setTtl(int $ttl): void`** — Set cache TTL in seconds (0 = disabled, negative clamped to 0)
- **`getTtl(): int`** — Get current TTL
- **`clear(): void`** — Clear all cached entries
- **`clearClass(string $enumClass): void`** — Clear cache for one enum class
- **`flush(): void`** — Static alias for `clear()`
- **`resetInstance(): void`** — **Test-only.** Destroy the singleton (never use in production)

---

### Class: `EnumMetadataResolver`

**Namespace:** `ZeroBoiler\Enums\Support`
**Type:** `final class`

Internal resolver. Not part of the public API. Use `HasEnumMetadata`
trait methods instead.

- **`resolve(string $enumClass): array`** — Resolve all metadata for an enum class (cached)
- **`invalidate(string $enumClass): void`** — Invalidate cache for a specific enum class
- **`invalidateAll(): void`** — Invalidate all cached metadata

---

### Class: `EnumCast`

**Namespace:** `ZeroBoiler\Enums\Casts`

Laravel Eloquent cast for storing/retrieving backed enum values in database columns.

```php
use ZeroBoiler\Enums\Casts\EnumCast;

class User extends Model
{
    protected $casts = [
        'status' => UserStatus::class,           // Native Laravel 9+ enum cast
        'status' => EnumCast::of(UserStatus::class),  // With extended validation
    ];
}
```

Implements `CastsAttributes`. Returns `null` silently for values that don't match
any enum case. The `serialize()` method returns the backed value for JSON output.

---

### Class: `EnumRule`

**Namespace:** `ZeroBoiler\Enums\Rules`
**Type:** `final readonly class`

Laravel validation rule for enum values. Supports both backed and pure enums.

```php
use ZeroBoiler\Enums\Rules\EnumRule;

// In your FormRequest
public function rules(): array
{
    return [
        'status' => ['required', EnumRule::for(UserStatus::class)],
        'role' => [EnumRule::for(UserStatus::class)->nullable()],  // optional field
    ];
}
```

- **`for(string $enumClass): self`** — Named constructor
- **`nullable(): self`** — Returns a new instance that allows null values
- **`validate(string $attribute, mixed $value, Closure $fail): void`** — Validation logic

---

### Facade: `Enum`

**Namespace:** `ZeroBoiler\Enums\Facades`
**Type:** `final class extends Facade`

Laravel facade proxy for `EnumManager`. Resolved from the container via
`'zeroboiler.enum'` key.

---

### Exception: `InvalidEnumException`

**Namespace:** `ZeroBoiler\Enums\Exceptions`
**Type:** `final class extends Exception`

Thrown when an enum case lookup fails.

```php
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;

try {
    UserStatus::fromName('NONEXISTENT');
} catch (InvalidEnumException $e) {
    // $e->getMessage() → "Case name [NONEXISTENT] does not exist on enum [UserStatus]."
}
```

Named constructors:

- **`value(string $enumClass, int&#124;string&#124;null $value): self`** — For invalid backed value lookups
- **`forName(string $enumClass, string $name): self`** — For invalid case name lookups

---

## Artisan Commands

### `php artisan zeroboiler:enum-inspect {class}`

Display all metadata for an enum in a table format.

```bash
php artisan zeroboiler:enum-inspect "App\Enums\UserStatus"
```

Output:

```
Enum: UserStatus

+----------+---------+---------------------+----------+-------------------------------+----------------------------------+
| Name     | Value   | Label               | Color    | Icon                          | Description                      |
+----------+---------+---------------------+----------+-------------------------------+----------------------------------+
| ACTIVE   | active  | Active User         | success  | heroicon-o-check-circle       | User can fully access the system |
| PENDING  | pending | Pending Verification | warning  | —                             | —                                |
| BANNED   | banned  | Banned User         | danger   | —                             | —                                |
+----------+---------+---------------------+----------+-------------------------------+----------------------------------+
```

### `php artisan zeroboiler:enum-test {class} {--dir=}`

Generate a comprehensive Pest test file for an enum.

```bash
php artisan zeroboiler:enum-test "App\Enums\UserStatus"
php artisan zeroboiler:enum-test "App\Enums\UserStatus" --dir=tests/Unit/Enums
```

Generated tests cover:

- Case existence and count
- `forSelect()` and `forApi()` bulk methods
- Per-case label, color, icon, description accessors
- Comparison methods (`is`, `isNot`, `in`, `notIn`) with instances and strings
- Reverse lookups (`tryFromLabel`, `tryFromName`, `fromName`, `hasCase`)
- `fromName()` throw behavior for invalid names
- Value type consistency checks

---

## Advanced Usage

### Pure Enums (no backing type)

```php
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Attributes\Color;

enum FeatureFlag
{
    use HasEnumMetadata;

    #[Label('New Dashboard')]
    #[Color('info')]
    case NEW_DASHBOARD;

    case LEGACY_MODE;  // auto-label: "Legacy Mode", color: 'secondary'
}

FeatureFlag::NEW_DASHBOARD->toValue(); // 'NEW_DASHBOARD' (case name)
FeatureFlag::NEW_DASHBOARD->label();   // 'New Dashboard'
FeatureFlag::values();                 // ['NEW_DASHBOARD', 'LEGACY_MODE']
```

### Int-Backed Enums

```php
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Attributes\Color;

enum Priority: int
{
    use HasEnumMetadata;

    #[Label('Low')]      #[Color('secondary')] case LOW = 0;
    #[Label('Medium')]   #[Color('warning')]   case MEDIUM = 1;
    #[Label('High')]     #[Color('danger')]    case HIGH = 2;
    #[Label('Critical')] #[Color('danger')]    case CRITICAL = 3;
}

Priority::HIGH->toValue(); // 2
Priority::values();         // [0, 1, 2, 3]
```

### Class-Level Bulk Metadata

```php
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\Label;

#[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
#[EnumColor(success: ['active'], danger: ['banned'], warning: ['pending'])]
#[EnumIcon(icons: ['active' => 'heroicon-o-check-circle'], default: 'heroicon-o-minus')]
enum UserStatus: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';   // label: 'Active User', color: 'success', icon: 'heroicon-o-check-circle'
    case PENDING = 'pending'; // label: 'Pending', color: 'warning', icon: 'heroicon-o-minus'
    case BANNED = 'banned';   // label: 'Banned User', color: 'danger', icon: 'heroicon-o-minus'

    // Per-case override still wins:
    #[Label('Suspended Account')]
    case SUSPENDED = 'suspended'; // label: 'Suspended Account', color: 'secondary', icon: 'heroicon-o-minus'
}
```

### Blade View Rendering

Enums are first-class PHP objects — use them directly in Blade:

```blade
{{-- Status badge with color --}}
<span class="badge badge-{{ $user->status->color() }}">
    {{ $user->status->label() }}
</span>

{{-- Icon from metadata --}}
<x-heroicon name="{{ $user->status->icon() ?? 'heroicon-o-question-mark-circle' }}" class="w-4 h-4" />

{{-- Tooltip with description --}}
<span title="{{ $user->status->description() }}">
    {{ $user->status->label() }}
</span>

{{-- Dropdown select from forSelect() --}}
<select name="status">
    @foreach (UserStatus::forSelect() as $option)
        <option value="{{ $option['value'] }}" {{ old('status') === $option['value'] ? 'selected' : '' }}>
            {{ $option['label'] }}
        </option>
    @endforeach
</select>

{{-- Filter buttons with color --}}
@foreach (UserStatus::forApi() as $status)
    <button
        class="btn btn-{{ $status['color'] }}"
        wire:click="filter('{{ $status['value'] }}')"
    >
        {{ $status['label'] }}
    </button>
@endforeach
```

### API Resource Output

```php
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => [
                'value' => $this->status->toValue(),
                'label' => $this->status->label(),
                'color' => $this->status->color(),
                'icon' => $this->status->icon(),
            ],
        ];
    }
}

// Or expose all enum options for a settings/config endpoint:
Route::get('/api/enums/statuses', fn () => UserStatus::forApi());
```

### Livewire Component Integration

```php
use Livewire\Component;

class UserStatusFilter extends Component
{
    public string $filter = '';

    public function render()
    {
        $users = User::query()
            ->when($this->filter !== '', function ($query) {
                $query->where('status', UserStatus::from($this->filter)->toValue());
            })
            ->get();

        return view('livewire.user-status-filter', [
            'users' => $users,
            'options' => UserStatus::forSelect(),
            'statuses' => UserStatus::forApi(),
        ]);
    }
}
```

### Conditional Business Logic

```php
// Fluent state checks — replaces switch/case and match() blocks
if ($order->status->is(OrderStatus::PENDING)) {
    $order->process();
}

// Group checks for authorization
if ($user->role->in([Role::ADMIN, Role::SUPER_ADMIN])) {
    return $this->adminDashboard();
}

// Exclusion logic
if ($user->status->notIn([UserStatus::BANNED, UserStatus::DELETED])) {
    $user->sendNotification();
}

// Color-based UI decisions
$badgeClass = match ($user->status->color()) {
    'success' => 'bg-green-100 text-green-800',
    'danger'  => 'bg-red-100 text-red-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info'    => 'bg-blue-100 text-blue-800',
    default    => 'bg-gray-100 text-gray-800',
};
```

### Database Query Scopes

```php
// In your model or a scope class
public function scopeWithStatus(Builder $query, string|int $statusValue): Builder
{
    $status = UserStatus::tryFromLabel($statusValue)
        ?? UserStatus::tryFrom($statusValue);

    return $query->where('status', $status?->toValue());
}

// Usage — accepts both label strings and raw values
User::withStatus('Active User')->get();
User::withStatus('active')->get();
```

### Eloquent Model Integration

```php
use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Rules\EnumRule;

class User extends Model
{
    protected $casts = [
        'status' => EnumCast::of(UserStatus::class),
    ];

    public function rules(): array
    {
        return [
            'status' => ['required', EnumRule::for(UserStatus::class)],
        ];
    }
}

// Usage
$user->status = UserStatus::ACTIVE;
$user->save();              // stores 'active' in DB
$user->status->label();     // 'Active User'
$user->status->color();     // 'success'
```

### Cache Configuration

```php
use ZeroBoiler\Enums\EnumCache;

// Get the singleton
$cache = EnumCache::getInstance();

// Configure TTL (default: 300 seconds)
$cache->setTtl(60);  // 1 minute
$cache->setTtl(0);   // Disable caching

// Manual invalidation (rarely needed)
EnumCache::flush();                      // Clear all
$cache->clearClass(UserStatus::class);  // Clear one enum
```

> **Note:** In `local` and `testing` environments, the service provider
> automatically sets TTL to 2 seconds so code changes are picked up
> immediately. In production, the default 300s TTL is used.

---

## Design Principles

- **Attribute-driven** — Metadata defined declaratively via PHP 8 attributes, not match() blocks
- **Convention over configuration** — Auto-generates labels from case names, defaults to 'secondary' color
- **Three-level resolution** — Per-case attribute > class-level attribute > auto-generated default
- **Type-safe** — PHPStan Level 9, `declare(strict_types=1)`, no `mixed` in public API
- **Zero state** — `EnumManager` is `final readonly` (stateless); `EnumCache` is the only stateful component
- **Laravel-native** — Facade, service provider, Eloquent cast, validation rule, artisan commands
- **All enum types** — String-backed, int-backed, and pure enums supported identically

---

## PHP 8.5 Features

| Feature | Where Used |
|---|---|
| `readonly` classes | `EnumManager` |
| `readonly` promoted properties | All 8 attribute classes, `EnumCast`, `EnumRule` |
| `#[\Override]` attribute | `InvalidEnumException::__toString()`, `Enum` facade |
| `never` return type | `EnumCache::__clone()`, `EnumCache::__wakeup()`, `EnumCache::__serialize()`, `EnumCache::__unserialize()` |
| Named arguments | `EnumLabel`, `EnumColor`, `EnumIcon`, `EnumDescription` constructors |
| `match` expressions | `EnumMetadataResolver::buildMetadata()` (color map iteration) |
| `static` return types | `HasEnumMetadata::fromName()`, `tryFromLabel()`, `tryFromName()`, `forSelect()`, `forApi()` |
| `get_debug_type()` | `EnumCast::set()`, `EnumCast::serialize()` |
| Singleton pattern | `EnumCache` (private constructor, `getInstance()`) |

---

## Source Code Structure

```
src/
├── Attributes/
│   ├── Color.php              # Per-case color override
│   ├── Description.php        # Per-case description override
│   ├── Icon.php               # Per-case icon override
│   ├── Label.php              # Per-case label override
│   ├── EnumColor.php          # Class-level color mapping
│   ├── EnumDescription.php    # Class-level description mapping
│   ├── EnumIcon.php           # Class-level icon mapping + default
│   └── EnumLabel.php          # Class-level label mapping
├── Concerns/
│   └── HasEnumMetadata.php    # Core trait — all public API methods
├── Casts/
│   └── EnumCast.php           # Eloquent cast for backed enums
├── Console/
│   └── Commands/
│       ├── InspectEnumCommand.php    # zeroboiler:enum-inspect
│       └── MakeEnumTestCommand.php   # zeroboiler:enum-test
├── Exceptions/
│   └── InvalidEnumException.php  # Thrown on invalid case/value lookup
├── Facades/
│   └── Enum.php               # Laravel facade for EnumManager
├── Rules/
│   └── EnumRule.php           # Validation rule for backed + pure enums
├── Support/
│   ├── EnumMetadataResolver.php  # Reflection-based attribute resolver
│   └── EnumTestGenerator.php     # Pest test code generator
├── EnumCache.php              # TTL-based singleton metadata cache
├── EnumManager.php            # Stateless proxy (DI/facade backing)
└── EnumsServiceProvider.php   # Laravel service provider
```

---

## Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| `label()` returns 'User Status' instead of custom label | Attribute not resolving | Ensure the attribute is imported: `use ZeroBoiler\Enums\Attributes\Label;` |
| `forSelect()` returns case names instead of backed values | Pure enum (no backing type) | This is expected — pure enums use case names as values |
| `tryFromLabel()` returns null for an existing label | Case sensitivity or whitespace | `tryFromLabel()` is case-insensitive but whitespace-sensitive |
| `fromName('active')` throws `InvalidEnumException` | Case name, not value | `fromName()` matches case **names** (e.g. 'ACTIVE'), not backed values |
| `toValue()` returns the case name for a backed enum | Trait not applied | Ensure the enum has `use HasEnumMetadata;` |
| `Enum::forSelect(UserStatus::class)` throws `BadMethodCallException` | Trait not on enum | The target enum must use `HasEnumMetadata` trait |
| `EnumCast::of()` returns null for valid value | Wrong enum type passed | `EnumCast` only works with **backed** enums (not pure enums) |
| `zeroboiler:enum-inspect` shows '—' for all metadata | Missing trait | The enum must use `use HasEnumMetadata;` to resolve metadata |
| Metadata is stale after code change | Cache TTL | In production, TTL is 300s. Use `EnumCache::flush()` or restart the process |
| `EnumRule` rejects valid int for int-backed enum | Type mismatch | The rule validates the PHP type matches the backing type (string→string, int→int) |

### FAQ

**Q: Can I use this package without Laravel?**
A: The core trait (`HasEnumMetadata`), attributes, and cache work without Laravel. However, `EnumCast`, `EnumRule`, `EnumManager`, the facade, and console commands require Laravel.

**Q: Does it work with PHP 8.1+?**
A: The package targets PHP 8.5+ and uses `#[\Override]` and `readonly` classes, which require PHP 8.3+ and PHP 8.2+ respectively. It will not work on PHP 8.1.

**Q: Can I use multiple attributes on the same case?**
A: Yes. You can stack any combination of `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]` on the same case:

```php
#[Label('Active User')]
#[Color('success')]
#[Icon('heroicon-o-check-circle')]
#[Description('User can fully access the system')]
case ACTIVE = 'active';
```

**Q: How do class-level and per-case attributes interact?**
A: Per-case attributes always win. The resolution order is:
1. Per-case `#[Label('...')]` (highest priority)
2. Class-level `#[EnumLabel(labels: [...])]`
3. Auto-generated from case name (lowest priority)

**Q: Can I use enum metadata in Blade views?**
A: Yes. Since enums are first-class objects in PHP, you can use them directly:

```blade
<span class="badge-{{ $user->status->color() }}">
    {{ $user->status->label() }}
</span>
```

**Q: How does caching work in Octane/Swoole?**
A: The service provider automatically registers event listeners for `octane.terminate` and `laravel.flush` to clear the cache between requests. No manual configuration needed.

---

## Performance & Caching

### How Caching Works

Enum metadata resolution uses reflection, which is expensive. To avoid repeated
reflection on every property access, `EnumMetadataResolver` caches results in
`EnumCache` (a process-wide singleton).

```
First access:  EnumMetadataResolver::resolve() → reflection → build metadata → cache
Subsequent:   EnumCache::has() → TTL check → cache hit → return cached data
```

### TTL Behavior

| Environment | TTL | Behavior |
|------------|-----|----------|
| **Production** | 300s (5 min) | Metadata cached for 5 minutes. Flush via `EnumCache::flush()` on deploy. |
| **Local** | 2s | Auto-set by service provider. Code changes detected on next request. |
| **Testing** | 2s | Auto-set by service provider. Fresh metadata between test cases. |
| **Custom** | 0 (disabled) | `EnumCache::getInstance()->setTtl(0)` — every call re-resolves. |

### When to Worry About Cache

- **Long-running processes** (Octane, Swoole, RoadRunner): The service provider
  automatically listens for `octane.terminate` and `laravel.flush` events to
  clear the cache between requests. No manual configuration needed.
- **Deployment**: If you use zero-downtime deployments with rolling restarts,
  cached metadata from the old code version may serve for up to 300s. Call
  `EnumCache::flush()` in a deployment hook or use the 2s TTL in production.
- **Memory**: Each cached enum class stores ~4 arrays (labels, descriptions, colors,
  icons). For an app with 100 enum classes averaging 10 cases each, the cache
  uses ~50KB — negligible.

### Thread Safety

| Component | Thread Safety | Notes |
|-----------|--------------|-------|
| `EnumCache` | **Per-process** | Static singleton. Each PHP-FPM/Octane worker has its own instance. |
| `EnumMetadataResolver` | **Stateless** | Pure static methods. No mutable instance state. Thread-safe. |
| `EnumManager` | **Stateless** | `final readonly` — zero mutable state. Thread-safe. |
| `HasEnumMetadata` (trait) | **Stateless** | All methods delegate to `EnumMetadataResolver`. Thread-safe. |
| `EnumRule` | **Stateless** | `final readonly` — no mutable state. Thread-safe. |
| `EnumCast` | **Stateless** | `final` with `readonly` property. Thread-safe. |

### Performance Tips

1. **Use class-level attributes** (`#[EnumLabel]`, `#[EnumColor]`) instead of
   per-case attributes when multiple cases share the same metadata. This reduces
   the number of reflection attribute lookups during `buildMetadata()`.

2. **Don't call `resetInstance()` in production.** It destroys the cache,
   forcing re-resolution on next access. Only use in test teardown.

3. **`forApi()` vs individual calls:** If you need multiple pieces of metadata
   for all cases (e.g. in an API response), use `forApi()` once rather than
   calling `label()`, `color()`, `icon()` on each case individually. `forApi()`
   benefits from a single cache lookup.

4. **`tryFromLabel()` is O(n):** It iterates all cases and compares labels.
   For enums with hundreds of cases, consider building a reverse lookup map
   at application boot time if this is a hot path.

---

## Changelog

See [GitHub Releases](https://github.com/zeroboiler/enums/releases).

## License

Proprietary. See [license file](LICENSE).
