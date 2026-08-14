<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests\Fixtures;

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;

/**
 * Complex mixed fixture — all attribute types at both class and case level.
 *
 * Used to verify:
 * - Class-level bulk mappings override auto-generated defaults
 * - Per-case attributes override class-level attributes
 * - Partial overrides (only some cases have per-case attrs)
 * - Int-backed enum with mixed class/case-level descriptions
 * - Label collision handling (class-level vs per-case)
 * - Icon default fallback for cases without specific icon
 */
#[EnumLabel(labels: [1 => 'Bug Report', 2 => 'Feature Request', 3 => 'Support Ticket', 4 => 'Documentation Issue'])]
#[EnumDescription(descriptions: [1 => 'Report a bug', 3 => 'Get help'])]
#[EnumIcon(default: 'heroicon-o-question-mark-circle', icons: [1 => 'heroicon-o-bug', 2 => 'heroicon-o-sparkles'])]
enum MixedTicketType: int
{
    use HasEnumMetadata;

    /** Critical case — has ALL per-case attributes overriding class-level. */
    #[Label('Critical Bug')]
    #[Description('System-breaking bug — immediate fix required')]
    #[Icon('heroicon-o-fire')]
    #[Color('danger')]
    case CRITICAL_BUG = 1;

    /** Normal case — only has a per-case color override. */
    #[Color('success')]
    case FEATURE = 2;

    /** Normal case — no per-case attributes, uses class-level defaults. */
    case SUPPORT = 3;

    /** Edge case — partially overridden. */
    #[Description('Needs documentation update')]
    case DOCS = 4;
}
