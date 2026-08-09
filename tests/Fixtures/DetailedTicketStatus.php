<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum fixture for testing class-level EnumDescription with per-case override.
 */
#[EnumDescription(descriptions: [
    'open' => 'Ticket is open and awaiting triage',
    'closed' => 'Ticket has been resolved',
])]
enum DetailedTicketStatus: string
{
    use HasEnumMetadata;

    case OPEN = 'open';

    case CLOSED = 'closed';

    #[Description('Ticket is currently being worked on')]
    case IN_PROGRESS = 'in_progress';
}
