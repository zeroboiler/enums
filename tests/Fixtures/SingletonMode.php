<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Edge-case fixture: single-case enum (singleton pattern).
 *
 * Tests:
 * - Enum with exactly one case
 * - forSelect() returns single-element array
 * - forApi() returns single-element array
 * - tryFromLabel works for the single case
 * - hasCase returns true for the single case, false for non-existent
 * - in() / notIn() work with single-element arrays
 */
enum SingletonMode
{
    use HasEnumMetadata;
    case INSTANCE;
}
