<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Int-backed enum with class-level EnumColor and per-case overrides.
 *
 * Tests that EnumColor works correctly with integer-backed values
 * and per-case Color overrides take precedence.
 */
#[EnumColor(success: [1, 4], danger: [3], warning: [2])]
enum IntStatusWithColor: int
{
    use HasEnumMetadata;

    case ACTIVE = 1;

    case PENDING = 2;

    #[Color('danger')]
    case BANNED = 3;

    case DRAFT = 4;
}
