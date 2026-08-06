<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * CamelCase enum fixture — tests generateLabel() with non-SCREAMING_SNAKE_CASE names.
 *
 *   isActive → Is Active
 *   isAdmin → Is Admin
 */
enum CamelCaseRole: string
{
    use HasEnumMetadata;

    case isActive = 'is_active';
    case isAdmin = 'is_admin';
    case isModerator = 'is_moderator';
    case isBanned = 'is_banned';
}
