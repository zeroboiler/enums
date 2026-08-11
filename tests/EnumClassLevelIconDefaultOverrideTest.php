<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;

beforeEach(function () {
    EnumCache::flush();
    EnumCache::getInstance()->setTtl(0);
});

afterEach(function () {
    EnumCache::resetInstance();
});

describe('Class-level EnumIcon with default and per-case override', function () {
    it('applies default icon to all cases when only default is set', function () {
        $cases = TestIconDefaultEnum::cases();

        foreach ($cases as $case) {
            expect($case->icon())->toBe('heroicon-o-question-mark-circle');
        }
    });

    it('overrides default icon for specific cases via per-case EnumIcon', function () {
        // FEATURE_A should use per-case override
        expect(TestIconOverrideEnum::FEATURE_A->icon())->toBe('heroicon-o-check');
        // FEATURE_B should use default
        expect(TestIconOverrideEnum::FEATURE_B->icon())->toBe('heroicon-o-flag');
        // FEATURE_C should use specific icon from icons map
        expect(TestIconOverrideEnum::FEATURE_C->icon())->toBe('heroicon-o-star');
    });

    it('per-case Icon attribute wins over class-level EnumIcon default', function () {
        expect(TestIconPerCaseWinsEnum::ACTIVE->icon())->toBe('heroicon-o-check-circle');
        // INACTIVE should use class-level default
        expect(TestIconPerCaseWinsEnum::INACTIVE->icon())->toBe('heroicon-o-x-mark');
    });

    it('caches icon resolution and returns consistent results', function () {
        EnumCache::getInstance()->setTtl(300);

        $icon1 = TestIconDefaultEnum::PENDING->icon();
        $icon2 = TestIconDefaultEnum::PENDING->icon();

        expect($icon1)->toBe($icon2);
        expect(EnumCache::getInstance()->has(TestIconDefaultEnum::class))->toBeTrue();
    });

    it('invalidates cache and re-resolves icons', function () {
        EnumCache::getInstance()->setTtl(300);

        $icon1 = TestIconDefaultEnum::PENDING->icon();
        EnumMetadataResolver::invalidate(TestIconDefaultEnum::class);
        $icon2 = TestIconDefaultEnum::PENDING->icon();

        expect($icon1)->toBe($icon2);
        expect(EnumCache::getInstance()->has(TestIconDefaultEnum::class))->toBeTrue();
    });
});

// ── Fixtures ────────────────────────────────────────────────────

#[\ZeroBoiler\Enums\Attributes\EnumIcon(default: 'heroicon-o-question-mark-circle')]
enum TestIconDefaultEnum: string
{
    use HasEnumMetadata;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETE = 'complete';
}

#[\ZeroBoiler\Enums\Attributes\EnumIcon(
    default: 'heroicon-o-flag',
    icons: [
        'star' => 'heroicon-o-star',
    ]
)]
enum TestIconOverrideEnum: string
{
    use HasEnumMetadata;

    // Per-case EnumIcon override
    #[\ZeroBoiler\Enums\Attributes\EnumIcon(default: 'heroicon-o-check')]
    case FEATURE_A = 'feature_a';

    // Uses class-level default
    case FEATURE_B = 'feature_b';

    // Uses icons map
    case FEATURE_C = 'star';
}

#[\ZeroBoiler\Enums\Attributes\EnumIcon(default: 'heroicon-o-x-mark')]
enum TestIconPerCaseWinsEnum: string
{
    use HasEnumMetadata;

    #[\ZeroBoiler\Enums\Attributes\Icon('heroicon-o-check-circle')]
    case ACTIVE = 'active';

    case INACTIVE = 'inactive';
}
