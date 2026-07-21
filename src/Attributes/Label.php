<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Per-case label override. Use on individual enum cases.
 *
 *   #[Label('Active User')]
 *   case ACTIVE = 'active';
 *
 * With translation:
 *   #[Label(translationKey: 'user_status.active')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final class Label
{
    /**
     * @param  string|null  $value  Static label text
     * @param  string|null  $translationKey  Translation key, resolved via __('enums.{translationKey}')
     */
    public function __construct(
        public ?string $value = null,
        public ?string $translationKey = null,
    ) {}
}
