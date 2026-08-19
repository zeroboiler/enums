<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\EdgeCaseNamingEnum;

describe('HasEnumMetadata — comparison methods across all enum types', function (): void {
    describe('is() with instance and string', function (): void {
        it('compares string-backed enum instances correctly', function (): void {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is(UserStatus::BANNED))->toBeFalse();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('BANNED'))->toBeFalse();
        });

        it('compares int-backed enum instances correctly', function (): void {
            expect(IntBackedPriority::HIGH->is(IntBackedPriority::HIGH))->toBeTrue();
            expect(IntBackedPriority::HIGH->is(IntBackedPriority::LOW))->toBeFalse();
            expect(IntBackedPriority::HIGH->is('HIGH'))->toBeTrue();
        });

        it('compares pure enum instances correctly', function (): void {
            expect(PureFeatureFlag::ON->is(PureFeatureFlag::ON))->toBeTrue();
            expect(PureFeatureFlag::ON->is(PureFeatureFlag::OFF))->toBeFalse();
            expect(PureFeatureFlag::ON->is('ON'))->toBeTrue();
        });

        it('is() is case-sensitive for string names', function (): void {
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });
    });

    describe('in() and notIn() with mixed types', function (): void {
        it('in() matches any given case', function (): void {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::BANNED->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeFalse();
        });

        it('in() accepts string names', function (): void {
            expect(IntBackedPriority::HIGH->in(['HIGH', 'MEDIUM']))->toBeTrue();
            expect(IntBackedPriority::LOW->in(['HIGH', 'MEDIUM']))->toBeFalse();
        });

        it('in() accepts mixed instances and strings', function (): void {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, 'PENDING']))->toBeTrue();
        });

        it('in() returns false for empty array', function (): void {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('notIn() is the inverse of in()', function (): void {
            expect(UserStatus::ACTIVE->notIn([UserStatus::BANNED]))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn([UserStatus::ACTIVE]))->toBeFalse();
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });
    });

    describe('toValue() across all enum types', function (): void {
        it('returns string value for string-backed enums', function (): void {
            expect(UserStatus::ACTIVE->toValue())->toBe('active');
            expect(UserStatus::BANNED->toValue())->toBe('banned');
        });

        it('returns int value for int-backed enums', function (): void {
            expect(IntBackedPriority::LOW->toValue())->toBeInt();
            expect(ZeroPriority::NONE->toValue())->toBe(0);
        });

        it('returns case name for pure enums', function (): void {
            expect(PureFeatureFlag::ON->toValue())->toBe('ON');
            expect(PureFeatureFlag::OFF->toValue())->toBe('OFF');
        });
    });

    describe('values() across all enum types', function (): void {
        it('returns string values for string-backed enums', function (): void {
            $values = UserStatus::values();
            expect($values)->toContain('active');
            expect($values)->toContain('banned');
            expect($values)->not->toContain('ACTIVE');
        });

        it('returns int values for int-backed enums', function (): void {
            $values = IntBackedPriority::values();
            foreach ($values as $v) {
                expect($v)->toBeInt();
            }
        });

        it('returns case names for pure enums', function (): void {
            $values = PureFeatureFlag::values();
            expect($values)->toContain('ON');
            expect($values)->toContain('OFF');
        });

        it('values count matches cases count', function (): void {
            expect(UserStatus::values())->toHaveCount(count(UserStatus::cases()));
            expect(IntBackedPriority::values())->toHaveCount(count(IntBackedPriority::cases()));
            expect(PureFeatureFlag::values())->toHaveCount(count(PureFeatureFlag::cases()));
        });
    });

    describe('tryFromLabel edge cases', function (): void {
        it('returns null for empty string', function (): void {
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('returns null for whitespace-only string', function (): void {
            expect(UserStatus::tryFromLabel('   '))->toBeNull();
        });

        it('is case-insensitive with mixed case labels', function (): void {
            $label = UserStatus::ACTIVE->label();
            expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(ucwords(strtolower($label))))->toBe(UserStatus::ACTIVE);
        });

        it('returns the first match when multiple cases share a label', function (): void {
            $case = UserStatus::tryFromLabel(UserStatus::ACTIVE->label());
            expect($case)->toBe(UserStatus::ACTIVE);
        });
    });

    describe('label auto-generation edge cases', function (): void {
        it('generates correct label for SCREAMING_SNAKE_CASE', function (): void {
            $label = UserStatus::INACTIVE->label();
            expect($label)->toBe('Inactive');
        });

        it('generates correct label for camelCase names', function (): void {
            $label = CamelCaseRole::isActive->label();
            expect($label)->toBe('Is Active');
        });

        it('handles single-word SCREAMING_SNAKE', function (): void {
            $label = PureFeatureFlag::ON->label();
            expect($label)->toBe('On');
        });
    });

    describe('forSelect and forApi structural contracts', function (): void {
        it('forSelect returns value/label pairs with correct types', function (): void {
            $select = UserStatus::forSelect();

            foreach ($select as $option) {
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi returns complete metadata for all cases', function (): void {
            $api = UserStatus::forApi();

            foreach ($api as $item) {
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forSelect and forApi have the same case count', function (): void {
            expect(UserStatus::forSelect())->toHaveCount(count(UserStatus::cases()));
            expect(UserStatus::forApi())->toHaveCount(count(UserStatus::cases()));
        });

        it('forApi color is never null or empty', function (): void {
            $api = IntBackedPriority::forApi();
            foreach ($api as $item) {
                expect($item['color'])->toBeString();
                expect(strlen($item['color']))->toBeGreaterThan(0);
            }
        });
    });

    describe('zero-backed enum edge cases', function (): void {
        it('handles zero as a valid backed value', function (): void {
            expect(ZeroPriority::NONE->toValue())->toBe(0);
            expect(ZeroBackedPriority::ZERO->toValue())->toBe(0);
        });

        it('tryFromName works with zero-valued cases', function (): void {
            expect(ZeroPriority::tryFromName('NONE'))->toBe(ZeroPriority::NONE);
        });

        it('hasCase works with zero-valued cases', function (): void {
            expect(ZeroPriority::hasCase('NONE'))->toBeTrue();
        });
    });
});
