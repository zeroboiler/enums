<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum with multi-word SCREAMING_SNAKE_CASE cases for label generation testing.
 */
#[EnumColor(success: ['IN_STOCK'], danger: ['OUT_OF_STOCK'], warning: ['ON_BACK_ORDER'])]
#[EnumDescription(descriptions: ['IN_STOCK' => 'Item is available', 'OUT_OF_STOCK' => 'Item is not available'])]
enum InventoryStatus: string
{
    use HasEnumMetadata;

    case IN_STOCK = 'in_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case ON_BACK_ORDER = 'on_back_order';
    case DISCONTINUED = 'discontinued';
}
