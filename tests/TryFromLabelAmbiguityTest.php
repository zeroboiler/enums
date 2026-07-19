<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\AmbiguousLabel;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('tryFromLabel ambiguity prevention', function (): void {
    it('returns exact case-sensitive match immediately', function (): void {
        // 'Active' exactly matches UserStatus::ACTIVE->label() === 'Active User'
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
    });

    it('returns null for ambiguous case-insensitive matches', function (): void {
        // AmbiguousLabel has both 'Active' and 'ACTIVE'
        // Case-insensitive search for 'active' matches both → ambiguous → null
        expect(AmbiguousLabel::tryFromLabel('active'))->toBeNull();
    });

    it('resolves exact match even when ambiguous case-insensitive exists', function (): void {
        // 'Active' exactly matches the first case label
        expect(AmbiguousLabel::tryFromLabel('Active'))->toBe(AmbiguousLabel::ACTIVE);
        expect(AmbiguousLabel::tryFromLabel('ACTIVE'))->toBe(AmbiguousLabel::ACTIVE_UPPER);
    });

    it('falls back to case-insensitive when unambiguous', function (): void {
        // 'ACTIVE USER' only matches 'Active User' case-insensitively
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
    });
});

describe('fromLabel', function (): void {
    it('returns the case for a valid label', function (): void {
        expect(UserStatus::fromLabel('Active User'))->toBe(UserStatus::ACTIVE);
    });

    it('throws ValueError for unknown label', function (): void {
        expect(fn (): UserStatus => UserStatus::fromLabel('NonExistent'))
            ->toThrow(ValueError::class, 'NonExistent');
    });

    it('throws ValueError for ambiguous label', function (): void {
        expect(fn (): AmbiguousLabel => AmbiguousLabel::fromLabel('active'))
            ->toThrow(ValueError::class);
    });
});
