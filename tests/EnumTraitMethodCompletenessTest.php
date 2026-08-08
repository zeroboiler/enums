<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\Enums\Tests;

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
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Trait Method Completeness', function () {
    describe('HasEnumMetadata trait methods exist on all fixture enums', function () {
        it('UserStatus has all trait methods available', function () {
            expect(method_exists(UserStatus::class, 'label'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'description'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'color'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'icon'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'forSelect'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'forApi'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'tryFromLabel'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'tryFromName'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'fromName'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'hasCase'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'is'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'isNot'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'in'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'values'))->toBeTrue();
            expect(method_exists(UserStatus::class, 'labels'))->toBeTrue();
        });

        it('Priority (int-backed) has all trait methods available', function () {
            expect(method_exists(Priority::class, 'label'))->toBeTrue();
            expect(method_exists(Priority::class, 'forSelect'))->toBeTrue();
            expect(method_exists(Priority::class, 'values'))->toBeTrue();
            expect(method_exists(Priority::class, 'tryFromLabel'))->toBeTrue();
            expect(method_exists(Priority::class, 'fromName'))->toBeTrue();
        });

        it('PureFeatureFlag (pure enum) has all trait methods available', function () {
            expect(method_exists(PureFeatureFlag::class, 'label'))->toBeTrue();
            expect(method_exists(PureFeatureFlag::class, 'values'))->toBeTrue();
            expect(method_exists(PureFeatureFlag::class, 'forSelect'))->toBeTrue();
            expect(method_exists(PureFeatureFlag::class, 'tryFromName'))->toBeTrue();
        });
    });

    describe('Return type correctness', function () {
        it('label() always returns string', function () {
            foreach (UserStatus::cases() as $case) {
                expect($case->label())->toBeString();
            }
            foreach (Priority::cases() as $case) {
                expect($case->label())->toBeString();
            }
            foreach (PureFeatureFlag::cases() as $case) {
                expect($case->label())->toBeString();
            }
        });

        it('color() always returns string (never null)', function () {
            foreach (UserStatus::cases() as $case) {
                expect($case->color())->toBeString();
            }
            foreach (Priority::cases() as $case) {
                expect($case->color())->toBeString();
            }
            foreach (PureFeatureFlag::cases() as $case) {
                expect($case->color())->toBeString();
            }
        });

        it('description() returns string or null', function () {
            $hasDescription = false;
            $hasNull = false;

            foreach (UserStatus::cases() as $case) {
                $desc = $case->description();
                if ($desc !== null) {
                    expect($desc)->toBeString();
                    $hasDescription = true;
                } else {
                    $hasNull = true;
                }
            }

            // At least one case should have a description, one should be null
            expect($hasDescription)->toBeTrue();
            expect($hasNull)->toBeTrue();
        });

        it('icon() returns string or null', function () {
            $hasIcon = false;
            $hasNull = false;

            foreach (UserStatus::cases() as $case) {
                $icon = $case->icon();
                if ($icon !== null) {
                    expect($icon)->toBeString();
                    $hasIcon = true;
                } else {
                    $hasNull = true;
                }
            }

            expect($hasIcon)->toBeTrue();
            expect($hasNull)->toBeTrue();
        });

        it('forSelect() returns consistent structure', function () {
            $select = UserStatus::forSelect();
            expect($select)->toBeArray();
            expect($select)->toHaveCount(count(UserStatus::cases()));

            foreach ($select as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKeys(['value', 'label']);
                expect($option['value'])->toBeString();
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi() returns consistent structure', function () {
            $api = UserStatus::forApi();
            expect($api)->toBeArray();
            expect($api)->toHaveCount(count(UserStatus::cases()));

            foreach ($api as $item) {
                expect($item)->toBeArray();
                expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect($item['color'])->toBeString();
            }
        });

        it('values() returns correct types for string-backed enum', function () {
            $values = UserStatus::values();
            foreach ($values as $value) {
                expect($value)->toBeString();
            }
        });

        it('values() returns correct types for int-backed enum', function () {
            $values = Priority::values();
            foreach ($values as $value) {
                expect($value)->toBeInt();
            }
        });

        it('values() returns string case names for pure enum', function () {
            $values = PureFeatureFlag::values();
            foreach ($values as $value) {
                expect($value)->toBeString();
            }

            // Case names should match enum case names
            $caseNames = array_map(fn ($c) => $c->name, PureFeatureFlag::cases());
            expect($values)->toBe($caseNames);
        });

        it('labels() returns non-empty strings for all cases', function () {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(count(UserStatus::cases()));

            foreach ($labels as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });
    });

    describe('Edge cases', function () {
        it('is() with non-existent string name returns false', function () {
            expect(UserStatus::ACTIVE->is('NON_EXISTENT_CASE'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse(); // value, not name
        });

        it('isNot() is exact negation of is()', function () {
            $case = UserStatus::ACTIVE;

            foreach (UserStatus::cases() as $other) {
                expect($case->isNot($other))->toBe(! $case->is($other));
            }
        });

        it('in() with empty array returns false', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('in() with single matching element returns true', function () {
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE]))->toBeTrue();
        });

        it('in() works with all cases', function () {
            $allCases = UserStatus::cases();
            foreach ($allCases as $case) {
                expect($case->in($allCases))->toBeTrue();
            }
        });

        it('fromName() throws InvalidEnumException with correct class', function () {
            try {
                UserStatus::fromName('NON_EXISTENT');
                expect(true)->toBeFalse('Should have thrown');
            } catch (InvalidEnumException $e) {
                expect($e->getMessage())->toContain('UserStatus');
                expect($e->getMessage())->toContain('NON_EXISTENT');
            }
        });

        it('tryFromLabel() is truly case-insensitive', function () {
            $label = UserStatus::ACTIVE->label();
            expect($label)->not->toBeEmpty();

            expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel(ucfirst($label)))->toBe(UserStatus::ACTIVE);
        });

        it('hasCase() returns correct boolean for all case names', function () {
            foreach (UserStatus::cases() as $case) {
                expect(UserStatus::hasCase($case->name))->toBeTrue();
            }
            expect(UserStatus::hasCase('DOES_NOT_EXIST'))->toBeFalse();
            expect(UserStatus::hasCase(''))->toBeFalse();
        });

        it('forSelect values are unique', function () {
            $select = UserStatus::forSelect();
            $values = array_column($select, 'value');
            expect($values)->toBeArray();
            expect(array_unique($values))->toBe($values); // all unique
        });

        it('forApi preserves declaration order', function () {
            $api = UserStatus::forApi();
            $names = array_column($api, 'name');
            $expectedNames = array_map(fn ($c) => $c->name, UserStatus::cases());
            expect($names)->toBe($expectedNames);
        });

        it('camelCase enum generates correct label', function () {
            $label = PureFeatureFlag::TWO_FACTOR_AUTH->label();
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
            expect($label)->not->toBe('TWO_FACTOR_AUTH'); // should not be raw case name
        });
    });

    describe('Attribute resolution priority', function () {
        it('per-case Label overrides class-level EnumLabel', function () {
            // ACTIVE has #[Label('Active User')] per-case, which should win
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
        });

        it('missing per-case Label falls back to auto-generated', function () {
            // INACTIVE has no per-case label, no class-level EnumLabel
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('per-case Color overrides class-level EnumColor', function () {
            // BANNED has #[Color('danger')] per-case
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('class-level EnumColor provides default color', function () {
            // ACTIVE maps to 'success' via EnumColor(success: ['active'])
            expect(UserStatus::ACTIVE->color())->toBe('success');
        });

        it('case with no color mapping defaults to secondary', function () {
            // If we had a case not in EnumColor and no per-case Color
            // For INACTIVE which is not mapped, it should get 'secondary'
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });
    });
});
