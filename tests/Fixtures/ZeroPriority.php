<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Int-backed enum with a zero value for edge case testing.
 */
enum ZeroPriority: int
{
    use HasEnumMetadata;

    case NONE = 0;
    case LOW = 1;
    case HIGH = 2;
}
