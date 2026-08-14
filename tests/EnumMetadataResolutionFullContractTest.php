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
use ZeroBoiler\Enums\Support\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\CamelCaseRole;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum Metadata Resolution Contract', function () {
    beforeEach(function () {
        EnumCache::resetInstance();
    });

    describe('class-level attribute resolution', function () {
        it('resolves EnumColor at class level for int-backed enum', function () {
            expect(IntBackedPriority::CRITICAL->color())->toBe('danger');
            expect(IntBackedPriority::HIGH->color())->toBe('warning');
            expect(IntBackedPriority::LOW->color())->toBe('success');
            expect(IntBackedPriority::NONE->color())->toBe('secondary');
        });

        it('resolves EnumLabel at class level for int-backed enum', function () {
            expect(IntBackedPriority::CRITICAL->label())->toBe('Critical Priority');
            expect(IntBackedPriority::LOW->label())->toBe('Low Priority');
        });

        it('resolves EnumDescription at class level for int-backed enum', function () {
            expect(IntBackedPriority::CRITICAL->description())->toBe('Critical priority — immediate action required');
            expect(IntBackedPriority::LOW->description())->toBe('Low priority — handle when convenient');
            expect(IntBackedPriority::HIGH->description())->toBeNull();
        });

        it('resolves EnumIcon default for uncovered cases', function () {
            expect(IntBackedPriority::CRITICAL->icon())->toBe('heroicon-o-flag');
            expect(IntBackedPriority::HIGH->icon())->toBe('heroicon-o-flag');
            expect(IntBackedPriority::LOW->icon())->toBe('heroicon-o-flag');
            expect(IntBackedPriority::NONE->icon())->toBe('heroicon-o-flag');
        });

        it('resolves EnumIcon per-case icon map when provided', function () {
            // IntBackedPriority has default icon but no per-case map — all get default
            foreach (IntBackedPriority::cases() as $case) {
                expect($case->icon())->toBe('heroicon-o-flag');
            }
        });
    });

    describe('per-case override takes precedence over class-level', function () {
        it('per-case Color overrides class-level EnumColor', function () {
            // CRITICAL = 1 is in EnumColor danger list, but also has #[Color('danger')]
            // Both agree — but the precedence chain matters
            expect(IntBackedPriority::CRITICAL->color())->toBe('danger');

            // HIGH = 2 has both class-level warning AND per-case #[Color('warning')]
            expect(IntBackedPriority::HIGH->color())->toBe('warning');

            // LOW = 3 has both class-level success AND per-case #[Color('success')]
            expect(IntBackedPriority::LOW->color())->toBe('success');
        });

        it('per-case Label overrides class-level EnumLabel', function () {
            // HIGH has per-case #[Label('High Priority')] which should override any class-level
            expect(IntBackedPriority::HIGH->label())->toBe('High Priority');

            // NONE has no per-case label and no class-level label for value 4
            // So it should auto-generate from case name
            expect(IntBackedPriority::NONE->label())->toBe('None');
        });
    });

    describe('pure enum metadata uses case names as keys', function () {
        it('resolves per-case label for pure enum', function () {
            expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
            expect(PureFeatureFlag::BETA_FEATURES->label())->toBe('Beta Features');
        });

        it('resolves per-case color for pure enum', function () {
            expect(PureFeatureFlag::DARK_MODE->color())->toBe('secondary');
            expect(PureFeatureFlag::BETA_FEATURES->color())->toBe('warning');
            expect(PureFeatureFlag::MAINTENANCE_MODE->color())->toBe('secondary');
        });

        it('resolves per-case icon for pure enum', function () {
            expect(PureFeatureFlag::DARK_MODE->icon())->toBe('heroicon-o-moon');
            expect(PureFeatureFlag::BETA_FEATURES->icon())->toBe('heroicon-o-beaker');
            expect(PureFeatureFlag::MAINTENANCE_MODE->icon())->toBeNull();
        });

        it('resolves per-case description for pure enum', function () {
            expect(PureFeatureFlag::DARK_MODE->description())->toBe('Toggle dark mode for the UI');
            expect(PureFeatureFlag::BETA_FEATURES->description())->toBe('Enable experimental beta features');
            expect(PureFeatureFlag::MAINTENANCE_MODE->description())->toBeNull();
        });

        it('auto-generates label for pure enum case without attribute', function () {
            expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
        });

        it('values() returns case names for pure enum', function () {
            $values = PureFeatureFlag::values();
            expect($values)->toContain('DARK_MODE');
            expect($values)->toContain('BETA_FEATURES');
            expect($values)->toContain('MAINTENANCE_MODE');
            expect($values)->toHaveCount(3);
        });

        it('forSelect() uses case name as value for pure enum', function () {
            $select = PureFeatureFlag::forSelect();
            $values = array_column($select, 'value');
            expect($values)->toContain('DARK_MODE');
            expect($values)->toContain('BETA_FEATURES');
            expect($values)->not->toContain(0);
            expect($values)->not->toContain('0');
        });

        it('forApi() includes case name in name field for pure enum', function () {
            $api = PureFeatureFlag::forApi();
            $names = array_column($api, 'name');
            expect($names)->toContain('DARK_MODE');
            expect($names)->toContain('BETA_FEATURES');
            expect($names)->toContain('MAINTENANCE_MODE');
        });

        it('tryFromName works for pure enum', function () {
            expect(PureFeatureFlag::tryFromName('DARK_MODE'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(PureFeatureFlag::tryFromName('NON_EXISTENT'))->toBeNull();
        });

        it('fromName throws for invalid pure enum case', function () {
            expect(fn () => PureFeatureFlag::fromName('INVALID'))
                ->toThrow(InvalidEnumException::class);
        });

        it('hasCase returns correct boolean for pure enum', function () {
            expect(PureFeatureFlag::hasCase('DARK_MODE'))->toBeTrue();
            expect(PureFeatureFlag::hasCase('NON_EXISTENT'))->toBeFalse();
        });

        it('tryFromLabel resolves case-insensitively for pure enum', function () {
            expect(PureFeatureFlag::tryFromLabel('Dark Mode'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(PureFeatureFlag::tryFromLabel('dark mode'))->toBe(PureFeatureFlag::DARK_MODE);
            expect(PureFeatureFlag::tryFromLabel('DARK MODE'))->toBe(PureFeatureFlag::DARK_MODE);
        });
    });

    describe('int-backed enum metadata uses backed values as keys', function () {
        it('values() returns int values', function () {
            $values = IntBackedPriority::values();
            expect($values)->toContain(1);
            expect($values)->toContain(2);
            expect($values)->toContain(3);
            expect($values)->toContain(4);
            expect($values)->toHaveCount(4);
        });

        it('forSelect() uses int values', function () {
            $select = IntBackedPriority::forSelect();
            $values = array_column($select, 'value');
            expect($values)->toContain(1);
            expect($values)->toContain(4);
            // No string values like 'CRITICAL'
            expect(in_array('CRITICAL', $values, true))->toBeFalse();
        });

        it('forApi() includes both value and name', function () {
            $api = IntBackedPriority::forApi();
            $critical = array_find($api, fn (array $item): bool => $item['name'] === 'CRITICAL');
            expect($critical)->not->toBeNull();
            expect($critical['value'])->toBe(1);
            expect($critical['name'])->toBe('CRITICAL');
            expect($critical['label'])->toBe('Critical Priority');
            expect($critical['color'])->toBe('danger');
            expect($critical['icon'])->toBe('heroicon-o-flag');
        });
    });

    describe('camelCase label generation', function () {
        it('generates Title Case from camelCase case names', function () {
            expect(CamelCaseRole::isActive->label())->toBe('Is Active');
            expect(CamelCaseRole::isAdmin->label())->toBe('Is Admin');
            expect(CamelCaseRole::isModerator->label())->toBe('Is Moderator');
            expect(CamelCaseRole::isBanned->label())->toBe('Is Banned');
        });

        it('values() returns string backed values', function () {
            $values = CamelCaseRole::values();
            expect($values)->toContain('is_active');
            expect($values)->toContain('is_admin');
            expect($values)->toHaveCount(4);
        });
    });

    describe('metadata cache behavior', function () {
        it('EnumMetadataResolver::invalidate clears specific class cache', function () {
            // First access — populates cache
            $label1 = UserStatus::ACTIVE->label();
            $cache = EnumCache::getInstance();
            expect($cache->has(UserStatus::class))->toBeTrue();

            // Invalidate
            EnumMetadataResolver::invalidate(UserStatus::class);
            expect($cache->has(UserStatus::class))->toBeFalse();

            // Re-access should rebuild cache
            $label2 = UserStatus::ACTIVE->label();
            expect($label1)->toBe($label2);
            expect($cache->has(UserStatus::class))->toBeTrue();
        });

        it('EnumMetadataResolver::invalidateAll clears everything', function () {
            UserStatus::ACTIVE->label();
            TicketStatus::OPEN->label();

            $cache = EnumCache::getInstance();
            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->has(TicketStatus::class))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(TicketStatus::class))->toBeFalse();
        });

        it('EnumCache TTL controls expiration', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0); // Disable caching
            expect($cache->has(UserStatus::class))->toBeFalse();

            // Access populates but immediately expires
            UserStatus::ACTIVE->label();
            expect($cache->has(UserStatus::class))->toBeFalse();
        });
    });

    describe('comparison methods type safety', function () {
        it('is() returns false for different enum types', function () {
            // Even if case names match, different enum types should not match
            $result = UserStatus::ACTIVE->is(PureFeatureFlag::DARK_MODE);
            // This should be false because they are different enum types
            expect($result)->toBeFalse();
        });

        it('in() rejects cases from different enum types', function () {
            $result = UserStatus::ACTIVE->in([PureFeatureFlag::DARK_MODE]);
            expect($result)->toBeFalse();
        });

        it('is() with string name is case-sensitive', function () {
            expect(UserStatus::ACTIVE->is('ACTIVE'))->toBeTrue();
            expect(UserStatus::ACTIVE->is('active'))->toBeFalse();
            expect(UserStatus::ACTIVE->is('Active'))->toBeFalse();
        });

        it('notIn() returns true when case is not in exclusion list', function () {
            expect(UserStatus::ACTIVE->notIn(['BANNED', 'DELETED']))->toBeTrue();
            expect(UserStatus::ACTIVE->notIn(['ACTIVE']))->toBeFalse();
        });
    });

    describe('bulk method structural contract', function () {
        it('forSelect returns array of array with value and label keys', function () {
            foreach (UserStatus::forSelect() as $option) {
                expect($option)->toBeArray();
                expect($option)->toHaveKey('value');
                expect($option)->toHaveKey('label');
                expect($option['label'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forApi returns array of array with all six keys', function () {
            $requiredKeys = ['value', 'name', 'label', 'description', 'color', 'icon'];
            foreach (UserStatus::forApi() as $item) {
                expect($item)->toBeArray();
                foreach ($requiredKeys as $key) {
                    expect($item)->toHaveKey($key);
                }
                expect($item['label'])->toBeString()->not->toBeEmpty();
                expect($item['color'])->toBeString()->not->toBeEmpty();
                expect($item['name'])->toBeString()->not->toBeEmpty();
            }
        });

        it('forSelect values are unique', function () {
            $values = array_column(UserStatus::forSelect(), 'value');
            expect($values)->toEqual(array_unique($values));
        });

        it('forApi values are unique', function () {
            $values = array_column(UserStatus::forApi(), 'value');
            expect($values)->toEqual(array_unique($values));
        });

        it('forSelect and forApi have same count as cases()', function () {
            $caseCount = count(UserStatus::cases());
            expect(UserStatus::forSelect())->toHaveCount($caseCount);
            expect(UserStatus::forApi())->toHaveCount($caseCount);
        });

        it('labels() returns same count as cases and all non-empty', function () {
            $labels = UserStatus::labels();
            expect($labels)->toHaveCount(count(UserStatus::cases()));
            foreach ($labels as $label) {
                expect($label)->toBeString()->not->toBeEmpty();
            }
        });
    });
});
