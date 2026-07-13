<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\AmbiguousLabelException;
use ZeroBoiler\Enums\Tests\Fixtures\AmbiguousStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('UserStatus enum (full attributes)', function (): void {
    it('has correct per-case label', function (): void {
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
        expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
    });

    it('auto-generates label when no attribute', function (): void {
        expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        expect(UserStatus::SUSPENDED->label())->toBe('Suspended');
    });

    it('resolves color from class-level EnumColor', function (): void {
        expect(UserStatus::ACTIVE->color())->toBe('success');
        expect(UserStatus::SUSPENDED->color())->toBe('warning');
        expect(UserStatus::PENDING->color())->toBe('warning');
    });

    it('resolves color from per-case Color override', function (): void {
        expect(UserStatus::BANNED->color())->toBe('danger');
    });

    it('resolves per-case icon', function (): void {
        expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
    });

    it('returns null icon when not set', function (): void {
        expect(UserStatus::INACTIVE->icon())->toBeNull();
    });

    it('resolves per-case description', function (): void {
        expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        expect(UserStatus::BANNED->description())->toBe('User is permanently banned');
    });

    it('returns null description when not set', function (): void {
        expect(UserStatus::INACTIVE->description())->toBeNull();
    });
});

describe('UserStatus bulk methods', function (): void {
    it('generates forSelect array', function (): void {
        $options = UserStatus::forSelect();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(5);
        expect($options[0])->toHaveKeys(['value', 'label']);
        expect($options[0]['value'])->toBe('active');
        expect($options[0]['label'])->toBe('Active User');
    });

    it('generates forApi array with full metadata', function (): void {
        $api = UserStatus::forApi();

        expect($api)->toHaveCount(5);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        expect($api[0]['value'])->toBe('active');
        expect($api[0]['name'])->toBe('ACTIVE');
        expect($api[0]['label'])->toBe('Active User');
        expect($api[0]['color'])->toBe('success');
        expect($api[0]['icon'])->toBe('heroicon-o-check-circle');
    });

    it('returns all values', function (): void {
        $values = UserStatus::values();

        expect($values)->toBe(['active', 'inactive', 'pending', 'suspended', 'banned']);
    });

    it('returns all labels', function (): void {
        $labels = UserStatus::labels();

        expect($labels)->toHaveCount(5);
        expect($labels[0])->toBe('Active User');
    });

    it('performs reverse label lookup', function (): void {
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('Inactive'))->toBe(UserStatus::INACTIVE);
    });

    it('returns null for unknown label', function (): void {
        expect(UserStatus::tryFromLabel('Unknown'))->toBeNull();
    });

    it('reverse label lookup is case-insensitive', function (): void {
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
    });

    it('returns null for unknown label in strict mode', function (): void {
        expect(UserStatus::tryFromLabel('ACTIVE USER', strict: true))->toBeNull();
    });

    it('matches case-sensitively in strict mode', function (): void {
        expect(UserStatus::tryFromLabel('Active User', strict: true))->toBe(UserStatus::ACTIVE);
    });
});

describe('AmbiguousStatus enum (ambiguous labels)', function (): void {
    it('throws on ambiguous case-insensitive match', function (): void {
        expect(fn () => AmbiguousStatus::tryFromLabel('new'))
            ->toThrow(AmbiguousLabelException::class);
    });

    it('exact match wins over case-insensitive match', function (): void {
        expect(AmbiguousStatus::tryFromLabel('NEW'))->toBe(AmbiguousStatus::NEW_ORDER);
        expect(AmbiguousStatus::tryFromLabel('New'))->toBe(AmbiguousStatus::NEW_ITEM);
    });

    it('strict mode avoids ambiguity exception', function (): void {
        expect(fn () => AmbiguousStatus::tryFromLabel('new', strict: true))
            ->not->toThrow(AmbiguousLabelException::class);
    });

    it('returns null for unknown label', function (): void {
        expect(AmbiguousStatus::tryFromLabel('Nonexistent'))->toBeNull();
    });
});

describe('OrderStatus enum (minimal, no attributes)', function (): void {
    it('auto-generates labels from case names', function (): void {
        expect(OrderStatus::PENDING->label())->toBe('Pending');
        expect(OrderStatus::SHIPPED->label())->toBe('Shipped');
        expect(OrderStatus::DELIVERED->label())->toBe('Delivered');
        expect(OrderStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('defaults color to secondary', function (): void {
        expect(OrderStatus::PENDING->color())->toBe('secondary');
    });

    it('returns null for icon and description', function (): void {
        expect(OrderStatus::PENDING->icon())->toBeNull();
        expect(OrderStatus::PENDING->description())->toBeNull();
    });

    it('generates forSelect with auto-labels', function (): void {
        $options = OrderStatus::forSelect();

        expect($options)->toHaveCount(4);
        expect($options[0])->toBe(['value' => 'pending', 'label' => 'Pending']);
    });
});

describe('Priority enum (int-backed)', function (): void {
    it('works with int values', function (): void {
        expect(Priority::LOW->value)->toBe(1);
        expect(Priority::URGENT->value)->toBe(4);
    });

    it('auto-generates labels', function (): void {
        expect(Priority::LOW->label())->toBe('Low');
        expect(Priority::HIGH->label())->toBe('High');
    });

    it('forSelect uses int values', function (): void {
        $options = Priority::forSelect();

        expect($options[0]['value'])->toBe(1);
        expect($options[3]['value'])->toBe(4);
    });

    it('forApi returns int values', function (): void {
        $api = Priority::forApi();

        expect($api[0]['value'])->toBe(1);
        expect($api[0]['name'])->toBe('LOW');
    });
});

describe('Caching', function (): void {
    it('caches metadata across calls', function (): void {
        // First call populates cache
        $label1 = UserStatus::ACTIVE->label();
        $label2 = UserStatus::ACTIVE->label();

        expect($label1)->toBe($label2);
    });
});
