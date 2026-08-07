<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Attributes\Color;
use ZeroBoiler\Enums\Attributes\Description;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\Enums\Attributes\EnumDescription;
use ZeroBoiler\Enums\Attributes\EnumIcon;
use ZeroBoiler\Enums\Attributes\EnumLabel;
use ZeroBoiler\Enums\Attributes\Icon;
use ZeroBoiler\Enums\Attributes\Label;
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Resolution Priority', function (): void {
    it('per-case Label overrides class-level EnumLabel', function (): void {
        expect(UserStatus::ACTIVE->label())->toBe('Active User');
    });

    it('falls back to auto-generated label when neither per-case nor class-level label exists', function (): void {
        expect(UserStatus::INACTIVE->label())->toBe('Inactive');
    });

    it('per-case Color overrides class-level EnumColor', function (): void {
        expect(UserStatus::BANNED->color())->toBe('danger');
    });

    it('class-level EnumColor applies when no per-case override exists', function (): void {
        expect(UserStatus::ACTIVE->color())->toBe('success');
    });
});

describe('Label Generation Edge Cases', function (): void {
    it('generates correct label for SCREAMING_SNAKE_CASE with multiple consecutive underscores', function (): void {
        // OrderStatus::CANCELLED → 'Cancelled'
        expect(OrderStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('generates correct label for short names', function (): void {
        // Priority::LOW → 'Low'
        expect(Priority::LOW->label())->toBe('Low');
    });

    it('generates correct label for camelCase names', function (): void {
        // CamelCaseRole::isActive → 'Is Active' (case name, not value)
        expect(CamelCaseRole::isActive->label())->toBe('Is Active');
        expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
        expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
        expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
    });
});

describe('Default Values', function (): void {
    it('color() defaults to secondary when no attributes defined', function (): void {
        expect(OrderStatus::PENDING->color())->toBe('secondary');
        expect(OrderStatus::SHIPPED->color())->toBe('secondary');
        expect(OrderStatus::DELIVERED->color())->toBe('secondary');
    });

    it('icon() defaults to null when not defined', function (): void {
        expect(OrderStatus::PENDING->icon())->toBeNull();
    });

    it('description() defaults to null when not defined', function (): void {
        expect(OrderStatus::PENDING->description())->toBeNull();
    });
});

describe('Int-Backed Enum Specifics', function (): void {
    it('forSelect returns int values as keys', function (): void {
        $options = Priority::forSelect();

        expect($options)->toHaveCount(4);
        expect($options[0]['value'])->toBe(1);
        expect($options[1]['value'])->toBe(2);
        expect($options[2]['value'])->toBe(3);
        expect($options[3]['value'])->toBe(4);
    });

    it('forApi returns int values', function (): void {
        $api = Priority::forApi();

        expect($api[0]['value'])->toBe(1);
        expect($api[0]['name'])->toBe('LOW');
    });

    it('values() returns int values', function (): void {
        $values = Priority::values();

        expect($values)->toBe([1, 2, 3, 4]);
    });

    it('handles zero value correctly', function (): void {
        expect(ZeroPriority::NONE->value)->toBe(0);
        expect(ZeroPriority::NONE->label())->toBe('None');
        expect(ZeroPriority::forSelect()[0]['value'])->toBe(0);
    });
});

describe('Single-Case Enum Edge Case', function (): void {
    it('generates forSelect with one entry', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toBe(['value' => 'only', 'label' => 'Only']);
    });

    it('generates forApi with one entry', function (): void {
        $api = SingleCaseEnum::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0]['name'])->toBe('ONLY');
        expect($api[0]['value'])->toBe('only');
    });

    it('in() with single-element array works', function (): void {
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
    });

    it('in() with empty array returns false', function (): void {
        expect(SingleCaseEnum::ONLY->in([]))->toBeFalse();
    });
});

describe('Comparison Method Type Safety', function (): void {
    it('is() correctly identifies same instance', function (): void {
        $active = UserStatus::ACTIVE;
        $alsoActive = UserStatus::ACTIVE;

        expect($active->is($alsoActive))->toBeTrue();
    });

    it('is() correctly rejects different case', function (): void {
        expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
    });

    it('is() with string compares case name exactly', function (): void {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
        expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
    });

    it('isNot() is exact negation of is()', function (): void {
        $status = UserStatus::BANNED;

        expect($status->isNot(UserStatus::BANNED))->toBeFalse();
        expect($status->isNot(UserStatus::ACTIVE))->toBeTrue();
        expect($status->isNot('BANNED'))->toBeFalse();
        expect($status->isNot('ACTIVE'))->toBeTrue();
    });

    it('in() with all same-type instances returns true for match', function (): void {
        $status = UserStatus::PENDING;

        expect($status->in([UserStatus::ACTIVE, UserStatus::PENDING, UserStatus::BANNED]))->toBeTrue();
        expect($status->in([UserStatus::ACTIVE, UserStatus::BANNED]))->toBeFalse();
    });

    it('in() with mixed instances and strings', function (): void {
        $status = UserStatus::SUSPENDED;

        expect($status->in([UserStatus::ACTIVE, 'SUSPENDED']))->toBeTrue();
        expect($status->in(['ACTIVE', 'PENDING']))->toBeFalse();
    });
});

describe('Lookup Method Type Safety', function (): void {
    it('tryFromName is case-sensitive', function (): void {
        expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromName('active'))->toBeNull();
        expect(UserStatus::tryFromName('Active'))->toBeNull();
    });

    it('tryFromLabel is case-insensitive', function (): void {
        expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
    });

    it('fromName throws InvalidEnumException for non-existent case', function (): void {
        expect(fn () => UserStatus::fromName('NON_EXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase returns correct boolean', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('NON_EXISTENT'))->toBeFalse();
    });
});

describe('Cache Behaviour Guarantees', function (): void {
    it('label() is deterministic across repeated calls', function (): void {
        $label1 = UserStatus::ACTIVE->label();
        $label2 = UserStatus::ACTIVE->label();
        $label3 = UserStatus::ACTIVE->label();

        expect($label1)->toBe($label2);
        expect($label2)->toBe($label3);
    });

    it('forApi() returns identical structure across calls', function (): void {
        $api1 = OrderStatus::forApi();
        $api2 = OrderStatus::forApi();

        expect($api1)->toBe($api2);
    });

    it('values() returns consistent order', function (): void {
        $values1 = Priority::values();
        $values2 = Priority::values();

        expect($values1)->toBe($values2);
        expect($values1)->toBe([1, 2, 3, 4]); // declaration order
    });
});
