<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Enum facade — runtime access to enum metadata via the EnumManager singleton.
 *
 * Provides a clean interface for enum operations without requiring
 * the calling code to use the HasEnumMetadata trait directly.
 *
 *   Enum::forSelect(UserStatus::class);
 *   Enum::forApi(UserStatus::class);
 *   Enum::tryFromLabel(UserStatus::class, 'Active User');
 *
 * @see \ZeroBoiler\Enums\EnumManager For the underlying singleton implementation
 */
final class Enum extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return 'zeroboiler.enum';
    }
}
