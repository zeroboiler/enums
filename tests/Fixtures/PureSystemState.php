<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Fixture: Pure enum (not backed) with all attribute types.
 *
 * Tests:
 * - Pure enum metadata resolution (case names used as keys, not backed values)
 * - Class-level EnumColor/EnumLabel/EnumDescription on pure enum
 * - Per-case Label/Color/Description/Icon overrides on pure enum
 * - forSelect() uses case name for value (no backed value)
 * - values() returns case names
 * - tryFromName/hasCase work correctly
 * - Auto-label generation for SCREAMING_SNAKE_CASE pure enum
 */
#[EnumColor(success: ['READY'], danger: ['FAILED'])]
#[EnumLabel(labels: ['READY' => 'System Ready', 'FAILED' => 'System Failed'])]
#[EnumDescription(descriptions: ['READY' => 'All systems operational', 'FAILED' => 'Critical failure'])]
#[EnumIcon(default: 'heroicon-o-cog', icons: ['INITIALIZING' => 'heroicon-o-arrow-path'])]
enum PureSystemState
{
    use HasEnumMetadata;

    case INITIALIZING;

    #[Label('Ready to Serve')]
    #[Color('success')]
    #[Description('All services started and accepting traffic')]
    #[Icon('heroicon-o-check-circle')]
    case READY;

    case RUNNING;

    #[Label('System Failure')]
    #[Color('danger')]
    case FAILED;
}
