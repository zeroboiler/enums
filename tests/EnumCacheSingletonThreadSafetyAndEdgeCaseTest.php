<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;

describe('EnumCache singleton thread safety and edge cases', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('returns the same singleton instance on multiple getInstance() calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();
        $c = EnumCache::getInstance();

        expect($a)->toBe($b);
        expect($b)->toBe($c);
    });

    it('isolates cache entries between different enum classes', function () {
        $cache = EnumCache::getInstance();

        // Resolve metadata for two different enums
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        // Both should be cached independently
        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();

        // Clearing one does not affect the other
        $cache->clearClass(OrderStatus::class);
        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
    });

    it('resets to fresh instance with empty cache on resetInstance()', function () {
        $cache = EnumCache::getInstance();
        EnumMetadataResolver::resolve(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeTrue();

        EnumCache::resetInstance();

        $newCache = EnumCache::getInstance();
        expect($newCache)->not->toBe($cache);
        expect($newCache->has(OrderStatus::class))->toBeFalse();
        expect($newCache->getTtl())->toBe(300);
    });

    it('getTtl returns default 300 seconds', function () {
        $cache = EnumCache::getInstance();

        expect($cache->getTtl())->toBe(300);
    });

    it('setTtl normalizes negative values to 0', function () {
        $cache = EnumCache::getInstance();

        $cache->setTtl(-10);
        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl accepts zero to disable caching', function () {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(OrderStatus::class);

        // With TTL=0, has() should always return false (disabled)
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('flush clears all entries via static method', function () {
        $cache = EnumCache::getInstance();
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        expect($cache->has(OrderStatus::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeFalse();
    });

    it('throws OutOfBoundsException when getting non-existent entry', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentEnum'))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('clear is idempotent — calling clear on empty cache does not throw', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->clear())
            ->not->toThrow(\Throwable::class);

        expect(fn () => $cache->clear())
            ->not->toThrow(\Throwable::class);
    });

    it('clearClass is idempotent — clearing non-cached class does not throw', function () {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->clearClass('NonExistentEnum'))
            ->not->toThrow(\Throwable::class);
    });

    it('resetInstance allows fresh metadata resolution after reset', function () {
        EnumMetadataResolver::resolve(OrderStatus::class);

        // Pre-resolve cache should be populated
        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        // Reset destroys the singleton entirely
        EnumCache::resetInstance();

        // Fresh instance should have no cache
        $fresh = EnumCache::getInstance();
        expect($fresh->has(OrderStatus::class))->toBeFalse();

        // Re-resolve should work fine
        $meta = EnumMetadataResolver::resolve(OrderStatus::class);
        expect($meta)->toBeArray();
        expect($meta)->toHaveKey('labels');
        expect($meta)->toHaveKey('colors');
    });
});
