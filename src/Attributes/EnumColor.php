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
 *
 * Usage (class-level):
 *   #[EnumColor(success: ['active', 'paid'], danger: ['banned'])]
 *   enum UserStatus: string { use HasEnumMetadata; ... }
 *
 * Per-case override:
 *   #[Color('success')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumColor
{
    /**
     * @param  list<string>  $success   Case values mapped to "success"
     * @param  list<string>  $danger    Case values mapped to "danger"
     * @param  list<string>  $warning   Case values mapped to "warning"
     * @param  list<string>  $info      Case values mapped to "info"
     * @param  list<string>  $secondary Case values mapped to "secondary"
     */
    public function __construct(
        public readonly array $success = [],
        public readonly array $danger = [],
        public readonly array $warning = [],
        public readonly array $info = [],
        public readonly array $secondary = [],
    ) {}
}
