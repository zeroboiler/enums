<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Pure enum fixture — no backing type, uses case names for metadata keys.
 *
 * Tests that metadata resolution works with case names as keys
 * (since there are no backed values).
 */
enum PureFeatureFlag
{
    use HasEnumMetadata;

    #[Label('Dark Mode'), Color('secondary')]
    #[Icon('heroicon-o-moon')]
    #[Description('Toggle dark mode for the UI')]
    case DARK_MODE;

    #[Label('Beta Features'), Color('warning')]
    #[Icon('heroicon-o-beaker')]
    #[Description('Enable experimental beta features')]
    case BETA_FEATURES;

    case MAINTENANCE_MODE;
}
