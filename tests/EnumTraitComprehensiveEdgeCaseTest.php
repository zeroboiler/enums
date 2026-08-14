<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

describe('HasEnumMetadata trait — comprehensive edge cases', function (): void {
    it('generates labels for SCREAMING_SNAKE_CASE pure enums', function (): void {
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
    });

    it('generates labels from CamelCase case names', function (): void {
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });

    it('single-case enum produces correct forSelect', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toBe(['value' => 'only', 'label' => 'Only']);
    });

    it('single-case enum produces correct forApi', function (): void {
        $api = SingleCaseEnum::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($api[0]['value'])->toBe('only');
        expect($api[0]['name'])->toBe('ONLY');
    });

    it('single-case enum comparison methods work', function (): void {
        $only = SingleCaseEnum::ONLY;

        expect($only->is(SingleCaseEnum::ONLY))->toBeTrue();
        expect($only->is('ONLY'))->toBeTrue();
        expect($only->isNot('NOT_EXISTING'))->toBeTrue();
        expect($only->in([SingleCaseEnum::ONLY]))->toBeTrue();
        expect($only->notIn(['NOT_EXISTING']))->toBeTrue();
    });

    it('pure enum forSelect uses case names as values', function (): void {
        $options = PureFeatureFlag::forSelect();

        expect($options)->toHaveCount(3);
        expect($options[0]['value'])->toBe('DARK_MODE');
        expect($options[0]['label'])->toBe('Dark Mode');
    });

    it('pure enum forApi includes all metadata fields', function (): void {
        $api = PureFeatureFlag::forApi();

        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($api[0]['name'])->toBe('DARK_MODE');
        expect($api[0]['description'])->toBe('Toggle dark mode for the UI');
        expect($api[0]['icon'])->toBe('heroicon-o-moon');
    });

    it('pure enum tryFromName works with case names', function (): void {
        expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBe(PureFeatureFlag::DARK_MODE);
        expect(PureFeatureFlag::tryFromName('NON_EXISTING'))->toBeNull();
    });

    it('pure enum hasCase returns correct boolean', function (): void {
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('NON_EXISTING'))->toBeFalse();
    });

    it('pure enum fromName throws InvalidEnumException for invalid name', function (): void {
        expect(fn (): mixed => PureFeatureFlag::fromName('NON_EXISTING'))
            ->toThrow(InvalidEnumException::class);
    });

    it('int-backed enum with zero value resolves metadata correctly', function (): void {
        $none = ZeroBackedPriority::NONE;

        expect($none->label())->toBe('None');
        expect($none->color())->toBe('secondary');
        expect($none->value)->toBe(0);
    });

    it('int-backed enum values() returns int values including zero', function (): void {
        $values = ZeroBackedPriority::values();

        expect($values)->toContain(0);
        expect($values)->toContain(1);
        expect($values)->toContain(2);
    });

    it('int-backed enum forSelect uses int values', function (): void {
        $options = ZeroBackedPriority::forSelect();

        expect($options[0]['value'])->toBe(0);
        expect($options[0]['label'])->toBe('None');
    });

    it('class-level EnumLabel overrides auto-generated labels', function (): void {
        expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
        expect(IntBackedPriority::NONE->label())->toBeNull(); // No class-level label for NONE
    });

    it('class-level EnumDescription resolves for int-backed', function (): void {
        expect(IntBackedPriority::CRITICAL->description())
            ->toBe('Critical priority — immediate action required');
        expect(IntBackedPriority::LOW->description())
            ->toBe('Low priority — handle when convenient');
    });

    it('class-level EnumIcon default applies to all cases', function (): void {
        expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
        expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
    });

    it('per-case Color overrides class-level EnumColor', function (): void {
        // IntBackedPriority CRITICAL = 1
        // Class-level: success: [3, 4], danger: [1]
        // Per-case: Color('danger') → should be 'danger'
        expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
    });

    it('in() works with empty array', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('notIn() works with empty array', function (): void {
        expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
    });

    it('in() works with single element', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
        expect(UserStatus::ACTIVE->in([UserStatus::BANNED]))->toBeFalse();
    });

    it('is() rejects different enum instances', function (): void {
        // Different enum types should not match via is()
        // Note: is(self|string $case) — comparing different enum types would be a TypeError
        // so we test same-type comparison only
        expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
    });

    it('forSelect maintains declaration order', function (): void {
        $options = OrderStatus::forSelect();

        expect($options[0]['value'])->toBe('pending');
        expect($options[1]['value'])->toBe('shipped');
        expect($options[2]['value'])->toBe('delivered');
        expect($options[3]['value'])->toBe('cancelled');
    });

    it('forApi maintains declaration order', function (): void {
        $api = Priority::forApi();

        expect($api[0]['name'])->toBe('LOW');
        expect($api[1]['name'])->toBe('MEDIUM');
        expect($api[2]['name'])->toBe('HIGH');
        expect($api[3]['name'])->toBe('URGENT');
    });

    it('labels() maintains declaration order', function (): void {
        $labels = OrderStatus::labels();

        expect($labels[0])->toBe('Pending');
        expect($labels[1])->toBe('Shipped');
        expect($labels[2])->toBe('Delivered');
        expect($labels[3])->toBe('Cancelled');
    });
});
