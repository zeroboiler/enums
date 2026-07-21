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
 * Usage:
 *   #[EnumLabel(labels: ['active' => 'Active User', 'banned' => 'Banned User'])]
 *
 * With translation keys:
 *   #[EnumLabel(translationKeys: ['active' => 'user_status.active', 'banned' => 'user_status.banned'])]
 *
 * Or per-case:
 *   #[Label('Active User')]
 *   case ACTIVE = 'active';
 *
 *   #[Label(translationKey: 'user_status.active')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumLabel
{
    /**
     * @param  array<string, string>|null  $labels  Map of case value => label (class-level)
     * @param  string|null  $label  Single label (case-level)
     * @param  array<string, string>|null  $translationKeys  Map of case value => translation key (class-level)
     * @param  string|null  $translationKey  Single translation key (case-level)
     */
    public function __construct(
        public ?array $labels = null,
        public ?string $label = null,
        public ?array $translationKeys = null,
        public ?string $translationKey = null,
    ) {}
}
