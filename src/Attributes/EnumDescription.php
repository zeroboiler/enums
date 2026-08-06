<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Defines description metadata for enum cases.
 *
 * At class level, maps multiple case values to descriptions.
 * At case level, sets a single case's description (override).
 *
 * Per-case overrides always win over class-level definitions.
 *
 * Usage (class-level):
 *   #[EnumDescription(descriptions: ['active' => 'User is active and can login'])]
 *
 * Per-case override:
 *   #[Description('User is active and can login')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumDescription
{
    /**
     * @param  array<string, string>|null  $descriptions Map of case value => description (class-level)
     * @param  string|null                  $description  Single description (case-level)
     */
    public function __construct(
        public readonly ?array $descriptions = null,
        public readonly ?string $description = null,
    ) {}
}
