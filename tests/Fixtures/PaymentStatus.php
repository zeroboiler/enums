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
 * Enum fixture with ALL class-level attributes for comprehensive testing.
 */
#[EnumColor(success: ['approved'], danger: ['rejected'], warning: ['review'])]
#[EnumDescription(descriptions: [
    'approved' => 'Payment has been approved',
    'rejected' => 'Payment was rejected',
    'review' => 'Payment is under review',
])]
#[EnumLabel(labels: [
    'approved' => 'Approved Payment',
    'rejected' => 'Rejected Payment',
    'review' => 'Under Review',
])]
#[EnumIcon(default: 'heroicon-o-banknotes')]
enum PaymentStatus: string
{
    use HasEnumMetadata;

    case APPROVED = 'approved';

    case REJECTED = 'rejected';

    case REVIEW = 'review';
}
