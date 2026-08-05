# ZeroBoiler Enums

Zero-boilerplate smart enum system for Laravel.

## Installation

```bash
composer require zeroboiler/enums
```

The package auto-registers via Laravel's package discovery. No manual configuration needed.

**Requirements:**
- PHP 8.5+
- Laravel 13+

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
```

### CLI Commands

```bash
php artisan zeroboiler:enum-test "App\Enums\UserStatus"
php artisan zeroboiler:enum-inspect "App\Enums\UserStatus"
```

## License

Proprietary — © ZeroBoiler
