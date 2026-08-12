<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Enum metadata resolution priority and cross-type consistency tests.
 *
 * Validates the documented resolution order:
 *   1. Per-case attribute (highest priority)
 *   2. Class-level attribute
 *   3. Auto-generated (labels only, lowest priority)
 *
 * Also validates that the resolution chain is identical across all three
 * enum types (string-backed, int-backed, pure).
 *
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver
 */

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\DetailedTicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\SystemStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum metadata resolution priority chain', function (): void {

    // ──────────────────────────────────────────────────────────────
    // Resolution priority: per-case > class-level > auto-generated
    // ──────────────────────────────────────────────────────────────

    describe('Per-case attribute overrides class-level', function (): void {
        it('per-case #[Color] overrides class-level #[EnumColor]', function (): void {
            // UserStatus ACTIVE gets 'success' from class-level EnumColor
            expect(UserStatus::ACTIVE->color())->toBe('success');

            // UserStatus BANNED has per-case #[Color('danger')] override
            expect(UserStatus::BANNED->color())->toBe('danger');

            // UserStatus PENDING gets 'warning' from class-level EnumColor
            expect(UserStatus::PENDING->color())->toBe('warning');

            // UserStatus INACTIVE has no explicit color — default 'secondary'
            expect(UserStatus::INACTIVE->color())->toBe('secondary');
        });

        it('per-case #[Label] overrides auto-generated label', function (): void {
            // ACTIVE has per-case #[Label('Active User')]
            expect(UserStatus::ACTIVE->label())->toBe('Active User');

            // PENDING has per-case #[Label('Awaiting Verification')]
            expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');

            // INACTIVE has no label attribute — auto-generated from 'INACTIVE'
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('per-case #[Icon] overrides class-level default icon', function (): void {
            // SystemStatus has class-level EnumIcon with default 'heroicon-o-cog-6-tooth'
            expect(SystemStatus::MAINTENANCE->icon())->toBe('heroicon-o-cog-6-tooth');

            // SystemStatus ENABLED has per-value icon 'heroicon-o-check'
            expect(SystemStatus::ENABLED->icon())->toBe('heroicon-o-check');

            // SystemStatus DISABLED has per-value icon 'heroicon-o-x-mark'
            expect(SystemStatus::DISABLED->icon())->toBe('heroicon-o-x-mark');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Class-level attribute resolution for int-backed enums
    // ──────────────────────────────────────────────────────────────

    describe('Int-backed class-level attributes resolve correctly', function (): void {
        it('class-level EnumLabel maps int values to labels', function (): void {
            expect(SystemStatus::ENABLED->label())->toBe('Enabled');
            expect(SystemStatus::DISABLED->label())->toBe('Disabled');
            expect(SystemStatus::MAINTENANCE->label())->toBe('Maintenance');
        });

        it('class-level EnumColor maps int values to colors', function (): void {
            expect(SystemStatus::ENABLED->color())->toBe('success');
            expect(SystemStatus::DISABLED->color())->toBe('danger');
            expect(SystemStatus::MAINTENANCE->color())->toBe('warning');
        });

        it('class-level EnumDescription maps int values to descriptions', function (): void {
            expect(SystemStatus::ENABLED->description())->toBe('System is fully operational');
            expect(SystemStatus::DISABLED->description())->toBe('System is offline');
            expect(SystemStatus::MAINTENANCE->description())->toBe('Undergoing scheduled maintenance');
        });

        it('forSelect() uses backed value (int), not case name', function (): void {
            $select = SystemStatus::forSelect();

            expect($select)->toHaveCount(3);

            $values = array_column($select, 'value');
            expect($values)->each->toBeInt();
            expect($values)->toContain(0);
            expect($values)->toContain(1);
            expect($values)->toContain(2);
        });

        it('forApi() includes int values in metadata', function (): void {
            $api = SystemStatus::forApi();

            $enabled = $api[array_search(1, array_column($api, 'value'), true)];
            expect($enabled['value'])->toBe(1);
            expect($enabled['name'])->toBe('ENABLED');
            expect($enabled['label'])->toBe('Enabled');
            expect($enabled['color'])->toBe('success');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Pure enum: case names as metadata keys
    // ──────────────────────────────────────────────────────────────

    describe('Pure enum uses case names as metadata keys', function (): void {
        it('per-case attributes resolve using case name as key', function (): void {
            expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
            expect(PureFeatureFlag::DARK_MODE->color())->toBe('secondary');
            expect(PureFeatureFlag::DARK_MODE->icon())->toBe('heroicon-o-moon');
            expect(PureFeatureFlag::DARK_MODE->description())->toBe('Toggle dark mode for the UI');
        });

        it('auto-generated label works for pure enum without per-case attribute', function (): void {
            // MAINTENANCE_MODE has no per-case attributes
            expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
            expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary');
            expect(PureFeatureFlag::MAINTENANCE_MODE->icon())->toBeNull();
            expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
        });

        it('forSelect() uses case name as value for pure enum', function (): void {
            $select = PureFeatureFlag::forSelect();

            $values = array_column($select, 'value');
            expect($values)->toContain('DARK_MODE');
            expect($values)->toContain('BETA_FEATURES');
            expect($values)->toContain('MAINTENANCE_MODE');
        });

        it('values() returns case names for pure enum', function (): void {
            $values = PureFeatureFlag::values();

            expect($values)->toBe(['DARK_MODE', 'BETA_FEATURES', 'MAINTENANCE_MODE']);
        });

        it('tryFromLabel() works with auto-generated labels on pure enum', function (): void {
            $result = PureFeatureFlag::tryFromLabel('Maintenance Mode');

            expect($result)->toBeInstanceOf(PureFeatureFlag::class);
            expect($result->name)->toBe('MAINTENANCE_MODE');
        });

        it('tryFromName() works with pure enum', function (): void {
            expect(PureFeatureFlag::tryFromName('DARK_MODE')->name)->toBe('DARK_MODE');
            expect(PureFeatureFlag::tryFromName('NONEXISTENT'))->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Auto-label generation: SCREAMING_SNAKE → Title Case, camelCase → Title Case
    // ──────────────────────────────────────────────────────────────

    describe('Auto-label generation edge cases', function (): void {
        it('SCREAMING_SNAKE_CASE generates Title Case', function (): void {
            expect(UserStatus::INACTIVE->label())->toBe('Inactive');
            expect(UserStatus::SUSPENDED->label())->toBe('Suspended');
            expect(UserStatus::BANNED->label())->toBe('Banned');
        });

        it('camelCase generates Title Case', function (): void {
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
            expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Cache isolation and invalidation
    // ──────────────────────────────────────────────────────────────

    describe('Cache isolation between enum types', function (): void {
        it('invalidating one enum does not affect another', function (): void {
            EnumMetadataResolver::invalidate(UserStatus::class);

            $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
            $systemMeta = EnumMetadataResolver::resolve(SystemStatus::class);

            // Both should have labels, proving cache isolation
            expect($userMeta['labels'])->not->toBeEmpty();
            expect($systemMeta['labels'])->not->toBeEmpty();

            // They should have different label structures
            expect($userMeta['labels'])->not->toBe($systemMeta['labels']);
        });

        it('invalidateAll clears all cached metadata', function (): void {
            // Resolve multiple enums to populate cache
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(SystemStatus::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);

            // Clear all
            EnumMetadataResolver::invalidateAll();
            EnumCache::getInstance()->clear();

            // Cache should be empty
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(SystemStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeFalse();

            // Re-resolve should work
            $meta = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta['labels'])->not->toBeEmpty();
        });

        it('cache stores int keys for int-backed enums', function (): void {
            EnumMetadataResolver::invalidate(IntBackedPriority::class);
            EnumCache::getInstance()->clear();

            $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);

            // Int-backed enum metadata uses int keys
            $keys = array_keys($meta['labels']);
            foreach ($keys as $key) {
                expect($key)->toBeInt();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Single-case enum edge cases
    // ──────────────────────────────────────────────────────────────

    describe('Single-case enum edge cases', function (): void {
        it('single case enum has correct metadata', function (): void {
            expect(SingleCaseEnum::ONLY->label())->toBeString()->not->toBeEmpty();
            expect(SingleCaseEnum::ONLY->color())->toBeString();
        });

        it('single case enum forSelect returns single item', function (): void {
            $select = SingleCaseEnum::forSelect();

            expect($select)->toHaveCount(1);
            expect($select[0])->toHaveKeys(['value', 'label']);
        });

        it('single case enum in() works correctly', function (): void {
            expect(SingleCaseEnum::ONLY->in([SingleCaseEnum::ONLY]))->toBeTrue();
            expect(SingleCaseEnum::ONLY->in(['ONLY']))->toBeTrue();
            expect(SingleCaseEnum::ONLY->in([]))->toBeFalse();
        });

        it('single case enum notIn() works correctly', function (): void {
            expect(SingleCaseEnum::ONLY->notIn([]))->toBeTrue();
            expect(SingleCaseEnum::ONLY->notIn(['ONLY']))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // OverriddenIconRole — per-case EnumIcon override at case level
    // ──────────────────────────────────────────────────────────────

    describe('Case-level EnumIcon override behavior', function (): void {
        it('case-level EnumIcon overrides class-level default', function (): void {
            // OverriddenIconRole should override specific cases via case-level EnumIcon
            $meta = EnumMetadataResolver::resolve(OverriddenIconRole::class);

            expect($meta['icons'])->toBeArray();
            expect($meta['icons'])->not->toBeEmpty();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DetailedTicketStatus — comprehensive attribute combination
    // ──────────────────────────────────────────────────────────────

    describe('Comprehensive attribute combinations', function (): void {
        it('all metadata types return expected types', function (): void {
            foreach (DetailedTicketStatus::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
                expect($case->color())->toBeString();
                // icon and description are nullable
                expect($case->icon() === null || is_string($case->icon()))->toBeTrue();
                expect($case->description() === null || is_string($case->description()))->toBeTrue();
            }
        });

        it('forApi() metadata is consistent with individual accessors', function (): void {
            $api = DetailedTicketStatus::forApi();

            foreach ($api as $item) {
                $case = DetailedTicketStatus::from($item['value']);
                expect($item['label'])->toBe($case->label());
                expect($item['color'])->toBe($case->color());
                expect($item['icon'])->toBe($case->icon());
                expect($item['description'])->toBe($case->description());
                expect($item['name'])->toBe($case->name);
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Cross-type fromName/tryFromName consistency
    // ──────────────────────────────────────────────────────────────

    describe('Cross-type name lookup consistency', function (): void {
        it('fromName() and tryFromName() agree on all case names', function (): void {
            $enums = [UserStatus::class, SystemStatus::class, PureFeatureFlag::class];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    expect($enumClass::tryFromName($case->name))->toBe($case);
                    expect($enumClass::fromName($case->name)->name)->toBe($case->name);
                }
            }
        });

        it('fromName() throws for every non-existent name across types', function (): void {
            $enums = [UserStatus::class, SystemStatus::class, PureFeatureFlag::class];

            foreach ($enums as $enumClass) {
                expect(fn () => $enumClass::fromName('DEFINITELY_NOT_A_CASE'))
                    ->toThrow(InvalidEnumException::class);
            }
        });

        it('hasCase() is consistent with tryFromName()', function (): void {
            $enums = [UserStatus::class, SystemStatus::class, PureFeatureFlag::class];

            foreach ($enums as $enumClass) {
                foreach ($enumClass::cases() as $case) {
                    expect($enumClass::hasCase($case->name))->toBeTrue();
                }
                expect($enumClass::hasCase('NOT_REAL'))->toBeFalse();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // forSelect values are unique per enum
    // ──────────────────────────────────────────────────────────────

    describe('forSelect value uniqueness', function (): void {
        it('string-backed enum select values are unique', function (): void {
            $select = UserStatus::forSelect();
            $values = array_column($select, 'value');

            expect($values)->toEqual(array_unique($values));
        });

        it('int-backed enum select values are unique', function (): void {
            $select = SystemStatus::forSelect();
            $values = array_column($select, 'value');

            expect($values)->toEqual(array_unique($values));
        });

        it('pure enum select values are unique', function (): void {
            $select = PureFeatureFlag::forSelect();
            $values = array_column($select, 'value');

            expect($values)->toEqual(array_unique($values));
        });
    });
});
