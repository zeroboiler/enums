<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCast — serialize method', function (): void {
    it('serializes raw string value passthrough', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: 'active',
            attributes: [],
        );

        expect($result)->toBe('active');
    });

    it('serializes raw int value passthrough', function (): void {
        $cast = new EnumCast(Priority::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'priority',
            value: 2,
            attributes: [],
        );

        expect($result)->toBe(2);
    });

    it('serializes null value as null', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('serializes enum instance to its backed value', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: UserStatus::BANNED,
            attributes: [],
        );

        expect($result)->toBe('banned');
    });

    it('serializes int-backed enum instance to its int value', function (): void {
        $cast = new EnumCast(Priority::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'priority',
            value: Priority::URGENT,
            attributes: [],
        );

        expect($result)->toBe(4);
    });
});

describe('EnumCast — set method error cases', function (): void {
    it('throws when setting wrong enum type', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $cast->set(
            model: new class {},
            key: 'status',
            value: Priority::HIGH,
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'Expected enum ZeroBoiler\\Enums\\Tests\\Fixtures\\UserStatus');

    it('throws when setting invalid raw string value for int-backed enum', function (): void {
        $cast = new EnumCast(Priority::class);

        $cast->set(
            model: new class {},
            key: 'priority',
            value: 999,
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'Invalid value [999] for enum');

    it('throws when setting invalid type (float)', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $cast->set(
            model: new class {},
            key: 'status',
            value: 3.14,
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'Invalid value type for enum');

    it('throws when setting invalid type (array)', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $cast->set(
            model: new class {},
            key: 'status',
            value: ['active'],
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'Invalid value type for enum');

    it('sets valid raw string for string-backed enum', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: 'banned',
            attributes: [],
        );

        expect($result)->toBe('banned');
    });

    it('sets valid raw int for int-backed enum', function (): void {
        $cast = new EnumCast(Priority::class);

        $result = $cast->set(
            model: new class {},
            key: 'priority',
            value: 1,
            attributes: [],
        );

        expect($result)->toBe(1);
    });
});
