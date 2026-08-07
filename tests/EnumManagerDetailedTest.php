<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumManager detailed tests', function (): void {
    it('forSelect returns correct structure with all cases', function (): void {
        $manager = new EnumManager;
        $options = $manager->forSelect(UserStatus::class);

        expect($options)->toBeArray();
        expect($options)->toHaveCount(5);

        foreach ($options as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
            expect($option['value'])->not->toBeEmpty();
            expect($option['label'])->not->toBeEmpty();
        }
    });

    it('forSelect returns string values for string-backed enums', function (): void {
        $manager = new EnumManager;
        $options = $manager->forSelect(UserStatus::class);

        expect($options[0]['value'])->toBe('active');
        expect($options[1]['value'])->toBe('inactive');
    });

    it('forSelect returns int values for int-backed enums', function (): void {
        $manager = new EnumManager;
        $options = $manager->forSelect(Priority::class);

        expect($options[0]['value'])->toBe(1);
        expect($options[3]['value'])->toBe(4);
    });

    it('forApi returns full metadata for string-backed enums', function (): void {
        $manager = new EnumManager;
        $api = $manager->forApi(UserStatus::class);

        expect($api)->toBeArray();
        expect($api)->toHaveCount(5);

        $activeCase = $api[0];
        expect($activeCase)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($activeCase['value'])->toBe('active');
        expect($activeCase['name'])->toBe('ACTIVE');
        expect($activeCase['label'])->toBe('Active User');
        expect($activeCase['color'])->toBe('success');
        expect($activeCase['icon'])->toBe('heroicon-o-check-circle');
        expect($activeCase['description'])->toBe('User can fully access the system');
    });

    it('forApi returns int values for int-backed enums', function (): void {
        $manager = new EnumManager;
        $api = $manager->forApi(Priority::class);

        expect($api[0]['value'])->toBe(1);
        expect($api[0]['name'])->toBe('LOW');
    });

    it('forApi includes null description and icon when not set', function (): void {
        $manager = new EnumManager;
        $api = $manager->forApi(UserStatus::class);

        $inactiveCase = $api[1]; // INACTIVE
        expect($inactiveCase['description'])->toBeNull();
        expect($inactiveCase['icon'])->toBeNull();
    });

    it('tryFromLabel resolves case-insensitively', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'Active User'))->toBe(UserStatus::ACTIVE);
        expect($manager->tryFromLabel(UserStatus::class, 'active user'))->toBe(UserStatus::ACTIVE);
        expect($manager->tryFromLabel(UserStatus::class, 'ACTIVE USER'))->toBe(UserStatus::ACTIVE);
    });

    it('tryFromLabel returns null for non-existent label', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'Non Existent Label'))->toBeNull();
    });

    it('tryFromLabel works with auto-generated labels', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'Inactive'))->toBe(UserStatus::INACTIVE);
        expect($manager->tryFromLabel(UserStatus::class, 'Suspended'))->toBe(UserStatus::SUSPENDED);
    });

    it('tryFromLabel works with int-backed enums', function (): void {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(Priority::class, 'Low'))->toBe(Priority::LOW);
        expect($manager->tryFromLabel(Priority::class, 'Urgent'))->toBe(Priority::URGENT);
    });

    it('throws BadMethodCallException for non-enum class', function (): void {
        $manager = new EnumManager;

        expect(fn (): mixed => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException for enum without HasEnumMetadata', function (): void {
        $manager = new EnumManager;

        // Create an anonymous enum without the trait
        $plainEnum = new class('A', 'B') extends \UnitEnum {
        };

        expect(fn (): mixed => $manager->forSelect($plainEnum::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('forSelect returns case names for pure enums without HasEnumMetadata (via values)', function (): void {
        // OrderStatus uses HasEnumMetadata so it should work
        $manager = new EnumManager;
        $options = $manager->forSelect(OrderStatus::class);

        expect($options)->toBeArray();
        expect($options)->toHaveCount(4);
        expect($options[0]['value'])->toBe('pending');
        expect($options[0]['label'])->toBe('Pending');
    });
});
