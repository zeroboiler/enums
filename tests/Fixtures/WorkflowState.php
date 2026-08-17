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
 * Comprehensive fixture with ALL attribute types — class-level and per-case.
 *
 * Tests full attribute coverage in a single enum:
 * - String-backed with multiple cases
 * - Class-level EnumColor, EnumLabel, EnumDescription, EnumIcon
 * - Per-case Label, Color, Icon, Description overrides
 * - Mixed attribute presence (some cases with overrides, some without)
 * - Auto-generated labels for cases without explicit Label/EnumLabel
 */
#[EnumColor(
    success: ['active', 'completed'],
    danger: ['failed', 'deleted'],
    warning: ['pending'],
    info: ['processing'],
)]
#[EnumLabel(labels: [
    'active' => 'Active',
    'pending' => 'Pending Review',
    'deleted' => 'Soft Deleted',
])]
#[EnumDescription(descriptions: [
    'active' => 'Currently active and running',
    'failed' => 'Execution has failed',
    'deleted' => 'Marked for deletion',
])]
#[EnumIcon(
    default: 'heroicon-o-circle-dot',
    icons: [
        'active' => 'heroicon-o-check-circle',
        'failed' => 'heroicon-o-x-circle',
        'completed' => 'heroicon-o-check',
    ],
)]
enum WorkflowState: string
{
    use HasEnumMetadata;

    #[Label('Active & Running')]
    #[Icon('heroicon-o-bolt')]
    #[Description('System is actively processing')]
    case ACTIVE = 'active';

    case PENDING = 'pending';

    case PROCESSING = 'processing';

    #[Color('info')]
    #[Description('Task is currently being processed')]
    case PROCESSING_ALT = 'processing_alt';

    case COMPLETED = 'completed';

    case FAILED = 'failed';

    #[Description('Soft deleted — recoverable within 30 days')]
    case DELETED = 'deleted';
}
