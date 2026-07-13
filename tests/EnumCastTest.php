<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Casts\EnumCast;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
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

    // Issue #7: EnumCast::set() must throw InvalidEnumException on invalid values
    it('throws InvalidEnumException for invalid raw string value in set()', function (): void {
        $cast = new EnumCast(UserStatus::class);

        expect(fn (): mixed => $cast->set(
            model: new class {},
            key: 'status',
            value: 'nonexistent',
            attributes: [],
        ))->toThrow(InvalidEnumException::class);
    });

    it('throws InvalidEnumException for invalid raw int value in set()', function (): void {
        $cast = new EnumCast(Priority::class);

        expect(fn (): mixed => $cast->set(
            model: new class {},
            key: 'priority',
            value: 999,
            attributes: [],
        ))->toThrow(InvalidEnumException::class);
    });

    it('throws InvalidEnumException for non-int/string value in set()', function (): void {
        $cast = new EnumCast(UserStatus::class);

        expect(fn (): mixed => $cast->set(
            model: new class {},
            key: 'status',
            value: ['array'],
            attributes: [],
        ))->toThrow(InvalidEnumException::class);
    });

    // Cross-enum safety: passing wrong enum instance must throw
    it('throws InvalidEnumException when wrong enum instance is passed to set()', function (): void {
        $cast = new EnumCast(UserStatus::class);

        expect(fn (): mixed => $cast->set(
            model: new class {},
            key: 'status',
            value: Priority::HIGH,
            attributes: [],
        ))->toThrow(InvalidEnumException::class);
    });

    it('does not silently accept wrong enum instance (regression for #7)', function (): void {
        $cast = new EnumCast(UserStatus::class);

        try {
            $cast->set(
                model: new class {},
                key: 'status',
                value: Priority::HIGH,
                attributes: [],
            );
            expect(false)->toBeTrue('Expected InvalidEnumException was not thrown');
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('Priority')
                ->and($e->getMessage())->toContain('UserStatus');
        }
    });

    it('accepts correct enum instance in set()', function (): void {
        $cast = new EnumCast(UserStatus::class);

        $result = $cast->set(
            model: new class {},
            key: 'status',
            value: UserStatus::ACTIVE,
            attributes: [],
        );

        expect($result)->toBe('active');
    });
});
