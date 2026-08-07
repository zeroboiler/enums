<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Mixed attribute fixture — class-level defaults with some per-case overrides.
 *
 * Used to verify the full resolution priority chain:
 *   1. Per-case attribute (highest)
 *   2. Class-level attribute
 *   3. Auto-generated (fallback)
 *
 * Also tests EnumLabel's case-level `label` parameter alongside
 * the class-level `labels` map.
 */
#[EnumLabel(labels: ['new' => 'Brand New Item', 'used' => 'Previously Owned'])]
#[EnumColor(success: ['active', 'new'], warning: ['pending', 'used'], danger: ['archived'])]
#[EnumDescription(descriptions: ['active' => 'Currently active', 'pending' => 'Awaiting review'])]
#[EnumIcon(default: 'heroicon-o-document')]
enum MixedAttributeStatus: string
{
    use HasEnumMetadata;

    // 'active' has class-level label ('Active Status' not set, will auto-generate),
    // class-level color (success), class-level description, and default icon.
    case ACTIVE = 'active';

    // 'new' has class-level label override 'Brand New Item' via EnumLabel::labels,
    // class-level color (success).
    case NEW = 'new';

    // 'pending' has class-level color (warning), class-level description.
    case PENDING = 'pending';

    // 'used' has class-level label 'Previously Owned' via EnumLabel::labels,
    // class-level color (warning).
    case USED = 'used';

    // 'archived' has class-level color (danger), no class-level label (auto-generated).
    case ARCHIVED = 'archived';

    // 'deleted' — no class-level anything, everything auto-generated/fallback.
    case DELETED = 'deleted';
}
