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
 * Enum with class-level attributes — tests bulk metadata definitions.
 */
#[EnumLabel(labels: ['open' => 'Open', 'in_progress' => 'In Progress', 'closed' => 'Closed'])]
#[EnumDescription(descriptions: ['open' => 'Ticket is open and awaiting response', 'closed' => 'Ticket has been resolved'])]
#[EnumIcon(default: 'heroicon-o-ticket')]
enum TicketStatus: string
{
    use HasEnumMetadata;

    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';
}
