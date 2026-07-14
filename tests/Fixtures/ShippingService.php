<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Fixture with case-insensitive label collision — used to test
 * ambiguous match detection in tryFromLabel().
 */
enum ShippingService: string
{
    use HasEnumMetadata;

    #[Label('DHL Express')]
    case DHL = 'dhl';

    #[Label('DHL EXPRESS')]
    case DHL_PREMIUM = 'dhl_premium';

    #[Label('FedEx')]
    case FEDEX = 'fedex';

    #[Label('UPS')]
    case UPS = 'ups';
}
