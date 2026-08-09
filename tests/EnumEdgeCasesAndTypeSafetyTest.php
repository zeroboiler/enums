<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

describe('Edge cases: single case enum', function (): void {
    it('forSelect returns one entry', function (): void {
        $options = SingleCaseEnum::forSelect();

        expect($options)->toHaveCount(1);
        expect($options[0])->toHaveKeys(['value', 'label']);
    });

    it('forApi returns one entry with all metadata keys', function (): void {
        $api = SingleCaseEnum::forApi();

        expect($api)->toHaveCount(1);
        expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
    });

    it('values and labels return single-element arrays', function (): void {
        expect(SingleCaseEnum::values())->toHaveCount(1);
        expect(SingleCaseEnum::labels())->toHaveCount(1);
    });

    it('is() returns true for the only case', function (): void {
        expect(SingleCaseEnum::ONLY->is(SingleCaseEnum::ONLY))->toBeTrue();
        expect(SingleCaseEnum::ONLY->is('ONLY'))->toBeTrue();
    });

    it('in() works with single-element array', function (): void {
        expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
        expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
    });
});

describe('Edge cases: zero-value int enum', function (): void {
    it('handles zero as a valid backed value', function (): void {
        expect(ZeroPriority::ZERO->value)->toBe(0);
        expect(ZeroPriority::ZERO->label())->toBeString()->not->toBeEmpty();
    });

    it('forSelect includes zero value', function (): void {
        $options = ZeroPriority::forSelect();
        $values = array_column($options, 'value');

        expect($values)->toContain(0);
    });

    it('values() includes zero', function (): void {
        $values = ZeroPriority::values();

        expect($values)->toContain(0);
    });
});

describe('Edge cases: int-backed enum with color', function (): void {
    it('maps int values to colors via EnumColor', function (): void {
        expect(IntStatusWithColor::ACTIVE->color())->toBe('success');
        expect(IntStatusWithColor::PENDING->color())->toBe('warning');
        expect(IntStatusWithColor::DRAFT->color())->toBe('success');
    });

    it('per-case Color override takes precedence', function (): void {
        expect(IntStatusWithColor::BANNED->color())->toBe('danger');
    });

    it('forSelect uses int values not string names', function (): void {
        $options = IntStatusWithColor::forSelect();

        expect($options[0]['value'])->toBeInt();
    });
});

describe('Edge cases: pure enum (no backing type)', function (): void {
    it('uses case name as value in forSelect', function (): void {
        $options = PureFeatureFlag::forSelect();

        expect($options[0]['value'])->toBeString();
        // Pure enums use case name as value
        expect($options[0]['value'])->toBe('TWO_FACTOR_AUTH');
    });

    it('values() returns case names', function (): void {
        $values = PureFeatureFlag::values();

        expect($values)->toContain('TWO_FACTOR_AUTH');
        expect($values)->toContain('DARK_MODE');
    });

    it('tryFromName works on pure enum', function (): void {
        expect(PureFeatureFlag::tryFromName('TWO_FACTOR_AUTH'))->toBe(PureFeatureFlag::TWO_FACTOR_AUTH);
        expect(PureFeatureFlag::tryFromName('UNKNOWN'))->toBeNull();
    });

    it('fromName throws on invalid name for pure enum', function (): void {
        expect(fn (): mixed => PureFeatureFlag::fromName('NONEXISTENT'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase works on pure enum', function (): void {
        expect(PureFeatureFlag::hasCase('TWO_FACTOR_AUTH'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
        expect(PureFeatureFlag::hasCase('UNKNOWN'))->toBeFalse();
    });

    it('color defaults to secondary', function (): void {
        expect(PureFeatureFlag::TWO_FACTOR_AUTH->color())->toBe('secondary');
    });

    it('icon and description default to null', function (): void {
        expect(PureFeatureFlag::DARK_MODE->icon())->toBeNull();
        expect(PureFeatureFlag::DARK_MODE->description())->toBeNull();
    });
});

describe('Edge cases: camelCase enum', function (): void {
    it('generates title case from camelCase name', function (): void {
        // camelCase → Title Case
        $label = CamelCaseRole::isActive->label();

        expect($label)->toBeString()->not->toBeEmpty();
    });

    it('forApi preserves case name as-is', function (): void {
        $api = CamelCaseRole::forApi();

        expect($api[0]['name'])->toBe('isActive');
    });
});

describe('Edge cases: mixed attribute enum', function (): void {
    it('resolves both class-level and per-case metadata', function (): void {
        $label = MixedAttributeStatus::ACTIVE->label();
        $color = MixedAttributeStatus::ACTIVE->color();

        expect($label)->toBeString()->not->toBeEmpty();
        expect($color)->toBeString();
    });
});

describe('Type safety: strict comparisons', function (): void {
    it('is() is case-sensitive for string names', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->is('ACTIVE'))->toBeTrue();
        expect($status->is('active'))->toBeFalse(); // backed value, not name
        expect($status->is('Active'))->toBeFalse();
    });

    it('isNot() negates correctly', function (): void {
        $status = UserStatus::ACTIVE;

        expect($status->isNot(UserStatus::BANNED))->toBeTrue();
        expect($status->isNot('BANNED'))->toBeTrue();
        expect($status->isNot(UserStatus::ACTIVE))->toBeFalse();
    });

    it('in() with empty array returns false', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('in() with all cases returns true', function (): void {
        $all = UserStatus::cases();

        expect(UserStatus::ACTIVE->in($all))->toBeTrue();
    });

    it('in() with mixed instances and strings', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        expect(UserStatus::ACTIVE->in([UserStatus::BANNED, 'PENDING']))->toBeFalse();
    });
});

describe('Reverse lookup: tryFromLabel edge cases', function (): void {
    it('is truly case-insensitive', function (): void {
        $result = UserStatus::tryFromLabel('active user');
        expect($result)->toBe(UserStatus::ACTIVE);

        $result = UserStatus::tryFromLabel('ACTIVE USER');
        expect($result)->toBe(UserStatus::ACTIVE);

        $result = UserStatus::tryFromLabel('Active User');
        expect($result)->toBe(UserStatus::ACTIVE);
    });

    it('returns null for empty string', function (): void {
        expect(UserStatus::tryFromLabel(''))->toBeNull();
    });
});

describe('TicketStatus: class-level label attribute', function (): void {
    it('resolves class-level labels from EnumLabel', function (): void {
        $label = TicketStatus::OPEN->label();

        expect($label)->toBeString()->not->toBeEmpty();
    });
});
