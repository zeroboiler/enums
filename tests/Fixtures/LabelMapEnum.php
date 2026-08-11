<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Test fixture for class-level label/description/icon map attributes.
 *
 * Tests that class-level attribute maps apply correctly to all cases
 * and that per-case overrides take precedence.
 */
#[EnumLabel(labels: ['draft' => 'Draft Article', 'published' => 'Published Article', 'archived' => 'Archived Article'])]
#[EnumDescription(descriptions: ['draft' => 'Article is in draft state', 'published' => 'Article is publicly visible'])]
#[EnumIcon(default: 'heroicon-o-document-text', icons: ['published' => 'heroicon-o-globe'])]
enum LabelMapEnum: string
{
    use HasEnumMetadata;

    // Draft uses class-level label: 'Draft Article'
    case DRAFT = 'draft';

    // Published uses class-level label: 'Published Article' + per-case icon: 'heroicon-o-globe'
    // (per-case would override class-level if any per-case #[Label] were present)
    case PUBLISHED = 'published';

    // Archived uses class-level label: 'Archived Article' + default icon: 'heroicon-o-document-text'
    case ARCHIVED = 'archived';

    // TRASHED is not in the class-level maps — should get auto-generated label
    // and the default icon
    case TRASHED = 'trashed';
}
