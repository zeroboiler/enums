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
 * If neither is set, {@see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::label()}
 * auto-generates a label from the case name.
 *
 * Usage (class-level):
 *   #[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
 *   enum UserStatus: string { use HasEnumMetadata; ... }
 *
 * Per-case override:
 *   #[Label('Active User')]
 *   case ACTIVE = 'active';
 *
 * @see Label For per-case label override
 * @see \ZeroBoiler\Enums\Concerns\HasEnumMetadata::label() For the label accessor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumLabel
{
    /**
     * @param  array<int|string, string>|null  $labels   Map of case value => label (class-level)
     * @param  string|null                     $label   Single label (case-level)
     */
    public function __construct(
        /** @var array<int|string, string>|null */
        public readonly ?array $labels = null,
        public readonly ?string $label = null,
    ) {}
}
