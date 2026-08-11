<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Test fixture for edge cases with int-backed enums that have a zero value
 * AND class-level attributes (unlike ZeroPriority which has no attributes).
 *
 * Zero-backed enums are particularly tricky because:
 * - `0` is falsy in PHP, which can cause issues with loose comparisons
 * - `in_array(0, [...])` can match string keys unexpectedly
 * - Cache key type consistency must handle zero values correctly
 * - Class-level attributes must correctly map zero-backed values
 */
#[EnumColor(success: [1], danger: [2], secondary: [0])]
#[EnumLabel(labels: [0 => 'None', 1 => 'Low Priority', 2 => 'High Priority'])]
enum ZeroBackedPriority: int
{
    use HasEnumMetadata;

    case NONE = 0;
    case LOW = 1;
    case HIGH = 2;
}
