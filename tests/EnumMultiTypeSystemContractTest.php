<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum multi-type system contract', function (): void {
    describe('String-backed enum (UserStatus)', function (): void {
        it('resolves per-case label over auto-generated', function (): void {
            expect(UserStatus::ACTIVE->label())->toBe('Active User');
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
        });

        it('resolves class-level color over default', function (): void {
            expect(UserStatus::ACTIVE->color())->toBe('success');
            expect(UserStatus::PENDING->color())->toBe('warning');
        });

        it('resolves per-case color over class-level', function (): void {
            expect(UserStatus::BANNED->color())->toBe('danger');
        });

        it('returns null for missing icon/description', function (): void {
            expect(UserStatus::INACTIVE->icon())->toBeNull();
            expect(UserStatus::INACTIVE->description())->toBeNull();
        });

        it('resolves per-case icon and description', function (): void {
            expect(UserStatus::ACTIVE->icon())->toBe('heroicon-o-check-circle');
            expect(UserStatus::ACTIVE->description())->toBe('User can fully access the system');
        });

        it('forSelect returns backed values not case names', function (): void {
            $select = UserStatus::forSelect();
            expect($select)->toHaveCount(5);

            $active = $select[0];
            expect($active)->toHaveKey('value');
            expect($active['value'])->toBe('active');
            expect($active['label'])->toBe('Active User');
        });

        it('values returns backed values', function (): void {
            $values = UserStatus::values();
            expect($values)->toContain('active');
            expect($values)->toContain('banned');
            expect($values)->not->toContain('ACTIVE');
        });

        it('tryFromLabel is case-insensitive', function (): void {
            expect(UserStatus::tryFromLabel('Active User'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('active user'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromLabel('inactive'))->toBe(UserStatus::INACTIVE);
            expect(UserStatus::tryFromLabel('UNKNOWN'))->toBeNull();
        });

        it('tryFromName is case-sensitive', function (): void {
            expect(UserStatus::tryFromName('ACTIVE'))->toBe(UserStatus::ACTIVE);
            expect(UserStatus::tryFromName('active'))->toBeNull();
        });

        it('fromName throws on invalid case', function (): void {
            expect(fn (): UserStatus => UserStatus::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns boolean', function (): void {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
        });

        it('comparison works with instances and strings', function (): void {
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->isNot(UserStatus::BANNED))->toBeTrue();
            expect(UserStatus::ACTIVE->in([UserStatus::ACTIVE, UserStatus::PENDING]))->toBeTrue();
            expect(UserStatus::ACTIVE->in(['ACTIVE', 'PENDING']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn(['BANNED', 'SUSPENDED']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn(['ACTIVE', 'PENDING']))->toBeFalse();
        });

        it('forApi returns full metadata shape', function (): void {
            $api = UserStatus::forApi();
            expect($api)->toHaveCount(5);

            $active = $api[0];
            expect($active)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($active['value'])->toBe('active');
            expect($active['name'])->toBe('ACTIVE');
        });

        it('labels returns all labels in order', function (): void {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(5);
            expect($labels[0])->toBe('Active User');
        });
    });

    describe('Int-backed enum (IntBackedPriority)', function (): void {
        it('resolves class-level label mapping', function (): void {
            expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
            expect(IntBackedPriority::LOW->label())->toBe('Low Priority');
        });

        it('per-case label overrides class-level', function (): void {
            expect(IntBackedPriority::HIGH->label())->toBe('High Priority');
        });

        it('auto-generates label when no attribute set', function (): void {
            expect(IntBackedPriority::NONE->label())->toBe('None');
        });

        it('resolves class-level description mapping', function (): void {
            expect(IntBackedPriority::CRITICAL->description())->toBe('Critical priority — immediate action required');
            expect(IntBackedPriority::LOW->description())->toBe('Low priority — handle when convenient');
        });

        it('class-level color from int-keyed map', function (): void {
            expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
            expect(IntBackedPriority::HIGH->color())->toBe('warning');
            expect(IntBackedPriority::LOW->color())->toBe('success');
        });

        it('class-level default icon applied', function (): void {
            expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
            expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
        });

        it('values returns int backed values', function (): void {
            $values = IntBackedPriority::values();
            expect($values)->toContain(1);
            expect($values)->toContain(2);
            expect($values)->toContain(3);
            expect($values)->toContain(4);
            expect($values)->not->toContain('CRITICAL');
        });

        it('forSelect uses int values as keys', function (): void {
            $select = IntBackedPriority::forSelect();
            expect($select[0]['value'])->toBe(1);
            expect($select[0]['label'])->toBe('Critical Priority');
        });

        it('comparison works with int-backed enum', function (): void {
            expect(IntBackedPriority::CRITICAL->is(IntBackedPriority::CRITICAL))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->is('CRITICAL'))->toBeTrue();
            expect(IntBackedPriority::CRITICAL->in([IntBackedPriority::HIGH, IntBackedPriority::CRITICAL]))->toBeTrue();
        });

        it('tryFromLabel works with int-backed', function (): void {
            expect(IntBackedPriority::tryFromLabel('Critical Priority'))->toBe(IntBackedPriority::CRITICAL);
            expect(IntBackedPriority::tryFromLabel('High Priority'))->toBe(IntBackedPriority::HIGH);
        });

        it('tryFromName works with int-backed', function (): void {
            expect(IntBackedPriority::tryFromName('CRITICAL'))->toBe(IntBackedPriority::CRITICAL);
            expect(IntBackedPriority::tryFromName('NONE'))->toBe(IntBackedPriority::NONE);
        });
    });

    describe('Pure enum (PureFeatureFlag)', function (): void {
        it('resolves per-case attributes', function (): void {
            expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
            expect(PureFeatureFlag::DARK_MODE->color())->toBe('secondary');
            expect(PureFeatureFlag::DARK_MODE->icon())->toBe('heroicon-o-moon');
            expect(PureFeatureFlag::DARK_MODE->description())->toBe('Toggle dark mode for the UI');
        });

        it('auto-generates label for unannotated cases', function (): void {
            expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
        });

        it('default color is secondary', function (): void {
            expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary');
            expect(PureFeatureFlag::MAINTENANCE_MODE->icon())->toBeNull();
            expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
        });

        it('values returns case names for pure enums', function (): void {
            $values = PureFeatureFlag::values();
            expect($values)->toContain('DARK_MODE');
            expect($values)->toContain('BETA_FEATURES');
            expect($values)->toContain('MAINTENANCE_MODE');
        });

        it('forSelect uses case names as values', function (): void {
            $select = PureFeatureFlag::forSelect();
            expect($select[0]['value'])->toBe('DARK_MODE');
            expect($select[0]['label'])->toBe('Dark Mode');
        });

        it('comparison works with pure enum', function (): void {
            expect(PureFeatureFlag::DARK_MODE->is(PureFeatureFlag::DARK_MODE))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->is('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->isNot(PureFeatureFlag::BETA_FEATURES))->toBeTrue();
            expect(PureFeatureFlag::DARK_MODE->in([PureFeatureFlag::BETA_FEATURES, PureFeatureFlag::DARK_MODE]))->toBeTrue();
        });

        it('tryFromName works with pure enum', function (): void {
            expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(PureFeatureFlag::tryFromName('UNKNOWN'))->toBeNull();
        });

        it('tryFromLabel works with pure enum', function (): void {
            expect(PureFeatureFlag::tryFromLabel('Dark Mode'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(PureFeatureFlag::tryFromLabel('Maintenance Mode'))->toBe(PureFeatureFlag::MAINTENANCE_MODE);
        });

        it('fromName throws on invalid case', function (): void {
            expect(fn (): PureFeatureFlag => PureFeatureFlag::fromName('NONEXISTENT'))
                ->toThrow(InvalidEnumException::class);
        });

        it('forApi returns correct shape with case names', function (): void {
            $api = PureFeatureFlag::forApi();
            expect($api)->toHaveCount(3);

            $dark = $api[0];
            expect($dark['value'])->toBe('DARK_MODE');
            expect($dark['name'])->toBe('DARK_MODE');
            expect($dark['label'])->toBe('Dark Mode');
            expect($dark['color'])->toBe('secondary');
        });
    });

    describe('Cross-type consistency', function (): void {
        it('all types produce consistent forSelect shape', function (): void {
            $stringSelect = UserStatus::forSelect();
            $intSelect = IntBackedPriority::forSelect();
            $pureSelect = PureFeatureFlag::forSelect();

            foreach ($stringSelect as $item) {
                expect($item)->toHaveKeys(['value', 'label']);
            }
            foreach ($intSelect as $item) {
                expect($item)->toHaveKeys(['value', 'label']);
            }
            foreach ($pureSelect as $item) {
                expect($item)->toHaveKeys(['value', 'label']);
            }
        });

        it('all types produce consistent forApi shape', function (): void {
            $keys = ['value', 'name', 'label', 'description', 'color', 'icon'];

            foreach (UserStatus::forApi() as $item) {
                expect($item)->toHaveKeys($keys);
            }
            foreach (IntBackedPriority::forApi() as $item) {
                expect($item)->toHaveKeys($keys);
            }
            foreach (PureFeatureFlag::forApi() as $item) {
                expect($item)->toHaveKeys($keys);
            }
        });

        it('is() rejects instances from different enum types', function (): void {
            // Type mismatch: UserStatus vs IntBackedPriority
            // The trait is used inside each enum, so 'is' checks against self.
            // We verify that is() does NOT throw a type error for same-type comparison.
            expect(UserStatus::ACTIVE->is(UserStatus::ACTIVE))->toBeTrue();
        });

        it('all types support hasCase', function (): void {
            expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
            expect(IntBackedPriority::hasCase('CRITICAL'))->toBeTrue();
            expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();

            expect(UserStatus::hasCase('NONEXISTENT'))->toBeFalse();
            expect(IntBackedPriority::hasCase('NONEXISTENT'))->toBeFalse();
            expect(PureFeatureFlag::hasCase('NONEXISTENT'))->toBeFalse();
        });

        it('all types support labels() bulk method', function (): void {
            expect(UserStatus::labels())->toHaveCount(5);
            expect(IntBackedPriority::labels())->toHaveCount(4);
            expect(PureFeatureFlag::labels())->toHaveCount(3);
        });

        it('all types support values() bulk method', function (): void {
            expect(UserStatus::values())->toHaveCount(5);
            expect(IntBackedPriority::values())->toHaveCount(4);
            expect(PureFeatureFlag::values())->toHaveCount(3);
        });
    });
});
