<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Test enum using translation keys for labels.
 */
#[EnumLabel(translationKeys: ['inactive' => 'translatable_status.inactive'])]
enum TranslatableStatus: string
{
    use HasEnumMetadata;

    #[Label(translationKey: 'translatable_status.active')]
    case ACTIVE = 'active';

    case INACTIVE = 'inactive';
}
