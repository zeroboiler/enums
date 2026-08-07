<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

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
    /**
     * @param  string  $value  Human-readable description text
     */
    public function __construct(
        public readonly string $value,
    ) {}
}
