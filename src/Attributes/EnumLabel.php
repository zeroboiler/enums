<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

declare(strict=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Defines human-readable labels for enum cases.
 *
 * Usage:
 *   #[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
 *
 * Or per-case:
 *   #[Label('Active User')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumLabel
{
    /**
     * @param  array<string, string>|null  $labels  Map of case value => label (class-level)
     * @param  string|null  $label  Single label (case-level)
     */
    public function __construct(
        public ?array $labels = null,
        public ?string $label = null,
    ) {}
}
