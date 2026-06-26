<?php

declare(strict_types=1);

namespace NovaForge\Enums\Attributes;

use Attribute;

/**
 * Per-case description override. Use on individual enum cases.
 *
 *   #[Description('User can fully access the system')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Description
{
    public function __construct(
        public string $value,
    ) {}
}
