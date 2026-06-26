<?php

declare(strict_types=1);

namespace NovaForge\Enums\Attributes;

use Attribute;

/**
 * Per-case label override. Use on individual enum cases.
 *
 *   #[Label('Active User')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Label
{
    public function __construct(
        public string $value,
    ) {}
}
