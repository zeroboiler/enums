<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumManager;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('EnumManager contract compliance', function () {
    it('throws BadMethodCallException for non-enum class in forSelect', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forSelect(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('throws BadMethodCallException for enum without HasEnumMetadata in forSelect', function () {
        $manager = new EnumManager;

        // Plain enum without HasEnumMetadata trait
        $manager->forSelect(PlainEnum::class);
    })->throws(\BadMethodCallException::class);

    it('throws BadMethodCallException for non-enum class in forApi', function () {
        $manager = new EnumManager;

        expect(fn () => $manager->forApi(\stdClass::class))
            ->toThrow(\BadMethodCallException::class);
    });

    it('returns correct structure from forSelect for string-backed enum', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(UserStatus::class);

        expect($result)
            ->toBeArray()
            ->not->toBeEmpty()
            ->each->toBeArray();

        // Each entry should have 'value' and 'label' keys
        foreach ($result as $item) {
            expect($item)->toHaveKeys(['value', 'label']);
            expect($item['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('returns correct structure from forSelect for int-backed enum', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(IntBackedPriority::class);

        expect($result)->toBeArray()->not->toBeEmpty();

        // Verify int values are used (not case names)
        $values = array_column($result, 'value');
        foreach ($values as $value) {
            expect($value)->toBeInt();
        }

        // Values should match the enum's backed values
        $expectedValues = array_map(fn (\BackedEnum $case) => $case->value, IntBackedPriority::cases());
        expect($values)->toBe($expectedValues);
    });

    it('returns correct structure from forApi', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(TicketStatus::class);

        expect($result)->toBeArray()->not->toBeEmpty();

        foreach ($result as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->toBeString();
            expect($item['name'])->toBeString();
            expect($item['label'])->toBeString()->not->toBeEmpty();
            expect($item['color'])->toBeString()->not->toBeEmpty();
        }
    });

    it('tryFromLabel resolves case-insensitively', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromLabel(UserStatus::class, 'active user');

        expect($case)->toBeInstanceOf(\UnitEnum::class);
        expect($case->name)->toBe('ACTIVE');
    });

    it('tryFromLabel returns null for non-existent label', function () {
        $manager = new EnumManager;

        expect($manager->tryFromLabel(UserStatus::class, 'nonexistent-label-xyz'))
            ->toBeNull();
    });

    it('tryFromName resolves by case name', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromName(Priority::class, 'LOW');

        expect($case)->toBeInstanceOf(\UnitEnum::class);
        expect($case->name)->toBe('LOW');
    });

    it('tryFromName returns null for non-existent name', function () {
        $manager = new EnumManager;

        expect($manager->tryFromName(Priority::class, 'NONEXISTENT'))
            ->toBeNull();
    });

    it('tryFromName works with int-backed enums', function () {
        $manager = new EnumManager;
        $case = $manager->tryFromName(ZeroBackedPriority::class, 'NONE');

        expect($case)->toBeInstanceOf(\UnitEnum::class);
        expect($case->name)->toBe('NONE');
    });

    it('hasCase returns true for existing case', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(TicketStatus::class, 'OPEN'))->toBeTrue();
        expect($manager->hasCase(TicketStatus::class, 'CLOSED'))->toBeTrue();
    });

    it('hasCase returns false for non-existent case', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(TicketStatus::class, 'DELETED'))->toBeFalse();
    });

    it('hasCase works with pure enums', function () {
        $manager = new EnumManager;

        expect($manager->hasCase(PureFeatureFlag::class, 'DARK_MODE'))->toBeTrue();
        expect($manager->hasCase(PureFeatureFlag::class, 'MAINTENANCE_MODE'))->toBeTrue();
        expect($manager->hasCase(PureFeatureFlag::class, 'NONEXISTENT'))->toBeFalse();
    });

    it('forSelect returns case count matching enum cases()', function () {
        $manager = new EnumManager;

        expect(count($manager->forSelect(TicketStatus::class)))
            ->toBe(count(TicketStatus::cases()));

        expect(count($manager->forSelect(Priority::class)))
            ->toBe(count(Priority::cases()));

        expect(count($manager->forSelect(PureFeatureFlag::class)))
            ->toBe(count(PureFeatureFlag::cases()));
    });

    it('forApi returns case count matching enum cases()', function () {
        $manager = new EnumManager;

        expect(count($manager->forApi(TicketStatus::class)))
            ->toBe(count(TicketStatus::cases()));

        expect(count($manager->forApi(IntBackedPriority::class)))
            ->toBe(count(IntBackedPriority::cases()));
    });

    it('forSelect values are unique', function () {
        $manager = new EnumManager;
        $result = $manager->forSelect(TicketStatus::class);
        $values = array_column($result, 'value');

        expect($values)->toEqual(array_unique($values));
    });

    it('forApi values match forSelect values in order', function () {
        $manager = new EnumManager;
        $selectValues = array_column($manager->forSelect(TicketStatus::class), 'value');
        $apiValues = array_column($manager->forApi(TicketStatus::class), 'value');

        expect($selectValues)->toBe($apiValues);
    });

    it('forApi description is null for cases without description', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(Priority::class);

        // Priority has no descriptions defined
        foreach ($result as $item) {
            expect($item['description'])->toBeNull();
        }
    });

    it('forApi description is string for cases with description', function () {
        $manager = new EnumManager;
        $result = $manager->forApi(TicketStatus::class);

        // OPEN has a description
        $open = array_first($result, fn ($item) => $item['name'] === 'OPEN');
        expect($open)->not->toBeNull();
        expect($open['description'])->toBeString()->not->toBeEmpty();
    });
});

/**
 * Plain enum without HasEnumMetadata — used for negative tests.
 */
enum PlainEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}
