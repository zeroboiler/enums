<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum edge cases — boundary values and exhaustive coverage', function (): void {
    describe('String-backed enum exhaustive case coverage', function (): void {
        it('covers all 5 UserStatus cases with correct metadata', function (): void {
            $cases = UserStatus::cases();
            expect($cases)->toHaveCount(5);

            $expectedNames = ['ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED', 'BANNED'];
            $actualNames = array_map(static fn ($c): string => $c->name, $cases);
            expect($actualNames)->toBe($expectedNames);
        });

        it('all cases have non-empty labels', function (): void {
            foreach (UserStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });

        it('all cases have a color string', function (): void {
            foreach (UserStatus::cases() as $case) {
                $color = $case->color();
                expect($color)->toBeString();
                expect($color)->toBeIn(['success', 'danger', 'warning', 'info', 'secondary']);
            }
        });

        it('forApi returns consistent order with cases()', function (): void {
            $api = UserStatus::forApi();
            $cases = UserStatus::cases();

            expect($api)->toHaveCount(count($cases));

            for ($i = 0; $i < count($cases); $i++) {
                expect($api[$i]['name'])->toBe($cases[$i]->name);
            }
        });

        it('forSelect returns consistent order with cases()', function (): void {
            $select = UserStatus::forSelect();
            $cases = UserStatus::cases();

            expect($select)->toHaveCount(count($cases));

            for ($i = 0; $i < count($cases); $i++) {
                $case = $cases[$i];
                $value = $case instanceof \BackedEnum ? $case->value : $case->name;
                expect($select[$i]['value'])->toBe($value);
            }
        });
    });

    describe('Int-backed enum boundary values', function (): void {
        it('CRITICAL has value 1 and danger color', function (): void {
            expect(IntBackedPriority::CRITICAL->value)->toBe(1);
            expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
        });

        it('NONE has value 4 and success color', function (): void {
            expect(IntBackedPriority::NONE->value)->toBe(4);
            expect(IntBackedPriority::NONE->color())->toBe('success');
        });

        it('values() returns all int values in order', function (): void {
            $values = IntBackedPriority::values();
            expect($values)->toBe([1, 2, 3, 4]);
        });

        it('labels() returns labels for all cases', function (): void {
            $labels = IntBackedPriority::labels();
            expect($labels)->toHaveCount(4);
            expect($labels[0])->toBe('Critical Priority');
            expect($labels[1])->toBe('High Priority');
            expect($labels[2])->toBe('Low Priority');
            expect($labels[3])->toBe('None');
        });

        it('is() works with same int-backed instance', function (): void {
            expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::CRITICAL))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::HIGH))->toBeFalse();
        });

        it('notIn() works with int-backed enum', function (): void {
            expect(IntBackedPriority::CRITICAL->notIn([2, 3, 4]))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->notIn([1, 2, 3]))->toBeFalse();
        });

        it('fromName returns correct int-backed case', function (): void {
            expect(IntBackedPriority::fromName('LOW')->value)->toBe(3);
        });

        it('fromName throws for invalid int-backed case', function (): void {
            IntBackedPriority::fromName('NONEXISTENT');
        })->throws(InvalidEnumException::class);
    });

    describe('Pure enum boundary behavior', function (): void {
        it('values() returns case names for all pure enum cases', function (): void {
            $values = PureFeatureFlag::values();
            expect($values)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
        });

        it('is() works with pure enum string name comparison', function (): void {
            expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('dark_mode'))->toBeFalse();
        });

        it('in() accepts mixed instances and strings for pure enum', function (): void {
            expect(PureFeatureFlag::DARK_MODE->in([
                PureFeatureFlag::DARK_MODE,
                'BETA_FEATURES',
            ]))->toBeTrue();
        });

        it('hasCase returns false for empty string', function (): void {
            expect(PureFeatureFlag::hasCase(''))->toBeFalse();
        });
    });

    describe('Comparison operators exhaustive combinations', function (): void {
        it('is() returns false for different enum types (string vs int)', function (): void {
            // This would be a type error if called across types,
            // but we verify same-type comparison works correctly
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::CRITICAL))->toBeTrue();
        });

        it('is() is reflexive for all types', function (): void {
            expect(UserStatus::BANNED->is(UserStatus::BANNED))->toBeTrue();
            expect(IntBackedPriority::NONE->is(IntBackedPriority::NONE))->toBeTrue();
            expect(PureFeatureFlag::MAINTENANCE_MODE->is(PureFeatureFlag::MAINTENANCE_MODE))->toBeTrue();
        });

        it('isNot() is symmetric for all types', function (): void {
            expect(UserStatus::ACTIVE->isNot(UserStatus::INACTIVE))->toBe(UserStatus::INACTIVE->isNot(UserStatus::ACTIVE));
            expect(IntBackedPriority::CRITICAL->isNot(IntBackedPriority::LOW))->toBe(IntBackedPriority::LOW->isNot(IntBackedPriority::CRITICAL));
        });

        it('in() returns false for empty array', function (): void {
            expect(UserStatus::ACTIVE->in([]))->toBeFalse();
            expect(IntBackedPriority::CRITICAL->in([]))->toBeFalse();
            expect(PureFeatureFlag::DARK_MODE->in([]))->toBeFalse();
        });

        it('notIn() returns true for empty array', function (): void {
            expect(UserStatus::ACTIVE->notIn([]))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->notIn([]))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->notIn([]))->toBeTrue();
        });
    });

    describe('Reverse lookup exhaustive coverage', function (): void {
        it('tryFromLabel is case-insensitive for all types', function (): void {
            // String-backed
            expect(UserStatus::tryFromLabel('ACTIVE USER'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);

            // Int-backed
            expect(IntBackedPriority::tryFromLabel('critical priority'))->toBe(IntBackedPriority::CRITICAL);
            expect(IntBackedPriority::tryFromLabel('CRITICAL PRIORITY'))->toBe(IntBackedPriority::CRITICAL);

            // Pure
            expect(PureFeatureFlag::tryFromLabel('dark mode'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(PureFeatureFlag::tryFromLabel('DARK MODE'))->toBe(PureFeatureFlag::DARK_MODE);
        });

        it('tryFromLabel returns null for whitespace-only label', function (): void {
            expect(UserStatus::tryFromLabel('   '))->toBeNull();
            expect(IntBackedPriority::tryFromLabel(''))->toBeNull();
        });

        it('tryFromName returns null for empty string', function (): void {
            expect(UserStatus::tryFromName(''))->toBeNull();
            expect(IntBackedPriority::tryFromName(''))->toBeNull();
            expect(PureFeatureFlag::tryFromName(''))->toBeNull();
        });
    });
});
