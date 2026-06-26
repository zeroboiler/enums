<?php

declare(strict_types=1);

namespace NovaForge\Enums\Attributes;

use Attribute;

/**
 * Per-case color override. Use on individual enum cases.
 *
 *   #[Color('success')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Color
{
    public function __construct(
        public string $value,
    ) {}
}
