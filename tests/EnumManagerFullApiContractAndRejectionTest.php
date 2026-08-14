<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Facades\Enum;
use ZeroBoiler\Enums\Tests\Fixtures\PlainTestEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager rejection of non-metadata enums', function (): void {
    it('forSelect throws BadMethodCallException for plain enum', function (): void {
        Enum::forSelect(PlainTestEnum::class);
    })->throws(\BadMethodCallException::class);

    it('forApi throws BadMethodCallException for plain enum', function (): void {
        Enum::forApi(PlainTestEnum::class);
    })->throws(\BadMethodCallException::class);

    it('tryFromLabel throws BadMethodCallException for plain enum', function (): void {
        Enum::tryFromLabel(PlainTestEnum::class, 'A');
    })->throws(\BadMethodCallException::class);

    it('tryFromName throws BadMethodCallException for plain enum', function (): void {
        Enum::tryFromName(PlainTestEnum::class, 'A');
    })->throws(\BadMethodCallException::class);

    it('fromName throws BadMethodCallException for plain enum', function (): void {
        Enum::fromName(PlainTestEnum::class, 'A');
    })->throws(\BadMethodCallException::class);

    it('hasCase throws BadMethodCallException for plain enum', function (): void {
        Enum::hasCase(PlainTestEnum::class, 'A');
    })->throws(\BadMethodCallException::class);

    it('values throws BadMethodCallException for plain enum', function (): void {
        Enum::values(PlainTestEnum::class);
    })->throws(\BadMethodCallException::class);

    it('labels throws BadMethodCallException for plain enum', function (): void {
        Enum::labels(PlainTestEnum::class);
    })->throws(\BadMethodCallException::class);
});

describe('EnumManager full API contract with valid enum', function (): void {
    it('forSelect returns correct structure', function (): void {
        $result = Enum::forSelect(UserStatus::class);

        expect($result)->toBeArray();
        expect($result[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi returns full metadata structure', function (): void {
        $result = Enum::forApi(UserStatus::class);

        expect($result)->toBeArray();
        expect($result[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('tryFromLabel returns matching case', function (): void {
        $result = Enum::tryFromLabel(UserStatus::class, 'Active User');

        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-existent label', function (): void {
        $result = Enum::tryFromLabel(UserStatus::class, 'Non Existent Label');

        expect($result)->toBeNull();
    });

    it('tryFromName returns matching case', function (): void {
        $result = Enum::tryFromName(UserStatus::class, 'ACTIVE');

        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('tryFromName returns null for non-existent name', function (): void {
        $result = Enum::tryFromName(UserStatus::class, 'NON_EXISTENT');

        expect($result)->toBeNull();
    });

    it('fromName returns matching case', function (): void {
        $result = Enum::fromName(UserStatus::class, 'BANNED');

        expect($result)->toBe(UserStatus::BANNED);
    });

    it('fromName throws InvalidEnumException for non-existent name', function (): void {
        Enum::fromName(UserStatus::class, 'NON_EXISTENT');
    })->throws(InvalidEnumException::class);

    it('hasCase returns true for existing case', function (): void {
        expect(Enum::hasCase(UserStatus::class, 'ACTIVE'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case', function (): void {
        expect(Enum::hasCase(UserStatus::class, 'NON_EXISTENT'))->toBeFalse();
    });

    it('values returns all backed values', function (): void {
        $values = Enum::values(UserStatus::class);

        expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
    });

    it('labels returns all labels', function (): void {
        $labels = Enum::labels(UserStatus::class);

        expect($labels)->toHaveCount(5);
        expect($labels[0])->toBe('Active User');
    });
});
