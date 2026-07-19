<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\AmbiguousLabel;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('tryFromLabel strict mode', function (): void {
    it('returns exact match in strict mode', function (): void {
        expect(UserStatus::tryFromLabel('Active User', strict: true))
            ->toBe(UserStatus::ACTIVE);
    });

    it('returns null for case-insensitive match in strict mode', function (): void {
        // 'ACTIVE USER' would match case-insensitively, but strict mode rejects it
        expect(UserStatus::tryFromLabel('ACTIVE USER', strict: true))
            ->toBeNull();
    });

    it('returns null for unknown label in strict mode', function (): void {
        expect(UserStatus::tryFromLabel('Unknown', strict: true))
            ->toBeNull();
    });

    it('still works without strict parameter (backwards compatible)', function (): void {
        expect(UserStatus::tryFromLabel('ACTIVE USER'))
            ->toBe(UserStatus::ACTIVE);
    });

    it('strict mode resolves exact match even when ambiguous labels exist', function (): void {
        // AmbiguousLabel has 'Active' and 'ACTIVE' as labels
        expect(AmbiguousLabel::tryFromLabel('Active', strict: true))
            ->toBe(AmbiguousLabel::ACTIVE);
        expect(AmbiguousLabel::tryFromLabel('ACTIVE', strict: true))
            ->toBe(AmbiguousLabel::ACTIVE_UPPER);
    });

    it('strict mode returns null for case-only-different label', function (): void {
        // 'active' matches neither 'Active' nor 'ACTIVE' exactly
        expect(AmbiguousLabel::tryFromLabel('active', strict: true))
            ->toBeNull();
    });
});

describe('fromLabel strict mode', function (): void {
    it('returns exact match in strict mode', function (): void {
        expect(UserStatus::fromLabel('Active User', strict: true))
            ->toBe(UserStatus::ACTIVE);
    });

    it('throws ValueError for case-insensitive-only match in strict mode', function (): void {
        expect(fn (): UserStatus => UserStatus::fromLabel('ACTIVE USER', strict: true))
            ->toThrow(ValueError::class);
    });
});

describe('EnumManager strict mode', function (): void {
    it('passes strict flag through to enum', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'Active User', true))
            ->toBe(UserStatus::ACTIVE);
        expect($manager->tryFromLabel(UserStatus::class, 'ACTIVE USER', true))
            ->toBeNull();
    });
});
