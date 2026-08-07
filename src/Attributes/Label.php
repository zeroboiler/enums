<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

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
    /**
     * @param  string  $value  Human-readable label text
     */
    public function __construct(
        public readonly string $value,
    ) {}
}
