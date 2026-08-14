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
 * Edge-case fixture: enum with numeric/zero string values and auto-generated labels.
 *
 * Tests:
 * - Zero string value ('') as a valid backed value
 * - Numeric string values ('0', '1', '2')
 * - Mix of class-level and per-case attributes with numeric keys
 * - Auto-generated labels for cases without explicit labels
 * - Default color fallback ('secondary') when no color attribute is set
 * - Default icon fallback (null) when no icon is set
 * - Default description fallback (null) when no description is set
 */
#[EnumLabel(labels: ['' => 'None', '0' => 'Zero', '1' => 'One'])]
#[EnumDescription(descriptions: ['0' => 'Numeric zero value', '1' => 'Numeric one value'])]
#[EnumColor(success: ['1'], warning: ['0'])]
#[EnumIcon(default: 'heroicon-o-number')]
enum NumericStatusCode: string
{
    use HasEnumMetadata;

    case EMPTY_VALUE = '';

    #[Color('danger')]
    case ZERO = '0';

    case ONE = '1';

    #[Label('Custom Two Label')]
    #[Description('Custom description for two')]
    #[Icon('heroicon-o-double')]
    case TWO = '2';
}
