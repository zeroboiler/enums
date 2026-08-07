<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Pure enum (no backing type) fixture with per-case attributes.
 *
 * Tests that per-case Icon/Description attributes work correctly
 * on pure enums without a backing type. values() and forSelect()
 * should return case names as values.
 */
enum PureFeatureFlag
{
    use HasEnumMetadata;

    #[Icon('heroicon-o-shield-check')]
    case TWO_FACTOR_AUTH;

    case DARK_MODE;

    case BETA_ACCESS;
}
