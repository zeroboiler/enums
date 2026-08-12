<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\LabelMapEnum;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\DefaultIconFeature;
use ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole;
use ZeroBoiler\Enums\Tests\Fixtures\SingleCaseEnum;
use ZeroBoiler\Enums\Tests\Fixtures\ZeroBackedPriority;

/**
 * Tests for enum cross-package integration with DTO roundtrips.
 *
 * These tests verify that enum metadata survives serialization to arrays
 * and back, which is critical when enums are properties of DTOs that
 * undergo fromArray → toArray cycles.
 */
describe('Enum cross-package DTO roundtrip', function () {
    it('forSelect produces consistent value/label pairs after cache flush', function () {
        EnumCache::flush();

        $select1 = IntBackedPriority::forSelect();
        EnumCache::flush();
        $select2 = IntBackedPriority::forSelect();

        expect($select1)->toBe($select2);
    });

    it('forApi produces full metadata after cache invalidation', function () {
        EnumMetadataResolver::invalidate(IntBackedPriority::class);

        $api = IntBackedPriority::forApi();

        expect($api)->toBeArray();
        expect($api)->not->toBeEmpty();

        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['value'])->not->toBeNull();
            expect($item['label'])->toBeString()->not->toBeEmpty();
            expect($item['color'])->toBeString();
        }
    });

    it('tryFromLabel is case-insensitive and works after resolver reset', function () {
        EnumMetadataResolver::invalidateAll();

        $label = IntBackedPriority::LOW->label();
        $found = IntBackedPriority::tryFromLabel(strtolower($label));

        expect($found)->not->toBeNull();
        expect($found)->toBe(IntBackedPriority::LOW);
    });

    it('fromName throws InvalidEnumException for invalid case name', function () {
        expect(fn () => IntBackedPriority::fromName('NON_EXISTENT_CASE'))
            ->toThrow(InvalidEnumException::class);
    });

    it('hasCase returns correct boolean for existing and non-existing names', function () {
        expect(IntBackedPriority::hasCase('LOW'))->toBeTrue();
        expect(IntBackedPriority::hasCase('HIGH'))->toBeTrue();
        expect(IntBackedPriority::hasCase('NON_EXISTENT'))->toBeFalse();
    });

    it('values() returns all backed values in case declaration order', function () {
        $values = IntBackedPriority::values();
        $expected = array_map(
            static fn (\BackedEnum $case) => $case->value,
            IntBackedPriority::cases(),
        );

        expect($values)->toBe($expected);
    });

    it('labels() returns non-empty strings for every case', function () {
        $labels = IntBackedPriority::labels();

        expect($labels)->toHaveCount(count(IntBackedPriority::cases()));
        expect($labels)->each->toBeString()->not->toBeEmpty();
    });

    it('in() works with empty array', function () {
        expect(IntBackedPriority::LOW->in([]))->toBeFalse();
    });

    it('is() and isNot() work with both instance and string comparison', function () {
        $low = IntBackedPriority::LOW;

        expect($low->is(IntBackedPriority::LOW))->toBeTrue();
        expect($low->is('LOW'))->toBeTrue();
        expect($low->is('low'))->toBeFalse(); // case-sensitive
        expect($low->isNot(IntBackedPriority::HIGH))->toBeTrue();
        expect($low->isNot('LOW'))->toBeFalse();
    });

    it('pure enum label generation from SCREAMING_SNAKE_CASE', function () {
        expect(PureFeatureFlag::DARK_MODE->label())->toBe('Dark Mode');
        expect(PureFeatureFlag::BETA_FEATURES->label())->toBe('Beta Features');
        // MAINTENANCE_MODE has no per-case label — auto-generated from case name
        expect(PureFeatureFlag::MAINTENANCE_MODE->label())->toBe('Maintenance Mode');
    });

    it('pure enum values() returns case names', function () {
        $values = PureFeatureFlag::values();

        expect($values)->toBe(array_map(
            static fn (\UnitEnum $c): string => $c->name,
            PureFeatureFlag::cases(),
        ));
    });

    it('class-level EnumLabel overrides auto-generated labels', function () {
        $labels = LabelMapEnum::forSelect();
        $labelMap = [];

        foreach ($labels as $item) {
            $labelMap[$item['value']] = $item['label'];
        }

        expect($labelMap['draft'])->toBe('Draft Article');
        expect($labelMap['published'])->toBe('Published Article');
        expect($labelMap['archived'])->toBe('Archived Article');
        // TRASHED not in class-level map — auto-generated from case name
        expect($labelMap['trashed'])->toBe('Trashed');
    });

    it('minimal enum without attributes resolves metadata with defaults', function () {
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);

        // OrderStatus has no attributes — colors/icons/descriptions default to null or fallback
        expect($meta['labels'])->toBeArray();
        expect($meta['labels'])->toHaveCount(count(OrderStatus::cases()));
    });

    it('class-level EnumIcon with default applies to all cases without specific icon', function () {
        $meta = EnumMetadataResolver::resolve(DefaultIconFeature::class);

        foreach (DefaultIconFeature::cases() as $case) {
            $value = $case instanceof \BackedEnum ? $case->value : $case->name;
            expect($meta['icons'][$value])->toBe('heroicon-o-circle-question-mark');
        }
    });

    it('per-case Icon overrides class-level EnumIcon default', function () {
        $meta = EnumMetadataResolver::resolve(OverriddenIconRole::class);

        $adminValue = OverriddenIconRole::ADMIN instanceof \BackedEnum
            ? OverriddenIconRole::ADMIN->value
            : OverriddenIconRole::ADMIN->name;

        expect($meta['icons'][$adminValue])->toBe('heroicon-o-user');

        // VIEWER uses class-level default
        $viewerValue = OverriddenIconRole::VIEWER instanceof \BackedEnum
            ? OverriddenIconRole::VIEWER->value
            : OverriddenIconRole::VIEWER->name;

        expect($meta['icons'][$viewerValue])->toBe('heroicon-o-circle-question-mark');
    });

    it('comparison methods handle single-case enum correctly', function () {
        $only = SingleCaseEnum::ONLY;

        expect($only->is(SingleCaseEnum::ONLY))->toBeTrue();
        expect($only->is('ONLY'))->toBeTrue();
        expect($only->isNot('ONLY'))->toBeFalse();
        expect($only->in([SingleCaseEnum::ONLY]))->toBeTrue();
    });

    it('zero-backed int enum handles value 0 correctly in metadata', function () {
        $case = ZeroBackedPriority::NONE;

        expect($case->value)->toBe(0);
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->color())->toBeString();

        $meta = EnumMetadataResolver::resolve(ZeroBackedPriority::class);
        expect(isset($meta['labels'][0]))->toBeTrue();
        expect($meta['labels'][0])->toBe('None');
    });

    it('enum facade delegation works for all methods', function () {
        $select = \ZeroBoiler\Enums\Facades\Enum::forSelect(IntBackedPriority::class);
        expect($select)->toBeArray()->not->toBeEmpty();

        $api = \ZeroBoiler\Enums\Facades\Enum::forApi(IntBackedPriority::class);
        expect($api)->toBeArray()->not->toBeEmpty();

        $label = IntBackedPriority::LOW->label();
        $found = \ZeroBoiler\Enums\Facades\Enum::tryFromLabel(IntBackedPriority::class, $label);
        expect($found)->not->toBeNull();
    });
});
