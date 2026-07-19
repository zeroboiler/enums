<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Pure enum (non-backed) — used to verify HasEnumMetadata works without backed values.
 */
enum PureStatus
{
    use HasEnumMetadata;

    #[Label('Published')]
    case PUBLISHED;

    #[Label('Draft')]
    case DRAFT;

    case ARCHIVED;
}
