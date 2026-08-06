<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\RequestState;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Cross-type enum consistency', function (): void {
    it('string-backed enum values() returns string values', function (): void {
        $values = UserStatus::values();

        expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
        expect($values)->each->toBeString();
    });

    it('int-backed enum values() returns int values', function (): void {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
        expect($values)->each->toBeInt();
    });

    it('pure enum values() returns case names as strings', function (): void {
        $values = RequestState::values();

        expect($values)->toBe(['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED']);
        expect($values)->each->toBeString();
    });

    it('forSelect preserves declaration order', function (): void {
        $select = UserStatus::forSelect();
        $values = array_column($select, 'value');

        expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
    });

    it('forApi preserves declaration order', function (): void {
        $api = UserStatus::forApi();
        $names = array_column($api, 'name');

        expect($names)->toBe(['ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED', 'BANNED']);
    });

    it('all labels are non-empty strings', function (): void {
        $labels = UserStatus::labels();

        expect($labels)->each(fn ($label) => expect($label)->toBeString()->not->toBeEmpty());
    });

    it('tryFromName works for both backed enum types', function (): void {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(Priority::tryFromName('LOW'))->toBe(Priority::LOW);
        expect(RequestState::tryFromName('DRAFT'))->toBe(RequestState::DRAFT);
    });

    it('fromName throws for invalid name', function (): void {
        expect(fn () => UserStatus::fromName('NONEXISTENT'))
            ->toThrow(\ZeroBoiler\Enums\Exceptions\InvalidEnumException::class);
    });

    it('hasCase returns correct booleans', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('active'))->toBeFalse(); // case-sensitive, not backed value
        expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
    });

    it('single-case enum works with all methods', function (): void {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
        expect(SingleCaseEnum::ONLY->label())->toBeString()->not->toBeEmpty();
        expect(SingleCaseEnum::ONLY->color())->toBe('secondary');
        expect(SingleCaseEnum::ONLY->icon())->toBeNull();
        expect(SingleCaseEnum::ONLY->description())->toBeNull();
        expect(SingleCaseEnum::forSelect())->toHaveCount(1);
        expect(SingleCaseEnum::forApi())->toHaveCount(1);
        expect(SingleCaseEnum::values())->toHaveCount(1);
        expect(SingleCaseEnum::labels())->toHaveCount(1);
        expect(SingleCaseEnum::tryFromName('ONLY'))->toBe(SingleCaseEnum::ONLY);
        expect(SingleCaseEnum::hasCase('ONLY'))->toBeTrue();
    });
});
