<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Single-case enum fixture — tests edge case where an enum has only one case.
 *
 *   OnlyOne::ONLY  → label: "Only", value: "only"
 */
enum SingleCaseEnum: string
{
    use HasEnumMetadata;

    case ONLY = 'only';
}
