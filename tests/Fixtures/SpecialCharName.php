<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum with special characters in case names.
 */
enum SpecialCharName: string
{
    use HasEnumMetadata;

    case WITH_DASH = 'with-dash';
    case WITH_NUMBER_2 = 'with-number-2';
    case UMLAUT_Ä = 'umlaut-ae';
}
