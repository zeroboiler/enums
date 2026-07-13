<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Enum with labels that differ only in case — used to test ambiguity detection.
 */
enum AmbiguousStatus: string
{
    use HasEnumMetadata;

    #[Label('NEW')]
    case NEW_ORDER = 'new_order';

    #[Label('New')]
    case NEW_ITEM = 'new_item';

    case PROCESSING = 'processing';
}
