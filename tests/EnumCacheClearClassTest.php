<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

beforeEach(function (): void {
    EnumCache::resetInstance();
});

afterEach(function (): void {
    EnumCache::resetInstance();
});

describe('EnumCache::clearClass selective flushing', function (): void {
    it('clears metadata for a specific class without affecting others', function (): void {
        $cache = EnumCache::getInstance();

        // Resolve two different enums to populate cache
        EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Resolve another enum
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class);
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class))->toBeTrue();

        // Clear only UserStatus
        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(\ZeroBoiler\Enums\Tests\Fixtures\Priority::class))->toBeTrue();
    });

    it('clearClass is a no-op for non-cached classes', function (): void {
        $cache = EnumCache::getInstance();

        // Clearing a class that was never cached should not throw
        $cache->clearClass('NonExistentEnum');

        expect($cache->has('NonExistentEnum'))->toBeFalse();
    });

    it('allows re-resolution after clearClass', function (): void {
        $cache = EnumCache::getInstance();

        // Resolve and cache
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Clear specific class
        $cache->clearClass(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Re-resolve should work and produce identical result
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta2)->toBe($meta1);
        expect($cache->has(UserStatus::class))->toBeTrue();
    });
});

describe('EnumCache::setTtl edge cases', function (): void {
    it('normalizes negative TTL to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        // With TTL 0, has() should always return false
        expect($cache->has(UserStatus::class))->toBeFalse();

        // But we can still set and immediately get
        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse(); // TTL 0 = always stale
    });

    it('TTL of 0 disables caching but data is still set', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set(UserStatus::class, [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        // has() returns false due to TTL 0
        expect($cache->has(UserStatus::class))->toBeFalse();

        // But get() still works if called directly
        $meta = $cache->get(UserStatus::class);
        expect($meta['labels']['active'])->toBe('Active');
    });

    it('switching from non-zero TTL to zero expires existing entries', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(300);

        // Cache an entry
        EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Switch TTL to 0
        $cache->setTtl(0);

        // Entry should now appear stale
        expect($cache->has(UserStatus::class))->toBeFalse();
    });
});
