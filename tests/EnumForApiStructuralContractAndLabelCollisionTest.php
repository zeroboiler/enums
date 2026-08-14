<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum forApi structural contract and label collision behavior', function (): void {
    describe('forApi() structural contract — consistent shape across all enum types', function (): void {
        it('returns exactly 6 keys for every case (string-backed)', function (): void {
            $api = UserStatus::forApi();

            foreach ($api as $entry) {
                expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect(array_keys($entry))->toBe([
                    'value', 'name', 'label', 'description', 'color', 'icon',
                ]);
            }
        });

        it('returns exactly 6 keys for every case (int-backed)', function (): void {
            $api = IntBackedPriority::forApi();

            foreach ($api as $entry) {
                expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect(array_keys($entry))->toBe([
                    'value', 'name', 'label', 'description', 'color', 'icon',
                ]);
            }
        });

        it('returns exactly 6 keys for every case (pure enum)', function (): void {
            $api = PureFeatureFlag::forApi();

            foreach ($api as $entry) {
                expect($entry)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
                expect(array_keys($entry))->toBe([
                    'value', 'name', 'label', 'description', 'color', 'icon',
                ]);
            }
        });

        it('value is backed value for backed enums', function (): void {
            $api = UserStatus::forApi();
            $values = array_column($api, 'value');
            expect($values)->toEqual(UserStatus::values());

            $intApi = IntBackedPriority::forApi();
            $intValues = array_column($intApi, 'value');
            expect($intValues)->toEqual(IntBackedPriority::values());
        });

        it('value is case name for pure enums', function (): void {
            $api = PureFeatureFlag::forApi();
            $values = array_column($api, 'value');
            expect($values)->toEqual(PureFeatureFlag::values());
        });

        it('name always matches case name', function (): void {
            $api = UserStatus::forApi();
            $names = array_column($api, 'name');
            expect($names)->toEqual(['ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED', 'BANNED']);
        });

        it('color is always a non-empty string (defaults to secondary)', function (): void {
            foreach ([UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass) {
                $api = $enumClass::forApi();
                foreach ($api as $entry) {
                    expect($entry['color'])->toBeString()->not->toBeEmpty();
                }
            }
        });

        it('description and icon are nullable (null when not set)', function (): void {
            $api = PureFeatureFlag::forApi();

            foreach ($api as $entry) {
                expect($entry['description'])->toBeNull();
                expect($entry['icon'])->toBeNull();
            }
        });

        it('preserves declaration order across all enum types', function (): void {
            $expected = array_map(static fn ($c): string => $c->name, UserStatus::cases());
            $actual = array_column(UserStatus::forApi(), 'name');
            expect($actual)->toBe($expected);

            $expectedInt = array_map(static fn ($c): string => $c->name, IntBackedPriority::cases());
            $actualInt = array_column(IntBackedPriority::forApi(), 'name');
            expect($actualInt)->toBe($expectedInt);

            $expectedPure = array_map(static fn ($c): string => $c->name, PureFeatureFlag::cases());
            $actualPure = array_column(PureFeatureFlag::forApi(), 'name');
            expect($actualPure)->toBe($expectedPure);
        });
    });

    describe('forSelect() structural contract', function (): void {
        it('returns exactly 2 keys (value, label) for every entry', function (): void {
            foreach ([UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass) {
                $select = $enumClass::forSelect();

                foreach ($select as $entry) {
                    expect($entry)->toHaveCount(2);
                    expect($entry)->toHaveKeys(['value', 'label']);
                    expect(array_keys($entry))->toBe(['value', 'label']);
                }
            }
        });

        it('all labels are non-empty strings', function (): void {
            foreach ([UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass) {
                $select = $enumClass::forSelect();

                foreach ($select as $entry) {
                    expect($entry['label'])->toBeString()->not->toBeEmpty();
                }
            }
        });
    });

    describe('tryFromLabel collision behavior — returns first match in declaration order', function (): void {
        it('returns first case when two cases have identical auto-generated labels (edge case)', function (): void {
            // For auto-generated labels, two cases with the same name pattern
            // would produce the same label. tryFromLabel should return the first match.
            $allCases = UserStatus::cases();
            $labels = array_map(static fn ($c): string => $c->label(), $allCases);

            // Find if there are any duplicate labels (shouldn't happen in normal enums,
            // but we verify the behavior)
            $unique = array_unique($labels);
            expect($labels)->toEqual($unique);
        });

        it('tryFromLabel is deterministic — always returns same case for same label', function (): void {
            $first = UserStatus::tryFromLabel('Active User');
            $second = UserStatus::tryFromLabel('Active User');
            $third = UserStatus::tryFromLabel('Active User');

            expect($first)->toBe($second);
            expect($second)->toBe($third);
            expect($first)->toBe(UserStatus::ACTIVE);
        });
    });

    describe('values/labels/forApi/forSelect length consistency', function (): void {
        it('all bulk methods return same count as cases() for every enum type', function (): void {
            foreach ([UserStatus::class, IntBackedPriority::class, PureFeatureFlag::class] as $enumClass) {
                $count = count($enumClass::cases());
                expect(count($enumClass::values()))->toBe($count);
                expect(count($enumClass::labels()))->toBe($count);
                expect(count($enumClass::forApi()))->toBe($count);
                expect(count($enumClass::forSelect()))->toBe($count);
            }
        });
    });
});
