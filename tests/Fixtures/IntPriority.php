<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Fixture: Int-backed enum with auto-generated labels only (no attributes at all).
 *
 * Tests:
 * - Int-backed enum without any class-level or per-case attributes
 * - Auto-generated labels from SCREAMING_SNAKE_CASE names
 * - Default color fallback to 'secondary'
 * - Default icon and description fallback to null
 * - values() returns int values, not case names
 * - forSelect() uses int as value
 */
enum IntPriority: int
{
    use HasEnumMetadata;

    case LOW = 1;
    case MEDIUM = 5;
    case HIGH = 10;
    case CRITICAL = 99;
}
