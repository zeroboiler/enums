<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Regression tests for Issue #7: EnumCast::set() must throw on invalid values
 * instead of silently passing them through.
 */
describe('Issue #7: EnumCast::set() throws on invalid values', function (): void {
    it('throws InvalidArgumentException for invalid string value on string-backed enum', function (): void {
        $cast = new EnumCast(UserStatus::class);

        expect(fn (): mixed => $cast->set(
            model: new class {},
            key: 'status',
            value: 'invalid_value',
            attributes: [],
        ))->toThrow(InvalidArgumentException::class, 'Invalid value [invalid_value]');
    });

    it('throws InvalidArgumentException for invalid int value on int-backed enum', function (): void {
        $cast = new EnumCast(Priority::class);

        expect(fn (): mixed => $cast->set(
            model: new class {},
            key: 'priority',
            value: 999,
            attributes: [],
        ))->toThrow(InvalidArgumentException::class, 'Invalid value [999]');
    });

    it('does NOT silently pass through invalid values', function (): void {
        $cast = new EnumCast(UserStatus::class);

        // The bug was that tryFrom() returned null but the code returned $value anyway.
        // Verify the fix: invalid values must NOT be returned.
        $threw = false;

        try {
            $result = $cast->set(
                model: new class {},
                key: 'status',
                value: 'definitely_not_valid',
                attributes: [],
            );
            // If we get here without throwing, the value should NOT be the invalid input
            expect($result)->not->toBe('definitely_not_valid');
        } catch (InvalidArgumentException $e) {
            $threw = true;
        }

        expect($threw)->toBeTrue('EnumCast::set() must throw for invalid values');
    });

    it('accepts valid raw string value (regression)', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: 'active',
            attributes: [],
        );

        expect($result)->toBe('active');
    });

    it('accepts valid raw int value (regression)', function (): void {
        $cast = new EnumCast(Priority::class);

        $result = $cast->set(
            model: new class {},
            key: 'priority',
            value: 2,
            attributes: [],
        );

        expect($result)->toBe(2);
    });

    it('accepts valid enum instance (regression)', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: UserStatus::PENDING,
            attributes: [],
        );

        expect($result)->toBe('pending');
    });
});
