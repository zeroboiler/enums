<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum with class-level attribute mappings — used to test class-level metadata.
 */
#[EnumLabel(labels: ['active' => 'Active Label', 'inactive' => 'Inactive Label'])]
#[EnumDescription(descriptions: ['active' => 'Class-level active description'])]
#[EnumIcon(default: 'heroicon-o-default')]
enum ClassLevelEnum: string
{
    use HasEnumMetadata;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
