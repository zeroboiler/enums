<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Defines color metadata for enum cases at the class level.
 *
 * Maps case values (backed values or case names) to UI color names.
 * Per-case overrides are supported via the {@see Color} attribute.
 *
 * Valid colors: `success`, `danger`, `warning`, `info`, `secondary`.
 * Colors default to `'secondary'` when not set via any attribute.
 *
 * Resolution priority (highest wins):
 * 1. Per-case `#[Color('success')]` — individual case override
 * 2. Class-level `#[EnumColor]` — bulk color mapping for multiple cases
 * 3. Default: `'secondary'`
 *
 * Usage (class-level):
 *   #[EnumColor(success: ['active', 'paid'], danger: ['banned'])]
 *   enum UserStatus: string { use HasEnumMetadata; ... }
 *
 * Per-case override:
 *   #[Color('success')]
 *   case ACTIVE = 'active';
 *
 * @see Color For per-case color override
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::color() For the color accessor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EnumColor
{
    /**
     * @param  array<int|string>  $success   Case values mapped to "success"
     * @param  array<int|string>  $danger    Case values mapped to "danger"
     * @param  array<int|string>  $warning   Case values mapped to "warning"
     * @param  array<int|string>  $info      Case values mapped to "info"
     * @param  array<int|string>  $secondary Case values mapped to "secondary"
     */
    public function __construct(
        public readonly array $success = [],
        public readonly array $danger = [],
        public readonly array $warning = [],
        public readonly array $info = [],
        public readonly array $secondary = [],
    ) {}
}
