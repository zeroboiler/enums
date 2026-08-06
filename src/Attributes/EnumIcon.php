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
 * At class level, sets a default icon for all cases.
 * At case level, overrides with a specific icon.
 *
 * Usage (class-level):
 *   #[EnumIcon(default: 'heroicon-o-question-mark-circle')]
 *   enum UserStatus: string { ... }
 *
 * Per-case override:
 *   #[Icon('heroicon-o-check-circle')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumIcon
{
    /**
     * @param  string|null  $default  Default icon for all cases (class-level)
     */
    public function __construct(
        public readonly ?string $default = null,
    ) {}
}
