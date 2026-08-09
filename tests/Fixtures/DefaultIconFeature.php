<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum fixture for testing class-level EnumIcon default.
 */
#[EnumIcon(default: 'heroicon-o-circle-question-mark')]
enum DefaultIconFeature: string
{
    use HasEnumMetadata;

    case SEARCH = 'search';

    case FILTER = 'filter';
}
