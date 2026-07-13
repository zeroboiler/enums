<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Tests\Fixtures\OrderStatus;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache invalidation', function (): void {
    beforeEach(function (): void {
        // Start each test with a clean cache and default TTL
        EnumCache::flush();
        EnumCache::getInstance()->setTtl(300);
    });

    afterEach(function (): void {
        // Ensure TTL is reset between tests even if a test changes it
        EnumCache::getInstance()->setTtl(300);
    });

    it('populates cache after resolving metadata', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Trigger metadata resolution
        UserStatus::ACTIVE->label();

        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('clears all entries after flush()', function (): void {
        $cache = EnumCache::getInstance();

        // Populate cache
        UserStatus::ACTIVE->label();
        OrderStatus::PENDING->label();

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(OrderStatus::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeFalse();
    });

    it('clears all entries after clear()', function (): void {
        $cache = EnumCache::getInstance();

        UserStatus::ACTIVE->label();

        expect($cache->has(UserStatus::class))->toBeTrue();

        $cache->clear();

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('re-resolves metadata after cache flush', function (): void {
        $cache = EnumCache::getInstance();

        // First resolution
        $label1 = UserStatus::ACTIVE->label();
        $metadata1 = $cache->get(UserStatus::class);

        // Flush
        EnumCache::flush();
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Second resolution should produce identical metadata
        $label2 = UserStatus::ACTIVE->label();
        $metadata2 = $cache->get(UserStatus::class);

        expect($label1)->toBe($label2);
        expect($metadata1)->toBe($metadata2);
    });

    it('clears a single class with clearClass()', function (): void {
        $cache = EnumCache::getInstance();

        UserStatus::ACTIVE->label();
        OrderStatus::PENDING->label();

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(OrderStatus::class))->toBeTrue();
    });

    it('respects TTL and auto-expires stale entries', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1); // 1 second TTL

        UserStatus::ACTIVE->label();
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Wait beyond TTL
        usleep(1100000); // 1.1 seconds

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('resetInstance() creates a fresh cache', function (): void {
        $original = EnumCache::getInstance();

        UserStatus::ACTIVE->label();
        expect($original->has(UserStatus::class))->toBeTrue();

        EnumCache::resetInstance();

        $fresh = EnumCache::getInstance();
        expect($fresh)->not->toBe($original);
        expect($fresh->has(UserStatus::class))->toBeFalse();
    });

    it('does not hold stale references in EnumMetadataResolver after flush', function (): void {
        // Populate metadata resolver cache path
        UserStatus::ACTIVE->label();

        // Flush — this is what happens between requests in Octane/Swoole
        EnumCache::flush();

        // Resolver should get the same (now empty) singleton instance
        // and re-resolve fresh metadata
        $label = UserStatus::ACTIVE->label();
        expect($label)->toBe('Active User');

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('simulates Octane request lifecycle: cache is empty at start of each request', function (): void {
        // Simulate request 1
        UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Simulate end of request 1 — Octane flushes cache
        EnumCache::flush();

        // Simulate request 2 — cache should be empty at start
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();

        // Metadata is re-resolved fresh
        $label = UserStatus::ACTIVE->label();
        expect($label)->toBe('Active User');
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
    });
});

describe('EnumCache singleton behavior', function (): void {
    it('returns the same instance from getInstance()', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('returns a new instance after resetInstance()', function (): void {
        $a = EnumCache::getInstance();
        EnumCache::resetInstance();
        $b = EnumCache::getInstance();

        expect($a)->not->toBe($b);
    });
});
