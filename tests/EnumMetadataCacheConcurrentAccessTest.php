<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\AllClassLevelEnum;

describe('Enum metadata cache — concurrent resolution and invalidation', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('resolving the same enum class twice returns the same cached metadata', function (): void {
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        $second = EnumMetadataResolver::resolve(UserStatus::class);

        expect($first)->toBe($second);
    });

    it('invalidating a class forces re-resolution with fresh metadata', function (): void {
        $cache = EnumCache::getInstance();

        // Resolve first
        $first = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Invalidate
        EnumMetadataResolver::invalidate(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeFalse();

        // Re-resolve
        $second = EnumMetadataResolver::resolve(UserStatus::class);
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($second)->toEqual($first);
    });

    it('invalidateAll clears all cached entries', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(PureFeatureFlag::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(PureFeatureFlag::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeFalse();
    });

    it('resolving different enum classes produces independent caches', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(PureFeatureFlag::class);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();

        // Clearing one should not affect the other
        EnumMetadataResolver::invalidate(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(PureFeatureFlag::class))->toBeTrue();
    });

    it('TTL of 0 disables caching — has() always returns false', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        EnumMetadataResolver::resolve(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('negative TTL is normalized to 0 and disables caching', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-5);

        expect($cache->getTtl())->toBe(0);
    });

    it('clearClass removes a specific class without affecting others', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
    });

    it('flush clears all entries and matches invalidateAll behavior', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(AllClassLevelEnum::class);

        EnumCache::flush();

        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(AllClassLevelEnum::class))->toBeFalse();
    });

    it('resetInstance destroys the singleton — new instance has empty cache', function (): void {
        EnumMetadataResolver::resolve(UserStatus::class);

        $old = EnumCache::getInstance();
        EnumCache::resetInstance();
        $new = EnumCache::getInstance();

        expect($old)->not->toBe($new);
        expect($new->has(UserStatus::class))->toBeFalse();
    });

    it('get() throws OutOfBoundsException when no cached entry exists', function (): void {
        $cache = EnumCache::getInstance();

        expect(fn () => $cache->get('NonExistentClass'))->toThrow(\OutOfBoundsException::class);
    });

    it('metadata structure always contains all four keys', function (): void {
        $metadata = EnumMetadataResolver::resolve(UserStatus::class);

        expect(array_keys($metadata))->toEqual(['labels', 'descriptions', 'colors', 'icons']);
    });

    it('metadata for pure enum uses case names as keys', function (): void {
        $metadata = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        // Pure enums use case names as keys
        expect($metadata['labels'])->toHaveKey('ON');
    });

    it('metadata for int-backed enum uses int values as keys', function (): void {
        $metadata = EnumMetadataResolver::resolve(IntBackedPriority::class);

        // Int-backed enums use int values as keys
        expect($metadata['labels'])->toHaveKey(1);
    });

    it('metadata for string-backed enum uses string values as keys', function (): void {
        $metadata = EnumMetadataResolver::resolve(UserStatus::class);

        expect($metadata['labels'])->toHaveKey('active');
    });
});
