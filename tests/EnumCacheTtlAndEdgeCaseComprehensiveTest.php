<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache TTL edge cases', function () {
    beforeEach(function () {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function () {
        EnumCache::resetInstance();
    });

    it('cache is cold after resetInstance', function () {
        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('cache becomes warm after resolve', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        $cache = EnumCache::getInstance();

        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('setTtl(0) disables caching — has() always returns false', function () {
        EnumCache::getInstance()->setTtl(0);
        EnumMetadataResolver::resolve(UserStatus::class);
        $cache = EnumCache::getInstance();

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('setTtl with negative value normalizes to 0', function () {
        EnumCache::getInstance()->setTtl(-5);
        expect(EnumCache::getInstance()->getTtl())->toBe(0);
    });

    it('cache expires after TTL', function () {
        EnumCache::getInstance()->setTtl(1);
        EnumMetadataResolver::resolve(UserStatus::class);

        // Immediately should be warm
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Wait for TTL to expire
        usleep(1_200_000); // 1.2 seconds

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('clear removes all entries', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('clearClass removes only the specified class', function () {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::invalidate(UserStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('get throws OutOfBoundsException for missing entry', function () {
        expect(fn () => EnumCache::getInstance()->get(UserStatus::class))
            ->toThrow(\OutOfBoundsException::class);
    });

    it('resolve returns consistent metadata across multiple calls', function () {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta1)->toBe($meta2);
    });

    it('invalidate forces re-resolve with fresh metadata', function () {
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

        // Metadata should be functionally identical even after invalidation
        expect($meta1['labels'])->toEqual($meta2['labels']);
        expect($meta1['colors'])->toEqual($meta2['colors']);
    });

    it('flush is a static alias for clear', function () {
        EnumMetadataResolver::resolve(UserStatus::class);

        EnumCache::flush();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('singleton returns same instance across multiple getInstance calls', function () {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance creates new singleton', function () {
        $a = EnumCache::getInstance();

        EnumCache::resetInstance();

        $b = EnumCache::getInstance();

        // Different instances (not the same object)
        expect($a)->not->toBe($b);
    });
});
