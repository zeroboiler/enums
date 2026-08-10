<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Int-backed enum with all class-level metadata attributes.
 * Tests int-backed enum metadata resolution in bulk.
 *
 * Used to verify that forSelect(), forApi(), labels(), colors(), icons(), descriptions()
 * all work correctly with int-backed enums using class-level attributes.
 */
#[EnumColor(success: [1], danger: [0], warning: [2])]
#[EnumLabel(labels: [1 => 'Enabled', 0 => 'Disabled', 2 => 'Maintenance'])]
#[EnumIcon(default: 'heroicon-o-cog-6-tooth', icons: [1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark'])]
#[EnumDescription(descriptions: [1 => 'System is fully operational', 0 => 'System is offline', 2 => 'Undergoing scheduled maintenance'])]
enum SystemStatus: int
{
    use HasEnumMetadata;

    case DISABLED = 0;
    case ENABLED = 1;
    case MAINTENANCE = 2;
}
