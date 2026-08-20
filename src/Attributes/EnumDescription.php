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
 * If neither is set, {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::description()} returns null.
 *
 * Resolution priority (highest wins):
 * 1. Per-case `#[Description('...')]` — individual case override
 * 2. Class-level `#[EnumDescription]` — bulk description mapping
 * 3. Default: `null`
 *
 * Usage (class-level):
 *   #[EnumDescription(descriptions: ['active' => 'User is active and can login'])]
 *
 * Per-case override:
 *   #[Description('User is active and can login')]
 *   case ACTIVE = 'active';
 *
 * @see Description For per-case description override
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::description() For the description accessor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumDescription
{
    /**
     * @param  array<int|string, string>|null  $descriptions Map of case value => description (class-level)
     * @param  string|null                       $description  Single description (case-level)
     */
    public function __construct(
        public readonly ?array $descriptions = null,
        public readonly ?string $description = null,
    ) {}
}
