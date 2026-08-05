# ZeroBoiler Enums

Zero-boilerplate smart enum system for Laravel — attribute-based metadata,
auto-casting, validation, serialization, and CLI tooling.

## Installation

```bash
composer require zeroboiler/enums
```

The package auto-registers via Laravel's package discovery. No manual configuration needed.

**Requirements:**
- PHP 8.5+
- Laravel 13+

## Type System

ZeroBoiler Enums works with **both** PHP enum types:

| Type | Backing | Use Case |
|------|---------|----------|
| **Backed Enum (string)** | `enum Foo: string` | Database columns, API payloads — most common |
| **Backed Enum (int)** | `enum Bar: int` | Status codes, priority levels, flags |
| **Pure Enum** | `enum Baz` | State machines, feature flags without storage |

All metadata features (`label()`, `color()`, `icon()`, `description()`) work identically
across both backed and pure enums. For backed enums, `forSelect()` and `values()` return
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
- **CLI tools** — `zeroboiler:enum-test`, `zeroboiler:enum-inspect`

## Usage

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
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';

    #[Color('danger')]
    case BANNED = 'banned';
}
```

### Accessors

```php
UserStatus::ACTIVE->label();       // "Active User"
UserStatus::INACTIVE->label();     // "Inactive" (auto-generated)
UserStatus::ACTIVE->color();       // "success"
UserStatus::BANNED->color();       // "danger"
UserStatus::ACTIVE->icon();        // "heroicon-o-check-circle"
UserStatus::ACTIVE->description(); // "User can fully access the system"
```

### Bulk Methods

```php
UserStatus::forSelect();
// [['value' => 'active', 'label' => 'Active User'], ...]

UserStatus::forApi();
// [['value' => 'active', 'name' => 'ACTIVE', 'label' => 'Active User', 'color' => 'success', 'icon' => '...', 'description' => '...'], ...]

UserStatus::values();    // ['active', 'inactive', 'pending', 'suspended', 'banned']
UserStatus::labels();    // ['Active User', 'Inactive', ...]
```

### Lookup

```php
// By label (case-insensitive)
UserStatus::tryFromLabel('Active User'); // UserStatus::ACTIVE

// By case name
UserStatus::tryFromName('ACTIVE');      // UserStatus::ACTIVE
UserStatus::fromName('ACTIVE');         // UserStatus::ACTIVE (throws InvalidEnumException)
UserStatus::hasCase('ACTIVE');          // true
UserStatus::hasCase('UNKNOWN');         // false
```

### Eloquent Cast

```php
protected $casts = [
    'status' => UserStatus::class,  // works automatically
];
```

### Validation

```php
use ZeroBoiler\Enums\Rules\EnumRule;

'status' => ['required', EnumRule::for(UserStatus::class)],

// Nullable field
'status' => [EnumRule::for(UserStatus::class)->nullable()],
```

### CLI Commands

```bash
php artisan zeroboiler:enum-test "App\Enums\UserStatus"
php artisan zeroboiler:enum-inspect "App\Enums\UserStatus"
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

### Integer-Backed Enum Example

```php
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
FeatureFlag::TWO_FACTOR_AUTH->value;        // NOT AVAILABLE (pure enum)
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

| Attribute | Level | Description |
|-----------|-------|-------------|
| `#[EnumColor(...)]` | Class + Case | Map case values to UI colors (`success`, `danger`, `warning`, `info`, `secondary`) |
| `#[EnumLabel(labels: [...])]` | Class + Case | Set labels for multiple cases at once (class-level) or a single case (case-level) |
| `#[EnumDescription(descriptions: [...])]` | Class + Case | Set descriptions for multiple cases at once (class-level) or a single case (case-level) |
| `#[EnumIcon(default: '...')]` | Class + Case | Set a default icon for all cases (class-level) or override per case |
| `#[Label('...')]` | Case | Human-readable label for a single case (overrides class-level) |
| `#[Color('...')]` | Case | UI color for a single case (overrides class-level) |
| `#[Icon('...')]` | Case | Icon for a single case (overrides class-level) |
| `#[Description('...')]` | Case | Description for a single case (overrides class-level) |

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

## License

Proprietary — © ZeroBoiler
