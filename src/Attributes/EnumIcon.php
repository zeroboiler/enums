<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Defines icon metadata for enum cases.
 *
 * At class level, sets a default icon for all cases, and/or maps specific
 * case values to individual icons. At case level, overrides with a specific icon.
 *
 * Usage (class-level with default):
 *   #[EnumIcon(default: 'heroicon-o-question-mark-circle')]
 *   enum UserStatus: string { ... }
 *
 * Usage (class-level with per-case icon map):
 *   #[EnumIcon(default: 'heroicon-o-flag', icons: [1 => 'heroicon-o-check', 0 => 'heroicon-o-x-mark'])]
 *   enum SystemStatus: int { ... }
 *
 * Per-case override (always wins over class-level):
 *   #[Icon('heroicon-o-check-circle')]
 *   case ACTIVE = 'active';
 *
 * @see Icon For per-case icon override
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::icon() For the icon accessor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumIcon
{
    /**
     * @param  string|null  $default  Default icon for all cases (class-level)
     * @param  array<int|string, string>  $icons  Map of case value => icon (class-level, optional)
     */
    public function __construct(
        public readonly ?string $default = null,
        /** @var array<int|string, string> */
        public readonly array $icons = [],
    ) {}
}
