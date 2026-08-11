<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Per-case icon override. Use on individual enum cases.
 *
 * Per-case overrides always win over class-level {@see EnumIcon} definitions
 * (both the default icon and per-value icon maps).
 * If neither is set, {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::icon()} returns null.
 *
 *   #[Icon('heroicon-o-check-circle')]
 *   case ACTIVE = 'active';
 *
 * @see EnumIcon For class-level icon mapping with defaults
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Icon
{
    /**
     * @param  string  $value  Icon identifier (e.g., 'heroicon-o-check-circle', 'fa-user')
     */
    public function __construct(
        public readonly string $value,
    ) {}
}
