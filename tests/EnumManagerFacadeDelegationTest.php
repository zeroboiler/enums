<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager facade delegation — fromName, values, labels', function () {
    it('delegates fromName() via facade', function () {
        $case = Enum::fromName(UserStatus::class, 'ACTIVE');
        expect($case)->toBeInstanceOf(UserStatus::class);
        expect($case->name)->toBe('ACTIVE');
    });

    it('fromName() throws InvalidEnumException for invalid name', function () {
        Enum::fromName(UserStatus::class, 'NON_EXISTENT');
    })->throws(InvalidEnumException::class);

    it('delegates values() via facade', function () {
        $values = Enum::values(UserStatus::class);
        expect($values)->toBeArray();
        expect($values)->not->toBeEmpty();
        expect($values)->toContain('active');
    });

    it('delegates labels() via facade', function () {
        $labels = Enum::labels(UserStatus::class);
        expect($labels)->toBeArray();
        expect($labels)->not->toBeEmpty();
        expect($labels[0])->toBeString()->not->toBeEmpty();
    });

    it('values() returns same result as static call', function () {
        expect(Enum::values(UserStatus::class))->toBe(UserStatus::values());
    });

    it('labels() returns same result as static call', function () {
        expect(Enum::labels(UserStatus::class))->toBe(UserStatus::labels());
    });

    it('fromName() returns same result as static call', function () {
        $facadeResult = Enum::fromName(UserStatus::class, 'ACTIVE');
        $staticResult = UserStatus::fromName('ACTIVE');
        expect($facadeResult)->toBe($staticResult);
    });

    it('all eight facade methods are accessible', function () {
        // Verify no BadMethodCallException is thrown for any method
        expect(fn () => Enum::forSelect(UserStatus::class))->not->toThrow(\BadMethodCallException::class);
        expect(fn () => Enum::forApi(UserStatus::class))->not->toThrow(\BadMethodCallException::class);
        expect(fn () => Enum::tryFromLabel(UserStatus::class, 'Active User'))->not->toThrow(\BadMethodCallException::class);
        expect(fn () => Enum::tryFromName(UserStatus::class, 'ACTIVE'))->not->toThrow(\BadMethodCallException::class);
        expect(fn () => Enum::fromName(UserStatus::class, 'ACTIVE'))->not->toThrow(\BadMethodCallException::class);
        expect(fn () => Enum::hasCase(UserStatus::class, 'ACTIVE'))->not->toThrow(\BadMethodCallException::class);
        expect(fn () => Enum::values(UserStatus::class))->not->toThrow(\BadMethodCallException::class);
        expect(fn () => Enum::labels(UserStatus::class))->not->toThrow(\BadMethodCallException::class);
    });
});
