<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum with labels that differ only in case — used to test ambiguity prevention.
 */
enum AmbiguousLabel: string
{
    use HasEnumMetadata;

    #[Label('Active')]
    case ACTIVE = 'active';

    #[Label('ACTIVE')]
    case ACTIVE_UPPER = 'active_upper';
}
