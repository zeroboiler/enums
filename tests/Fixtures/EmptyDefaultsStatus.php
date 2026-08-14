<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Empty defaults fixture — no class-level or per-case attributes.
 *
 * Tests the auto-generation path where all metadata is derived
 * from case names only (SCREAMING_SNAKE_CASE → Title Case),
 * colors default to 'secondary', descriptions are null, icons are null.
 */
enum EmptyDefaultsStatus: string
{
    use HasEnumMetadata;

    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
