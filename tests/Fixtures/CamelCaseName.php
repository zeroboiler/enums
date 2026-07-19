<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum with camelCase names — used to test generateLabel() for camelCase.
 */
enum CamelCaseName: string
{
    use HasEnumMetadata;

    case pendingReview = 'pending_review';
    case inProgress = 'in_progress';
    case readyToShip = 'ready_to_ship';
}
