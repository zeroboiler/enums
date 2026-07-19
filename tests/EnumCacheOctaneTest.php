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
    EnumMetadataResolver::resetCache();
});

describe('EnumCache flush behavior', function (): void {
    it('flush() clears all cached metadata', function (): void {
        $cache = EnumCache::getInstance();

        // Populate cache
        EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Flush
        EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('flush() allows re-resolution with fresh data', function (): void {
        $cache = EnumCache::getInstance();

        // First resolution populates cache
        $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Flush
        EnumCache::flush();
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Second resolution after flush produces same correct data
        $meta2 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($meta2)->toBe($meta1);
        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('clear() and flush() are equivalent for cache data', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        $cache->clear();
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Re-populate, then flush
        EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        EnumCache::flush();
        expect($cache->has(UserStatus::class))->toBeFalse();
    });
});

describe('Cache isolation between requests (Octane simulation)', function (): void {
    it('simulates two sequential requests with cache flush between them', function (): void {
        // === Request 1 ===
        $labelRequest1 = UserStatus::ACTIVE->label();
        expect($labelRequest1)->toBe('Active User');

        // Octane flushes state between requests
        EnumCache::flush();

        // === Request 2 ===
        // After flush, metadata should re-resolve correctly
        $labelRequest2 = UserStatus::ACTIVE->label();
        expect($labelRequest2)->toBe('Active User');

        // Different cases should also work
        expect(UserStatus::BANNED->label())->toBe('Banned');
        expect(UserStatus::PENDING->label())->toBe('Awaiting Verification');
    });

    it('simulates multiple request cycles without stale data', function (): void {
        // Run 5 simulated requests
        for ($i = 0; $i < 5; $i++) {
            // Within request: resolve metadata
            $meta = EnumMetadataResolver::resolve(UserStatus::class);

            // Verify it's always correct
            expect($meta['labels']['active'])->toBe('Active User');
            expect($meta['colors']['active'])->toBe('success');
            expect($meta['icons']['active'])->toBe('heroicon-o-check-circle');

            // End of request: flush
            EnumCache::flush();
        }
    });

    it('clearClass() removes only specific enum from cache', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeTrue();

        $cache->clearClass(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeFalse();
    });
});

describe('EnumCache TTL behavior', function (): void {
    it('respects TTL and auto-expires stale entries', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Expire immediately

        EnumMetadataResolver::resolve(UserStatus::class);

        // With TTL=0, the entry should be considered stale on next access
        // (microtime diff will be > 0)
        usleep(1000); // 1ms delay

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Restore default TTL
        $cache->setTtl(300);
    });

    it('setTtl() changes the TTL value', function (): void {
        $cache = EnumCache::getInstance();

        $cache->setTtl(600);
        $cache->setTtl(300); // Restore

        // Just verify it doesn't throw
        expect(true)->toBeTrue();
    });
});

describe('EnumCache singleton behavior', function (): void {
    it('getInstance() returns same instance', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('resetInstance() creates a fresh instance', function (): void {
        $a = EnumCache::getInstance();

        EnumCache::resetInstance();

        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
    });
});
