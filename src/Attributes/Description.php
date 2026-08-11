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
 * Per-case overrides always win over class-level {@see EnumDescription} definitions.
 * If neither is set, {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::description()} returns null.
 *
 *   #[Description('User can fully access the system')]
 *   case ACTIVE = 'active';
 *
 * @see EnumDescription For class-level description mapping
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
