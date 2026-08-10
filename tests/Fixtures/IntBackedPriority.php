<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Integer-backed enum fixture for testing int key metadata resolution.
 *
 * Verifies that class-level and per-case metadata work correctly
 * with int backing values (not just strings).
 */
#[EnumColor(success: [3, 4], danger: [1], warning: [2])]
#[EnumDescription(descriptions: [
    1 => 'Critical priority — immediate action required',
    3 => 'Low priority — handle when convenient',
])]
#[EnumLabel(labels: [
    1 => 'Critical Priority',
    3 => 'Low Priority',
])]
#[EnumIcon(default: 'heroicon-o-flag')]
enum IntBackedPriority: int
{
    use HasEnumMetadata;

    #[Color('danger')]
    case CRITICAL = 1;

    #[Color('warning')]
    #[Label('High Priority')]
    case HIGH = 2;

    #[Color('success')]
    case LOW = 3;

    case NONE = 4;
}
