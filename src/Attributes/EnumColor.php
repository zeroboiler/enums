<?php

declare(strict_types=1);

namespace NovaForge\Enums\Attributes;

use Attribute;

/**
 * Defines color metadata for enum cases.
 *
 * Usage:
 *   #[EnumColor(success: ['active', 'paid'], danger: ['banned', 'failed'])]
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
     * @param array<string>  $success   Case values that map to "success"
     * @param array<string>  $danger    Case values that map to "danger"
     * @param array<string>  $warning   Case values that map to "warning"
     * @param array<string>  $info      Case values that map to "info"
     * @param array<string>  $secondary Case values that map to "secondary"
     */
    public function __construct(
        public array $success = [],
        public array $danger = [],
        public array $warning = [],
        public array $info = [],
        public array $secondary = [],
    ) {}
}
