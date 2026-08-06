<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

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
     * @param  array<string>  $success  Case values that map to "success"
     * @param  array<string>  $danger  Case values that map to "danger"
     * @param  array<string>  $warning  Case values that map to "warning"
     * @param  array<string>  $info  Case values that map to "info"
     * @param  array<string>  $secondary  Case values that map to "secondary"
     */
    /** @var list<string> */
    public readonly array $success;

    /** @var list<string> */
    public readonly array $danger;

    /** @var list<string> */
    public readonly array $warning;

    /** @var list<string> */
    public readonly array $info;

    /** @var list<string> */
    public readonly array $secondary;

    /**
     * @param  list<string>  $success  Case values that map to "success"
     * @param  list<string>  $danger  Case values that map to "danger"
     * @param  list<string>  $warning  Case values that map to "warning"
     * @param  list<string>  $info  Case values that map to "info"
     * @param  list<string>  $secondary  Case values that map to "secondary"
     */
    public function __construct(
        array $success = [],
        array $danger = [],
        array $warning = [],
        array $info = [],
        array $secondary = [],
    ) {
        $this->success = $success;
        $this->danger = $danger;
        $this->warning = $warning;
        $this->info = $info;
        $this->secondary = $secondary;
    }
}
