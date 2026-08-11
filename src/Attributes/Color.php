<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Per-case color override. Use on individual enum cases.
 *
 * Per-case overrides always win over class-level {@see EnumColor} definitions.
 * If neither is set, {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::color()}
 * returns 'secondary' as the default color.
 *
 *   #[Color('success')]
 *   case ACTIVE = 'active';
 *
 * @see EnumColor For class-level color mapping
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Color
{
    /**
     * @param  string  $value  UI color name (e.g., 'success', 'danger', 'warning', 'info', 'secondary')
     */
    public function __construct(
        public readonly string $value,
    ) {}
}
