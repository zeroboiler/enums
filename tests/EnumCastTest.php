<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCast', function (): void {
    it('casts database value to enum instance', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            model: new class {},
            key: 'status',
            value: 'active',
            attributes: [],
        );

        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('returns null for null value', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            model: new class {},
            key: 'status',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('returns null for invalid value', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            model: new class {},
            key: 'status',
            value: 'nonexistent',
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('sets enum instance to its value', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: UserStatus::BANNED,
            attributes: [],
        );

        expect($result)->toBe('banned');
    });

    it('sets null for null value', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('works with int-backed enums', function (): void {
        $cast = new EnumCast(Priority::class);

        $result = $cast->get(
            model: new class {},
            key: 'priority',
            value: 3,
            attributes: [],
        );

        expect($result)->toBe(Priority::HIGH);
    });

    it('serializes enum to its value', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: UserStatus::ACTIVE,
            attributes: [],
        );

        expect($result)->toBe('active');
    });

    it('throws when setting a BackedEnum from a different class', function (): void {
        $cast = new EnumCast(UserStatus::class);

        expect(fn (): mixed => $cast->set(
            model: new class {},
            key: 'status',
            value: Priority::HIGH,
            attributes: [],
        ))->toThrow(InvalidArgumentException::class);
    });

    it('accepts the correct enum instance in set()', function (): void {
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
