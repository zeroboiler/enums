<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum fixture for testing class-level EnumIcon with per-case override.
 */
#[EnumIcon(default: 'heroicon-o-circle-question-mark')]
enum OverriddenIconRole: string
{
    use HasEnumMetadata;

    #[Icon('heroicon-o-user')]
    case ADMIN = 'admin';

    case VIEWER = 'viewer';
}
