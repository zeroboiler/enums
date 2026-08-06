<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Pure enum (no backing type) for testing EnumRule with non-backed enums.
 */
enum RequestState
{
    use HasEnumMetadata;

    case DRAFT;
    case SUBMITTED;
    case APPROVED;
    case REJECTED;
}
