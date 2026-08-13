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
 * Fixture: single-case enum for edge case testing.
 *
 * Tests the minimum boundary of enum functionality —
 * a single case with full metadata attributes.
 */
#[EnumLabel(labels: ['on' => 'Enabled'])]
#[EnumColor(success: ['on'])]
#[EnumDescription(descriptions: ['on' => 'Feature is enabled'])]
#[EnumIcon(default: 'heroicon-o-check')]
enum SingleCaseToggle: string
{
    use HasEnumMetadata;

    case ON = 'on';
}
