<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroPriority;

/**
 * Verifies that HasEnumMetadata trait provides all expected public API methods
 * with correct signatures across all enum types (string-backed, int-backed, pure).
 *
 * This test acts as a contract compliance check — if a method is renamed,
 * removed, or has its signature changed, this test will fail immediately.
 */
describe('HasEnumMetadata Trait Contract Compliance', function () {
    describe('String-Backed Enum (UserStatus)', function () {
        it('has all expected instance methods with correct return types', function () {
            $case = UserStatus::ACTIVE;

            // label() returns string
            expect($case->label())->toBeString();

            // description() returns ?string
            $desc = $case->description();
            expect($desc)->toBeNull()->or()->toBeString();

            // color() returns string
            expect($case->color())->toBeString();

            // icon() returns ?string
            $icon = $case->icon();
            expect($icon)->toBeNull()->or()->toBeString();

            // is() accepts self and string
            expect($case->is(UserStatus::ACTIVE))->toBeBool();
            expect($case->is('ACTIVE'))->toBeBool();

            // isNot() accepts self and string
            expect($case->isNot(UserStatus::BANNED))->toBeBool();
            expect($case->isNot('BANNED'))->toBeBool();

            // in() accepts array of self|string
            expect($case->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeBool();
            expect($case->in(['ACTIVE', 'PENDING']))->toBeBool();
            expect($case->in([]))->toBeBool();
        });

        it('has all expected static methods with correct return types', function () {
            // forSelect() returns non-empty array with value+label keys
            $select = UserStatus::forSelect();
            expect($select)->toBeArray()->not->toBeEmpty();
            expect($select[0])->toHaveKeys(['value', 'label']);

            // forApi() returns non-empty array with full metadata keys
            $api = UserStatus::forApi();
            expect($api)->toBeArray()->not->toBeEmpty();
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);

            // values() returns list of strings
            $values = UserStatus::values();
            expect($values)->toBeArray();
            expect($values)->toHaveCount(count(UserStatus::cases()));

            // labels() returns list of strings
            $labels = UserStatus::labels();
            expect($labels)->toBeArray();
            expect($labels)->toHaveCount(count(UserStatus::cases()));
            expect($labels)->each->toBeString();
        });

        it('has all expected lookup methods', function () {
            // tryFromLabel() returns ?static
            $byLabel = UserStatus::tryFromLabel('Active User');
            expect($byLabel)->toBeInstanceOf(UserStatus::class);
            expect(UserStatus::tryFromLabel('nonexistent'))->toBeNull();

            // tryFromName() returns ?static
            expect(UserStatus::tryFromName('ACTIVE'))->toBeInstanceOf(UserStatus::class);
            expect(UserStatus::tryFromName('NONEXISTENT'))->toBeNull();

            // fromName() returns static (throws on failure)
            expect(UserStatus::fromName('ACTIVE'))->toBeInstanceOf(UserStatus::class);
            expect(fn () => UserStatus::fromName('NONEXISTENT'))->toThrow(InvalidEnumException::class);

            // hasCase() returns bool
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
        });
    });

    describe('Int-Backed Enum (IntBackedPriority)', function () {
        it('provides correct values() with int backed values', function () {
            $values = IntBackedPriority::values();
            expect($values)->toBeArray();
            expect($values)->each->toBeInt();
        });

        it('provides correct forSelect() with int values', function () {
            $select = IntBackedPriority::forSelect();
            expect($select)->toBeArray()->not->toBeEmpty();
            expect($select[0])->toHaveKeys(['value', 'label']);
            expect($select[0]['value'])->toBeInt();
        });

        it('provides correct forApi() with int values', function () {
            $api = IntBackedPriority::forApi();
            expect($api)->toBeArray()->not->toBeEmpty();
            expect($api[0])->toHaveKey('value');
            expect($api[0]['value'])->toBeInt();
        });

        it('supports is() comparison with string case names', function () {
            expect(IntBackedPriority::LOW->is('LOW'))->toBeTrue();
            expect(IntBackedPriority::LOW->is('HIGH'))->toBeFalse();
        });

        it('supports tryFromName() lookup', function () {
            expect(IntBackedPriority::tryFromName('LOW'))->toBeInstanceOf(IntBackedPriority::class);
            expect(IntBackedPriority::tryFromName('NONEXISTENT'))->toBeNull();
        });
    });

    describe('Pure Enum (PureFeatureFlag)', function () {
        it('provides values() with case names instead of backed values', function () {
            $values = PureFeatureFlag::values();
            expect($values)->toBeArray();
            expect($values)->each->toBeString();
            // Pure enums return case names
            expect($values)->toBe(array_map(
                fn (\UnitEnum $c): string => $c->name,
                PureFeatureFlag::cases()
            ));
        });

        it('provides correct forSelect() with case names as values', function () {
            $select = PureFeatureFlag::forSelect();
            expect($select)->toBeArray()->not->toBeEmpty();
            expect($select[0])->toHaveKeys(['value', 'label']);
            expect($select[0]['value'])->toBeString();
        });

        it('provides correct forApi() with case names as values', function () {
            $api = PureFeatureFlag::forApi();
            expect($api)->toBeArray()->not->toBeEmpty();
            expect($api[0])->toHaveKey('value');
            expect($api[0]['value'])->toBeString();
            expect($api[0]['name'])->toBeString();
        });

        it('supports is() comparison', function () {
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->is(PureFeatureFlag::TWO_FACTOR_AUTH))->toBeTrue();
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->is('TWO_FACTOR_AUTH'))->toBeTrue();
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->isNot(PureFeatureFlag::DARK_MODE))->toBeTrue();
        });

        it('supports in() group matching', function () {
            expect(PureFeatureFlag::TWO_FACTOR_AUTH->in(['TWO_FACTOR_AUTH', 'DARK_MODE']))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->in(['TWO_FACTOR_AUTH']))->toBeFalse();
        });
    });

    describe('Edge Case: Zero-Value Int-Backed Enum', function () {
        it('correctly handles zero as a valid backed value', function () {
            expect(ZeroPriority::NONE->value)->toBe(0);
            expect(ZeroPriority::values())->toContain(0);
            expect(ZeroPriority::tryFromName('NONE'))->toBeInstanceOf(ZeroPriority::class);
            expect(ZeroPriority::NONE->label())->toBeString()->not->toBeEmpty();
        });
    });

    describe('Edge Case: Single-Case Enum', function () {
        it('works correctly with only one case', function () {
            expect(SingleCaseEnum::cases())->toHaveCount(1);
            expect(SingleCaseEnum::ONLY->label())->toBeString();
            expect(SingleCaseEnum::forSelect())->toHaveCount(1);
            expect(SingleCaseEnum::forApi())->toHaveCount(1);
            expect(SingleCaseEnum::tryFromName('ONLY'))->toBeInstanceOf(SingleCaseEnum::class);
            expect(SingleCaseEnum::hasCase('ONLY'))->toBeTrue();
            expect(SingleCaseEnum::hasCase('NONEXISTENT'))->toBeFalse();
        });
    });

    describe('Edge Case: CamelCase Enum', function () {
        it('auto-generates Title Case labels from camelCase names', function () {
            foreach (CamelCaseRole::cases() as $case) {
                $label = $case->label();
                // Auto-generated labels should not contain underscores
                expect($label)->toBeString()->not->toBeEmpty();
                // Should have spaces between words
                if (preg_match('/[a-z][A-Z]/', $case->name)) {
                    expect($label)->toContain(' ');
                }
            }
        });
    });

    describe('Cross-Fixture Consistency', function () {
        it('forSelect values are always unique per enum', function () {
            $enums = [
                UserStatus::class,
                OrderStatus::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($enums as $enumClass) {
                $values = array_column($enumClass::forSelect(), 'value');
                expect($values)->toBeArray();
                expect(array_unique($values))->toHaveCount(count($values));
            }
        });

        it('forSelect count matches cases count for all fixtures', function () {
            $enums = [
                UserStatus::class,
                OrderStatus::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
                CamelCaseRole::class,
                SingleCaseEnum::class,
                ZeroPriority::class,
            ];

            foreach ($enums as $enumClass) {
                expect($enumClass::forSelect())->toHaveCount(count($enumClass::cases()));
                expect($enumClass::forApi())->toHaveCount(count($enumClass::cases()));
                expect($enumClass::values())->toHaveCount(count($enumClass::cases()));
                expect($enumClass::labels())->toHaveCount(count($enumClass::cases()));
            }
        });

        it('color() always returns a non-empty string', function () {
            $enums = [
                UserStatus::class,
                OrderStatus::class,
                IntBackedPriority::class,
                PureFeatureFlag::class,
            ];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    $color = $case->color();
                    expect($color)->toBeString()->not->toBeEmpty();
                }
            }
        });
    });
});
