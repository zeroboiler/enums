<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum fixture with ALL class-level attributes set.
 *
 * Used to test that class-level EnumLabel, EnumDescription, and EnumIcon
 * are all resolved correctly in combination.
 */
#[EnumLabel(labels: ['open' => 'Open Status', 'in_progress' => 'In Progress', 'done' => 'Done'])]
#[EnumDescription(descriptions: ['open' => 'Task is open', 'in_progress' => 'Task is being worked on', 'done' => 'Task is complete'])]
#[EnumIcon(default: 'heroicon-o-circle')]
enum AllClassLevelEnum: string
{
    use HasEnumMetadata;

    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
}
