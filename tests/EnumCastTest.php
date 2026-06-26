<?php

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCast', function () {
    it('casts database value to enum instance', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            model: new class {},
            key: 'status',
            value: 'active',
            attributes: [],
        );

        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('returns null for null value', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            model: new class {},
            key: 'status',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('returns null for invalid value', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->get(
            model: new class {},
            key: 'status',
            value: 'nonexistent',
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('sets enum instance to its value', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: UserStatus::BANNED,
            attributes: [],
        );

        expect($result)->toBe('banned');
    });

    it('sets null for null value', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('works with int-backed enums', function () {
        $cast = new EnumCast(Priority::class);

        $result = $cast->get(
            model: new class {},
            key: 'priority',
            value: 3,
            attributes: [],
        );

        expect($result)->toBe(Priority::HIGH);
    });

    it('serializes enum to its value', function () {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'status',
            value: UserStatus::ACTIVE,
            attributes: [],
        );

        expect($result)->toBe('active');
    });
});
