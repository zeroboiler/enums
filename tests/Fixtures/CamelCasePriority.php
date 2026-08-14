<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * CamelCase enum fixture — tests auto-label generation for non-SCREAMING_SNAKE_CASE names.
 *
 * When the case name is NOT all uppercase (e.g. camelCase or mixed),
 * the label generator splits on capital letters instead of underscores:
 *   camelCase → "Camel Case"
 *   PascalCase → "Pascal Case"
 *
 * Tests:
 * - CamelCase auto-label generation (splits on uppercase letters)
 * - Mixed class-level + per-case attributes with non-standard naming
 * - Color/icon/description resolution for camelCase cases
 */
#[EnumLabel(labels: ['active' => 'Online', 'pendingReview' => 'Under Review'])]
#[EnumColor(success: ['active'], warning: ['pendingReview'])]
#[EnumDescription(descriptions: ['active' => 'User is online', 'archived' => 'Account archived'])]
#[EnumIcon(default: 'heroicon-o-circle', icons: ['active' => 'heroicon-o-check'])]
enum CamelCasePriority: string
{
    use HasEnumMetadata;

    case active = 'active';

    #[Label('Awaiting Approval')]
    #[Color('warning')]
    case pendingReview = 'pendingReview';

    case archived = 'archived';

    #[Description('Soft-deleted account')]
    case softDeleted = 'softDeleted';
}
