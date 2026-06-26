<?php

declare(strict_types=1);

namespace NovaForge\Enums\Tests\Fixtures;

use NovaForge\Enums\Concerns\HasEnumMetadata;

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
