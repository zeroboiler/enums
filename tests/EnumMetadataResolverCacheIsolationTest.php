<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\TicketStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests for EnumMetadataResolver cache isolation, invalidation, and edge cases.
 */
describe('EnumMetadataResolver cache isolation', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(300);
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(300);
        EnumCache::resetInstance();
    });

    it('resolves metadata for multiple enum classes independently', function () {
        $userMeta = EnumMetadataResolver::resolve(UserStatus::class);
        $priorityMeta = EnumMetadataResolver::resolve(Priority::class);

        expect($userMeta)->toBeArray()->toHaveKeys(['labels', 'colors', 'descriptions', 'icons']);
        expect($priorityMeta)->toBeArray()->toHaveKeys(['labels', 'colors', 'descriptions', 'icons']);
        expect($userMeta)->not->toBe($priorityMeta);
    });

    it('invalidates specific class without affecting others', function () {
        $userMeta1 = EnumMetadataResolver::resolve(UserStatus::class);
        $priorityMeta1 = EnumMetadataResolver::resolve(Priority::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        // UserStatus cache should be gone
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        // Priority cache should still be intact
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
        $priorityMeta2 = EnumMetadataResolver::resolve(Priority::class);
        expect($priorityMeta1)->toBe($priorityMeta2);
    });

    it('invalidateAll clears everything', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);
        EnumMetadataResolver::resolve(TicketStatus::class);

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(TicketStatus::class))->toBeFalse();
    });

    it('resolves pure enum with no attributes gracefully', function () {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($meta['labels'])->toBeArray();
        expect($meta['colors'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['icons'])->toBeArray();

        // Pure enum — labels should be auto-generated from case names
        foreach (PureFeatureFlag::cases() as $case) {
            $label = $case->label();
            expect($label)->toBeString()->not->toBeEmpty();
        }
    });

    it('resolves int-backed enum correctly', function () {
        $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);

        expect($meta['labels'])->toBeArray();
        expect($meta['colors'])->toBeArray();

        // For int-backed enums, keys in labels should be int values
        $case = IntBackedPriority::CRITICAL;
        expect($case->label())->toBeString();
        expect($case->color())->toBeString();
    });

    it('throws fromName for non-existent case', function () {
        expect(fn () => UserStatus::fromName('NON_EXISTENT_CASE'))
            ->toThrow(InvalidEnumException::class);
    });

    it('tryFromName returns null for non-existent case', function () {
        expect(UserStatus::tryFromName('NON_EXISTENT_CASE'))->toBeNull();
    });

    it('hasCase returns correct boolean', function () {
        expect(UserStatus::hasCase('ACTIVE'))->toBeTrue();
        expect(UserStatus::hasCase('active'))->toBeFalse(); // case-sensitive
        expect(UserStatus::hasCase(''))->toBeFalse();
    });

    it('tryFromLabel is case-insensitive', function () {
        $label = UserStatus::ACTIVE->label();
        expect($label)->not->toBeEmpty();

        expect(UserStatus::tryFromLabel($label))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel(strtolower($label)))->toBe(UserStatus::ACTIVE);
        expect(UserStatus::tryFromLabel(strtoupper($label)))->toBe(UserStatus::ACTIVE);
    });

    it('cache TTL of 0 disables caching', function () {
        EnumCache::getInstance()->setTtl(0);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        EnumMetadataResolver::resolve(UserStatus::class);

        // With TTL=0, cache should immediately be considered stale
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('negative TTL is normalized to 0', function () {
        EnumCache::getInstance()->setTtl(-100);

        expect(EnumCache::getInstance()->getTtl())->toBe(0);
    });

    it('EnumCache clear removes all entries', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();

        EnumCache::getInstance()->clear();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
    });

    it('EnumCache clearClass removes only the targeted class', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(Priority::class);

        EnumCache::getInstance()->clearClass(UserStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
    });

    it('EnumCache get throws OutOfBoundsException for missing entry', function () {
        EnumCache::getInstance()->clear();

        expect(fn () => EnumCache::getInstance()->get(UserStatus::class))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('values() returns correct types for each enum backing type', function () {
        // String-backed
        $userValues = UserStatus::values();
        expect($userValues)->not->toBeEmpty();
        foreach ($userValues as $v) {
            expect($v)->toBeString();
        }

        // Int-backed
        $priorityValues = Priority::values();
        expect($priorityValues)->not->toBeEmpty();
        foreach ($priorityValues as $v) {
            expect($v)->toBeInt();
        }

        // Pure enum — returns case names as strings
        $pureValues = PureFeatureFlag::values();
        expect($pureValues)->not->toBeEmpty();
        foreach ($pureValues as $v) {
            expect($v)->toBeString();
        }
    });

    it('labels() returns same count as cases', function () {
        foreach ([UserStatus::class, Priority::class, PureFeatureFlag::class] as $enumClass) {
            $labels = $enumClass::labels();
            expect($labels)->toHaveCount(count($enumClass::cases()));
        }
    });

    it('forSelect returns value+label pairs for all cases', function () {
        $options = UserStatus::forSelect();

        expect($options)->toHaveCount(count(UserStatus::cases()));
        foreach ($options as $option) {
            expect($option)->toHaveKeys(['value', 'label']);
            expect($option['label'])->toBeString()->not->toBeEmpty();
        }
    });

    it('forApi returns complete metadata for all cases', function () {
        $api = UserStatus::forApi();

        expect($api)->toHaveCount(count(UserStatus::cases()));
        foreach ($api as $item) {
            expect($item)->toHaveKeys(['value', 'name', 'label', 'description', 'color', 'icon']);
            expect($item['color'])->toBeString()->not->toBeEmpty();
        }
    });
});
