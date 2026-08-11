<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('fromName() throwing behavior', function (): void {
    it('throws InvalidEnumException for unknown case name', function (): void {
        UserStatus::fromName('NONEXISTENT');
    })->throws(InvalidEnumException::class);

    it('throws with class name in message', function (): void {
        try {
            UserStatus::fromName('UNKNOWN');
            expect(true)->toBeFalse(); // should not reach
        } catch (InvalidEnumException $e) {
            expect($e->getMessage())->toContain('UserStatus');
            expect($e->getMessage())->toContain('UNKNOWN');
        }
    });
});

describe('hasCase() edge cases', function (): void {
    it('returns false for empty string', function (): void {
        expect(UserStatus::hasCase(''))->toBeFalse();
    });

    it('returns false for lowercase case name', function (): void {
        expect(UserStatus::hasCase('active'))->toBeFalse();
    });

    it('returns true for valid case name', function (): void {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
    });

    it('returns true for all known cases', function (): void {
        foreach (UserStatus::cases() as $case) {
            expect(UserStatus::hasCase($case->name))->toBeTrue();
        }
    });
});

describe('is() with mixed types', function (): void {
    it('compares enum instance with string name', function (): void {
        expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
        expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
    });

    it('compares enum instance with another instance', function (): void {
        expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
    });

    it('isNot negates correctly with instances', function (): void {
        expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
        expect(UserStatus::ACTIVE->isNot(UserStatus::ACTIVE))->toBeFalse();
    });

    it('isNot negates correctly with strings', function (): void {
        expect(UserStatus::ACTIVE->isNot('BANNED'))->toBeTrue();
        expect(UserStatus::ACTIVE->isNot('ACTIVE'))->toBeFalse();
    });
});

describe('in() with mixed instances and strings', function (): void {
    it('returns true when instance matches', function (): void {
        expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::BANNED]))->toBeTrue();
    });

    it('returns true when string name matches', function (): void {
        expect(UserStatus::ACTIVE->in(['ACTIVE', 'BANNED']))->toBeTrue();
    });

    it('returns false when no match', function (): void {
        expect(UserStatus::ACTIVE->in(['BANNED', 'SUSPENDED']))->toBeFalse();
    });

    it('works with empty array', function (): void {
        expect(UserStatus::ACTIVE->in([]))->toBeFalse();
    });

    it('handles mixed instance and string list', function (): void {
        expect(UserStatus::BANNED->in([UserStatus::ACTIVE, 'BANNED']))->toBeTrue();
    });
});

describe('CamelCaseRole auto-label generation', function (): void {
    it('converts camelCase to Title Case', function (): void {
        $label = CamelCaseRole::ADMIN->label();
        expect($label)->toBe('Admin');
    });

    it('converts multi-word camelCase', function (): void {
        $label = CamelCaseRole::SUPER_ADMIN->label();
        expect($label)->toBe('Super Admin');
    });
});

describe('IntBackedPriority edge cases', function (): void {
    it('tryFromLabel works with auto-generated labels', function (): void {
        expect(IntBackedPriority::tryFromLabel('Low'))->toBe(IntBackedPriority::LOW);
        expect(IntBackedPriority::tryFromLabel('Critical'))->toBe(IntBackedPriority::CRITICAL);
    });

    it('values() returns int values', function (): void {
        $values = IntBackedPriority::values();
        foreach ($values as $v) {
            expect(is_int($v))->toBeTrue();
        }
    });
});

describe('PureFeatureFlag values/forSelect', function (): void {
    it('values() returns case names', function (): void {
        $values = PureFeatureFlag::values();
        expect($values)->toBe(['TWO_FACTOR_AUTH', 'DARK_MODE']);
    });

    it('forSelect() uses case names as values', function (): void {
        $options = PureFeatureFlag::forSelect();
        expect($options[0])->toBe(['value' => 'TWO_FACTOR_AUTH', 'label' => 'Two Factor Auth']);
    });
});

describe('SingleCaseEnum edge case', function (): void {
    it('has exactly one case', function (): void {
        expect(SingleCaseEnum::cases())->toHaveCount(1);
    });

    it('forSelect returns single option', function (): void {
        $options = SingleCaseEnum::forSelect();
        expect($options)->toHaveCount(1);
    });

    it('labels returns single-element array', function (): void {
        $labels = SingleCaseEnum::labels();
        expect($labels)->toHaveCount(1);
    });

    it('tryFromLabel works for single case', function (): void {
        $result = SingleCaseEnum::tryFromLabel(SingleCaseEnum::ONLY->label());
        expect($result)->toBe(SingleCaseEnum::ONLY);
    });
});
