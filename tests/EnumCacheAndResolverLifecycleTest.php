<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Tests for EnumCache lifecycle, TTL expiration, per-class invalidation,
 * EnumMetadataResolver::invalidate/invalidateAll, and singleton behavior.
 *
 * @see \ZeroBoiler\Enums\EnumCache
 * @see \ZeroBoiler\Enums\Support\EnumMetadataResolver
 */

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache and EnumMetadataResolver lifecycle', function (): void {

    // ──────────────────────────────────────────────────────────────
    // EnumCache — TTL expiration
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache TTL expiration', function (): void {
        it('considers entries stale when TTL is 0', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(0);

            $cache->set('TestEnum', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('considers entries stale when TTL has elapsed', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(1);

            $cache->set('TestEnum', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            // TTL=1 second; sleep briefly
            usleep(1100000); // 1.1 seconds

            expect($cache->has('TestEnum'))->toBeFalse();
        });

        it('considers entries fresh when TTL has not elapsed', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(300);

            $cache->set('TestEnum', [
                'labels' => ['active' => 'Active'],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('TestEnum'))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCache — clearClass
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache clearClass', function (): void {
        it('clears a specific class without affecting others', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(300);

            $cache->set('EnumA', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->set('EnumB', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            $cache->clearClass('EnumA');

            expect($cache->has('EnumA'))->toBeFalse();
            expect($cache->has('EnumB'))->toBeTrue();

            $cache->clear();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumMetadataResolver — invalidate / invalidateAll
    // ──────────────────────────────────────────────────────────────

    describe('EnumMetadataResolver invalidation', function (): void {
        it('invalidate() clears metadata for a specific enum class', function (): void {
            EnumCache::getInstance()->clear();
            EnumCache::getInstance()->setTtl(300);

            // Resolve to populate cache
            EnumMetadataResolver::resolve(UserStatus::class);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

            // Invalidate
            EnumMetadataResolver::invalidate(UserStatus::class);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        });

        it('invalidateAll() clears all cached enum metadata', function (): void {
            EnumCache::getInstance()->clear();
            EnumCache::getInstance()->setTtl(300);

            // Resolve multiple enums
            EnumMetadataResolver::resolve(UserStatus::class);
            EnumCache::getInstance()->set('SomeOtherEnum', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
            expect(EnumCache::getInstance()->has('SomeOtherEnum'))->toBeTrue();

            EnumMetadataResolver::invalidateAll();

            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
            expect(EnumCache::getInstance()->has('SomeOtherEnum'))->toBeFalse();
        });

        it('resolve() re-builds metadata after invalidation', function (): void {
            EnumCache::getInstance()->clear();
            EnumCache::getInstance()->setTtl(300);

            $meta1 = EnumMetadataResolver::resolve(UserStatus::class);
            EnumMetadataResolver::invalidate(UserStatus::class);

            $meta2 = EnumMetadataResolver::resolve(UserStatus::class);

            expect($meta1)->toEqual($meta2);
            expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCache — singleton identity
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache singleton identity', function (): void {
        it('getInstance() always returns the same instance', function (): void {
            $a = EnumCache::getInstance();
            $b = EnumCache::getInstance();

            expect($a)->toBe($b);
        });

        it('resetInstance() creates a fresh instance', function (): void {
            EnumCache::getInstance()->setTtl(42);
            EnumCache::resetInstance();

            $fresh = EnumCache::getInstance();
            // Fresh instance should have default TTL (300), not 42
            expect($fresh->getTtl())->toBe(300);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // EnumCache — flush static helper
    // ──────────────────────────────────────────────────────────────

    describe('EnumCache::flush', function (): void {
        it('flush() delegates to singleton clear()', function (): void {
            $cache = EnumCache::getInstance();
            $cache->clear();
            $cache->setTtl(300);

            $cache->set('A', [
                'labels' => [],
                'descriptions' => [],
                'colors' => [],
                'icons' => [],
            ]);

            expect($cache->has('A'))->toBeTrue();

            EnumCache::flush();

            expect($cache->has('A'))->toBeFalse();
        });
    });
});
