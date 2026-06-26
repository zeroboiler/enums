<?php

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Per-case icon override. Use on individual enum cases.
 *
 *   #[Icon('heroicon-o-check-circle')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Icon
{
    public function __construct(
        public string $value,
    ) {}
}
