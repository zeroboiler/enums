<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Defines human-readable labels for enum cases.
 *
 * At class level, maps multiple case values to labels at once.
 * At case level, sets a single case's label (override).
 *
 * Per-case overrides always win over class-level definitions.
 *
 * Usage (class-level):
 *   #[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
 *   enum UserStatus: string { use HasEnumMetadata; ... }
 *
 * Per-case override:
 *   #[Label('Active User')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumLabel
{
    /**
     * @param  array<int|string, string>|null  $labels   Map of case value => label (class-level)
     * @param  string|null                     $label   Single label (case-level)
     */
    public function __construct(
        public readonly ?array $labels = null,
        public readonly ?string $label = null,
    ) {}
}
