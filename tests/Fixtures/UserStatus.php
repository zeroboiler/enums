<?php

declare(strict_types=1);

namespace NovaForge\Enums\Tests\Fixtures;

use NovaForge\Enums\Attributes\Color;
use NovaForge\Enums\Attributes\Description;
use NovaForge\Enums\Attributes\EnumColor;
use NovaForge\Enums\Enums;
use NovaForge\Enums\Attributes\Icon;
use NovaForge\Enums\Attributes\Label;
use NovaForge\Enums\Concerns\HasEnumMetadata;

/**
 * Full-featured test enum — uses class-level and per-case attributes.
 */
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
