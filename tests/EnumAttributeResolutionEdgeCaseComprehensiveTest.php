<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\NumericStatusCode;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingletonMode;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Attribute Resolution — Comprehensive Edge Cases', function () {
    // ─────────────────────────────────────────────────────────────
    // §1: NumericStatusCode — empty string, zero, and numeric string values
    // ─────────────────────────────────────────────────────────────

    describe('NumericStatusCode — empty string value', function () {
        it('has EMPTY_VALUE case with empty string backed value', function () {
            $case = NumericStatusCode::EMPTY_VALUE;

            expect($case->value)->toBe('');
            expect($case->name)->toBe('EMPTY_VALUE');
        });

        it('resolves class-level label for empty string value', function () {
            expect(NumericStatusCode::EMPTY_VALUE->label())->toBe('None');
        });

        it('has default color (secondary) for empty string value', function () {
            expect(NumericStatusCode::EMPTY_VALUE->color())->toBe('secondary');
        });

        it('has default icon from class-level EnumIcon', function () {
            expect(NumericStatusCode::EMPTY_VALUE->icon())->toBe('heroicon-o-number');
        });

        it('description falls back to null when no attribute is set', function () {
            expect(NumericStatusCode::EMPTY_VALUE->description())->toBeNull();
        });
    });

    describe('NumericStatusCode — zero string value', function () {
        it('resolves class-level label for "0" value', function () {
            expect(NumericStatusCode::ZERO->label())->toBe('Zero');
        });

        it('resolves class-level description for "0" value', function () {
            expect(NumericStatusCode::ZERO->description())->toBe('Numeric zero value');
        });

        it('resolves class-level color (warning) for "0" value', function () {
            expect(NumericStatusCode::ZERO->color())->toBe('warning');
        });

        it('per-case Color override takes priority over class-level', function () {
            expect(NumericStatusCode::ZERO->color())->toBe('danger');
        });
    });

    describe('NumericStatusCode — one string value', function () {
        it('resolves class-level label for "1" value', function () {
            expect(NumericStatusCode::ONE->label())->toBe('One');
        });

        it('resolves class-level description for "1" value', function () {
            expect(NumericStatusCode::ONE->description())->toBe('Numeric one value');
        });

        it('resolves class-level color (success) for "1" value', function () {
            expect(NumericStatusCode::ONE->color())->toBe('success');
        });
    });

    describe('NumericStatusCode — TWO with all per-case overrides', function () {
        it('uses per-case Label over class-level', function () {
            expect(NumericStatusCode::TWO->label())->toBe('Custom Two Label');
        });

        it('uses per-case Description over class-level', function () {
            expect(NumericStatusCode::TWO->description())->toBe('Custom description for two');
        });

        it('uses per-case Icon over class-level default', function () {
            expect(NumericStatusCode::TWO->icon())->toBe('heroicon-o-double');
        });

        it('has default color for TWO (no class or per-case color)', function () {
            expect(NumericStatusCode::TWO->color())->toBe('secondary');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §2: SingletonMode — single-case enum edge cases
    // ─────────────────────────────────────────────────────────────

    describe('SingletonMode — single case enum', function () {
        it('has exactly one case', function () {
            expect(SingletonMode::cases())->toHaveCount(1);
        });

        it('forSelect returns single-element array', function () {
            $select = SingletonMode::forSelect();

            expect($select)->toHaveCount(1);
            expect($select[0])->toHaveKeys(['value', 'label']);
            expect($select[0]['value'])->toBe('INSTANCE');
            expect($select[0]['label'])->toBe('Instance');
        });

        it('forApi returns single-element array', function () {
            $api = SingletonMode::forApi();

            expect($api)->toHaveCount(1);
            expect($api[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('values returns single-element array', function () {
            expect(SingletonMode::values())->toHaveCount(1);
        });

        it('labels returns single-element array', function () {
            expect(SingletonMode::labels())->toHaveCount(1);
        });

        it('tryFromLabel resolves the single case', function () {
            $case = SingletonMode::tryFromLabel('Instance');

            expect($case)->toBeInstanceOf(SingletonMode::class);
            expect($case->name)->toBe('INSTANCE');
        });

        it('hasCase returns true for existing case', function () {
            expect(SingletonMode::hasCase('INSTANCE'))->toBeTrue();
        });

        it('hasCase returns false for non-existent case', function () {
            expect(SingletonMode::hasCase('NON_EXISTENT'))->toBeFalse();
        });

        it('in() works with single-element array', function () {
            expect(SingletonMode::INSTANCE->in([SingletonMode::INSTANCE]))->toBeTrue();
        });

        it('notIn() works with single-element array', function () {
            expect(SingletonMode::INSTANCE->notIn([SingletonMode::INSTANCE]))->toBeFalse();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §3: Cross-type consistency — backed vs pure enum metadata structure
    // ─────────────────────────────────────────────────────────────

    describe('Metadata structure consistency across enum types', function () {
        it('forApi() keys are consistent between string-backed and int-backed enums', function () {
            $stringApi = UserStatus::forApi();
            $intApi = Priority::forApi();

            expect($stringApi[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($intApi[0])->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
        });

        it('forSelect() keys are consistent between backed and pure enums', function () {
            $backedSelect = UserStatus::forSelect();
            $pureSelect = PureFeatureFlag::forSelect();

            expect($backedSelect[0])->toHaveKeys(['value', 'label']);
            expect($pureSelect[0])->toHaveKeys(['value', 'label']);
        });

        it('forApi() count matches cases() count for all enum types', function () {
            expect(count(UserStatus::forApi()))->toBe(count(UserStatus::cases()));
            expect(count(Priority::forApi()))->toBe(count(Priority::cases()));
            expect(count(PureFeatureFlag::forApi()))->toBe(count(PureFeatureFlag::cases()));
            expect(count(NumericStatusCode::forApi()))->toBe(count(NumericStatusCode::cases()));
        });

        it('forSelect() count matches cases() count for all enum types', function () {
            expect(count(UserStatus::forSelect()))->toBe(count(UserStatus::cases()));
            expect(count(Priority::forSelect()))->toBe(count(Priority::cases()));
            expect(count(PureFeatureFlag::forSelect()))->toBe(count(PureFeatureFlag::cases()));
        });

        it('values() count matches cases() count for all enum types', function () {
            expect(count(UserStatus::values()))->toBe(count(UserStatus::cases()));
            expect(count(Priority::values()))->toBe(count(Priority::cases()));
            expect(count(PureFeatureFlag::values()))->toBe(count(PureFeatureFlag::cases()));
        });

        it('labels() are non-empty strings for all cases across enum types', function () {
            foreach (UserStatus::labels() as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }

            foreach (Priority::labels() as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }

            foreach (PureFeatureFlag::labels() as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }

            foreach (NumericStatusCode::labels() as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });

        it('colors are always strings for all cases across enum types', function () {
            foreach (UserStatus::forApi() as $item) {
                expect($item['color'])->toBeString();
            }

            foreach (Priority::forApi() as $item) {
                expect($item['color'])->toBeString();
            }

            foreach (PureFeatureFlag::forApi() as $item) {
                expect($item['color'])->toBeString();
            }
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §4: Comparison method edge cases
    // ─────────────────────────────────────────────────────────────

    describe('Comparison methods — edge cases', function () {
        it('is() with empty array does not match any case', function () {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
        });

        it('notIn() with empty array returns true', function () {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
        });

        it('tryFromLabel returns null for empty string', function () {
            expect(UserStatus::tryFromLabel(''))->toBeNull();
        });

        it('tryFromLabel is truly case-insensitive', function () {
            $label = UserStatus::ACTIVE->label();

            expect(UserStatus::tryFromLabel(strtoupper($label)))->toBeInstanceOf(UserStatus::class);
            expect(UserStatus::tryFromLabel(strtolower($label)))->toBeInstanceOf(UserStatus::class);
            expect(UserStatus::tryFromLabel(ucwords(strtolower($label))))->toBeInstanceOf(UserStatus::class);
        });

        it('tryFromName is case-sensitive', function () {
            expect(UserStatus::tryFromName('ACTIVE'))->toBeInstanceOf(UserStatus::class);
            expect(UserStatus::tryFromName('active'))->toBeNull();
            expect(UserStatus::tryFromName('Active'))->toBeNull();
        });

        it('fromName throws for empty string', function () {
            expect(fn () => UserStatus::fromName(''))->toThrow(InvalidEnumException::class);
        });

        it('fromName throws for case with different casing', function () {
            expect(fn () => UserStatus::fromName('active'))->toThrow(InvalidEnumException::class);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §5: Value uniqueness across all enum types
    // ─────────────────────────────────────────────────────────────

    describe('Value uniqueness — backed values are unique', function () {
        it('UserStatus backed values are unique', function () {
            $values = UserStatus::values();

            expect($values)->toEqual(array_unique($values));
        });

        it('Priority backed values are unique', function () {
            $values = Priority::values();

            expect($values)->toEqual(array_unique($values));
        });

        it('NumericStatusCode backed values are unique', function () {
            $values = NumericStatusCode::values();

            expect($values)->toEqual(array_unique($values));
        });

        it('PureFeatureFlag case names are unique', function () {
            $values = PureFeatureFlag::values();

            expect($values)->toEqual(array_unique($values));
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §6: Cache invalidation — multiple enum types
    // ─────────────────────────────────────────────────────────────

    describe('Cache behavior — multiple enum types', function () {
        it('accessing multiple enum types does not pollute cache', function () {
            $userStatusApi1 = UserStatus::forApi();
            $priorityApi = Priority::forApi();
            $userStatusApi2 = UserStatus::forApi();

            expect($userStatusApi1)->toEqual($userStatusApi2);
            expect($priorityApi)->not->toBe($userStatusApi1);
        });

        it('labels from different enums are independent', function () {
            $userLabels = UserStatus::labels();
            $priorityLabels = Priority::labels();

            // They should have different counts (unless coincidentally equal)
            // and be independent arrays
            expect($userLabels)->not->toBe($priorityLabels);
        });
    });
});
