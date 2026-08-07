<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Attributes;

use Attribute;

/**
 * Defines color metadata for enum cases at the class level.
 *
 * Maps case values (backed values or case names) to UI color names.
 * Per-case overrides are supported via the {@see Color} attribute.
 *
 * Valid colors: `success`, `danger`, `warning`, `info`, `secondary`.
 *
 * Usage (class-level):
 *   #[EnumColor(success: ['active', 'paid'], danger: ['banned'])]
 *   enum UserStatus: string { use HasEnumMetadata; ... }
 *
 * Per-case override:
 *   #[Color('success')]
 *   case ACTIVE = 'active';
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
final class EnumColor
{
    /** @var list<string> */
    public readonly array $success = [];

    /** @var list<string> */
    public readonly array $danger = [];

    /** @var list<string> */
    public readonly array $warning = [];

    /** @var list<string> */
    public readonly array $info = [];

    /** @var list<string> */
    public readonly array $secondary = [];

    /**
     * @param  list<string>  $success   Case values mapped to "success"
     * @param  list<string>  $danger    Case values mapped to "danger"
     * @param  list<string>  $warning   Case values mapped to "warning"
     * @param  list<string>  $info      Case values mapped to "info"
     * @param  list<string>  $secondary Case values mapped to "secondary"
     */
    public function __construct(
        /** @var list<string> */
        array $success = [],
        /** @var list<string> */
        array $danger = [],
        /** @var list<string> */
        array $warning = [],
        /** @var list<string> */
        array $info = [],
        /** @var list<string> */
        array $secondary = [],
    ) {
        $this->success = $success;
        $this->danger = $danger;
        $this->warning = $warning;
        $this->info = $info;
        $this->secondary = $secondary;
    }
}
