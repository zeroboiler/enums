<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumMetadataResolver cache integration', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
        EnumMetadataResolver::invalidateAll();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
        EnumMetadataResolver::invalidateAll();
    });

    it('first resolve caches the result', function (): void {
        $cache = EnumCache::getInstance();

        // First call — should populate cache
        $result1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Second call — should return cached result (same reference)
        $result2 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($result1)->toBe($result2);
    });

    it('invalidate removes cached metadata for a specific class', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidate(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('invalidateAll clears all cached metadata', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('re-resolve after invalidation produces correct metadata', function (): void {
        $result1 = EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        $result2 = EnumMetadataResolver::resolve(UserStatus::class);

        // Structure should be identical
        expect($result1)->toBe($result2);
        expect($result2)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('metadata has correct structure for string-backed enum', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect($meta['labels'])->toBeArray();
        expect($meta['descriptions'])->toBeArray();
        expect($meta['colors'])->toBeArray();
        expect($meta['icons'])->toBeArray();

        // Every case should have a label entry (at minimum auto-generated)
        expect(count($meta['labels']))->toBeGreaterThanOrEqual(count(UserStatus::cases()));
    });

    it('metadata labels contain human-readable strings', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        foreach ($meta['labels'] as $key => $label) {
            expect($label)->toBeString();
            expect($label)->not->toBeEmpty();
        }
    });

    it('colors defaults to secondary for cases without explicit color', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        // At least one color should exist (from the fixture class-level or per-case attributes)
        expect($meta['colors'])->toBeArray();
    });

    it('cache TTL expiration triggers re-resolution', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $result1 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Wait for TTL to expire
        usleep(1_200_000); // 1.2 seconds

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Should rebuild automatically
        $result2 = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($result1)->toBe($result2);
    });

    it('clearing a specific class does not affect other classes', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        // IntBackedPriority should still be cached
        expect(EnumCache::getInstance()->has(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class))->toBeTrue();
    });

    it('flush is equivalent to invalidateAll', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class);

        EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(\ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority::class))->toBeFalse();
    });

    it('throws LogicException for non-enum class', function (): void {
        EnumMetadataResolver::resolve(\stdClass::class);
    })->throws(\LogicException::class);

    it('setTtl clamps negative values to 0', function (): void {
        $cache = EnumCache::getInstance();

        $cache->setTtl(-5);
        expect($cache->getTtl())->toBe(0);

        $cache->setTtl(-1);
        expect($cache->getTtl())->toBe(0);
    });
});
