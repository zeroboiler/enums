<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Minimal enum — no attributes, auto-generate everything.
 */
enum OrderStatus: string
{
    use HasEnumMetadata;

    case PENDING = 'pending';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
