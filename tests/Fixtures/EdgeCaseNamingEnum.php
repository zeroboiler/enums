<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Fixture: enum with unusual case names for label generation edge case testing.
 *
 * Tests generateLabel() behavior with:
 * - Single-letter case names
 * - Numeric-only case names (as strings)
 * - Multi-word SCREAMING_SNAKE_CASE
 * - Short case names
 * - Mixed-case single-word names
 */
enum EdgeCaseNamingEnum: string
{
    use HasEnumMetadata;

    case X = 'x';

    case AB = 'ab';

    case A1 = 'a1';

    case UNDER_SCORE__ = 'under_score__';

    case TRIPLE___WORD = 'triple___word';

    case NUMBER_2 = 'number_2';

    case SINGLE = 'single';

    case LOWER = 'lower';
}
