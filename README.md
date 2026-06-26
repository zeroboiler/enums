# NovaForge Enums

Zero-boilerplate smart enum system for Laravel.

## Features

- **Attribute-based metadata** — `#[Label]`, `#[Color]`, `#[Icon]`, `#[Description]`
- **Auto-generated labels** — SCREAMING_SNAKE_CASE → Title Case automatically
- **Class-level defaults** — `#[EnumColor(success: ['active'], danger: ['banned'])]`
- **Eloquent auto-cast** — works with any backed enum
- **Validation rule** — `EnumRule::for(UserStatus::class)`
- **Bulk helpers** — `forSelect()`, `forApi()`, `values()`, `labels()`
- **Reverse lookup** — `tryFromLabel('Active User')`
- **CLI tools** — `novaforge:enum-test`, `novaforge:enum-inspect`

## Usage

```php
use NovaForge\Enums\Attributes\EnumColor;
use NovaForge\Enums\Attributes\Color;
use NovaForge\Enums\Attributes\Label;
use NovaForge\Enums\Attributes\Icon;
use NovaForge\Enums\Attributes\Description;
use NovaForge\Enums\Concerns\HasEnumMetadata;

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

### Reverse Lookup

```php
UserStatus::tryFromLabel('Active User'); // UserStatus::ACTIVE
```

### Eloquent Cast

```php
protected $casts = [
    'status' => UserStatus::class,  // works automatically
];
```

### Validation

```php
use NovaForge\Enums\Rules\EnumRule;

'status' => ['required', EnumRule::for(UserStatus::class)],
```

### CLI Commands

```bash
php artisan novaforge:enum-test "App\Enums\UserStatus"
php artisan novaforge:enum-inspect "App\Enums\UserStatus"
```

## License

Proprietary — © NovaForge
