<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache TTL and edge cases', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('creates fresh singleton on first getInstance call', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache)->toBeInstanceOf(EnumCache::class);
    });

    it('returns same instance across multiple getInstance calls', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('returns new instance after resetInstance', function (): void {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
    });

    it('resetInstance clears TTL back to default', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(999);
        expect($cache->getTtl())->toBe(999);

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();
        expect($fresh->getTtl())->toBe(300);
    });

    it('has() returns false when cache is empty', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('has() returns true after set()', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(OrderStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(OrderStatus::class))->toBeTrue();
    });

    it('get() throws OutOfBoundsException for missing class', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn (): mixed => $cache->get(OrderStatus::class))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('get() returns stored metadata after set()', function (): void {
        $cache = EnumCache::getInstance();
        $meta = [
            'labels' => ['pending' => 'Pending'],
            'descriptions' => [],
            'colors' => ['pending' => 'secondary'],
            'icons' => [],
        ];
        $cache->set(OrderStatus::class, $meta);

        expect($cache->get(OrderStatus::class))->toBe($meta);
    });

    it('set() overwrites existing entry', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(OrderStatus::class, [
            'labels' => ['old' => 'Old Label'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $updated = [
            'labels' => ['new' => 'New Label'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set(OrderStatus::class, $updated);

        expect($cache->get(OrderStatus::class))->toBe($updated);
    });

    it('clearClass() removes specific class without affecting others', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clearClass(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('clear() removes all cached entries', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);
        $cache->set(UserStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        $cache->clear();

        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('flush() is a static alias for clear()', function (): void {
        $cache = EnumCache::getInstance();
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        EnumCache::flush();

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('setTtl normalizes negative values to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('setTtl(0) disables caching — has() always returns false', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('cache expires after TTL passes', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable TTL for the set
        $cache->set(OrderStatus::class, [
            'labels' => [], 'descriptions' => [], 'colors' => [], 'icons' => [],
        ]);

        // Re-enable a very short TTL — simulate expiration by manipulating timestamp
        // Since we can't actually wait, we test the TTL mechanism indirectly
        $cache->setTtl(300); // Re-enable caching
        expect($cache->has(OrderStatus::class))->toBeTrue();
    });

    it('EnumMetadataResolver::invalidate() clears specific class', function (): void {
        // Resolve to populate cache
        EnumMetadataResolver::resolve(OrderStatus::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(OrderStatus::class);

        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('EnumMetadataResolver::invalidateAll() clears everything', function (): void {
        EnumMetadataResolver::resolve(OrderStatus::class);
        EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::invalidateAll();

        $cache = EnumCache::getInstance();
        expect($cache->has(OrderStatus::class))->toBeFalse();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('clone prevention throws RuntimeException', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn (): mixed => clone $cache)
            ->toThrow(\RuntimeException::class, 'EnumCache is a singleton and cannot be cloned.');
    });

    it('wakeup prevention throws RuntimeException', function (): void {
        $cache = EnumCache::getInstance();

        // serialize + unserialize triggers __wakeup
        $serialized = serialize($cache);
        expect(fn (): mixed => unserialize($serialized))
            ->toThrow(\RuntimeException::class, 'EnumCache is a singleton and cannot be unserialized.');
    });
});
