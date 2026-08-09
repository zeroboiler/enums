<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntStatusWithColor;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\Priority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('Enum cache TTL and concurrency behaviour', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    describe('TTL edge cases', function () {
        it('entries expire immediately when TTL is set to 0', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(0);

            // Set an entry
            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active User'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // Should not be considered cached (TTL 0 = disabled)
            expect($cache->has(UserStatus::class))->toBeFalse();
        });

        it('negative TTL is normalized to 0 (always stale)', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(-5);

            $cache->set(Priority::class, [
                'labels' => ['CRITICAL' => 'Critical'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(Priority::class))->toBeFalse();
        });

        it('entries persist within TTL window', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active User'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has(UserStatus::class))->toBeTrue();
            expect($cache->get(UserStatus::class)['labels']['active'])->toBe('Active User');
        });

        it('getTtl returns the configured TTL', function () {
            $cache = EnumCache::getInstance();

            $cache->setTtl(60);
            expect($cache->getTtl())->toBe(60);

            $cache->setTtl(0);
            expect($cache->getTtl())->toBe(0);

            $cache->setTtl(-1);
            expect($cache->getTtl())->toBe(0);
        });
    });

    describe('Singleton lifecycle', function () {
        it('getInstance returns the same instance across calls', function () {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('resetInstance creates a fresh singleton', function () {
            $first = EnumCache::getInstance();
            $first->set(UserStatus::class, [
                'labels' => ['active' => 'Cached'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::resetInstance();

            $second = EnumCache::getInstance();
            // New instance should have empty cache
            expect($second->has(UserStatus::class))->toBeFalse();
            expect($second)->not->toBe($first);
        });

        it('flush clears all entries but keeps singleton', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(Priority::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            EnumCache::flush();

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeFalse();
        });
    });

    describe('clearClass isolation', function () {
        it('clearing one class does not affect others', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => ['active' => 'Active User'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(Priority::class, [
                'labels' => ['CRITICAL' => 'Critical'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);
            $cache->set(IntStatusWithColor::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // Clear only UserStatus
            $cache->clearClass(UserStatus::class);

            expect($cache->has(UserStatus::class))->toBeFalse();
            expect($cache->has(Priority::class))->toBeTrue();
            expect($cache->has(IntStatusWithColor::class))->toBeTrue();
        });

        it('clearing non-existent class is a no-op', function () {
            $cache = EnumCache::getInstance();
            $cache->setTtl(300);

            $cache->set(UserStatus::class, [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // Clear a class that was never cached — should not throw
            $cache->clearClass('NonExistent\Enum');

            expect($cache->has(UserStatus::class))->toBeTrue();
        });
    });

    describe('Resolver cache integration', function () {
        it('invalidating via resolver clears the cache entry', function () {
            // Resolve to populate cache
            EnumMetadataResolver::resolve(MixedAttributeStatus::class);

            $cache = EnumCache::getInstance();
            expect($cache->has(MixedAttributeStatus::class))->toBeTrue();

            // Invalidate via resolver
            EnumMetadataResolver::invalidate(MixedAttributeStatus::class);
            expect($cache->has(MixedAttributeStatus::class))->toBeFalse();
        });

        it('invalidateAll removes all cached entries', function () {
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::resolve(Priority::class);
            EnumMetadataResolver::resolve(PureFeatureFlag::class);

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
            expect(EnumCache::getInstance()->has(Priority::class))->toBeTrue();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(Priority::class))->toBeFalse();
            expect(EnumCache::getInstance()->has(PureFeatureFlag::class))->toBeFalse();
        });

        it('resolve rebuilds metadata after invalidation', function () {
            $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta1)->toBeArray();
            expect($meta1)->toHaveKey('labels');

            EnumMetadataResolver::invalidate(UserStatus::class);

            $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
            expect($meta2)->toBe($meta1);
        });
    });
});
