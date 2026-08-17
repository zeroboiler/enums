<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * EnumMetadataResolver cache lifecycle tests.
 *
 * Covers cache population, TTL expiration, invalidation, and cross-enum isolation.
 */
describe('EnumMetadataResolver Cache Lifecycle', function (): void {
    beforeEach(function (): void {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::flush();
        EnumCache::resetInstance();
    });

    it('populates cache on first resolve call', function (): void {
        $cache = EnumCache::getInstance();
        expect($cache->has(UserStatus::class))->toBeFalse();

        EnumMetadataResolver::resolve(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('returns cached result on subsequent resolve calls', function (): void {
        $result1 = EnumMetadataResolver::resolve(UserStatus::class);
        $result2 = EnumMetadataResolver::resolve(UserStatus::class);

        expect($result1)->toBe($result2);
    });

    it('isolates cache entries per enum class', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();

        // Invalidating one class does not affect the other
        EnumMetadataResolver::invalidate(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
    });

    it('invalidates all cached entries with invalidateAll()', function (): void {
        $cache = EnumCache::getInstance();

        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(PureFeatureFlag::class);

        EnumMetadataResolver::invalidateAll();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(PureFeatureFlag::class))->toBeFalse();
    });

    it('resolves metadata correctly after invalidation', function (): void {
        $before = EnumMetadataResolver::resolve(UserStatus::class);

        EnumMetadataResolver::invalidate(UserStatus::class);

        $after = EnumMetadataResolver::resolve(UserStatus::class);

        expect($before)->toBe($after);
    });

    it('expires cache entries when TTL elapses', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0); // Disable caching — entries always stale

        EnumMetadataResolver::resolve(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('resolves int-backed enum metadata with correct value keys', function (): void {
        $meta = EnumMetadataResolver::resolve(IntBackedPriority::class);

        // CRITICAL = 1, class-level EnumLabel maps 1 => 'Critical Priority'
        expect($meta['labels'][1])->toBe('Critical Priority');
        expect($meta['labels'][2])->toBe('High Priority');
        expect($meta['labels'][3])->toBe('Low Priority');

        // Colors: class-level EnumColor maps 1 => 'danger', 2 => 'warning', 3,4 => 'success'
        expect($meta['colors'][1])->toBe('danger');
        expect($meta['colors'][2])->toBe('warning');
        expect($meta['colors'][3])->toBe('success');
        expect($meta['colors'][4])->toBe('success');
    });

    it('resolves pure enum metadata with case name keys', function (): void {
        $meta = EnumMetadataResolver::resolve(PureFeatureFlag::class);

        // Pure enums use case names as keys
        expect($meta['labels']['DARK_MODE'])->toBe('Dark Mode');
        expect($meta['labels']['BETA_FEATURES'])->toBe('Beta Features');
        expect($meta['labels']['MAINTENANCE_MODE'])->toBe('Maintenance Mode');

        expect($meta['colors']['DARK_MODE'])->toBe('secondary');
        expect($meta['colors']['BETA_FEATURES'])->toBe('warning');

        expect($meta['icons']['DARK_MODE'])->toBe('heroicon-o-moon');
        expect($meta['icons']['BETA_FEATURES'])->toBe('heroicon-o-beaker');
        expect($meta['icons']['MAINTENANCE_MODE'])->toBeNull();

        expect($meta['descriptions']['DARK_MODE'])->toBe('Toggle dark mode for the UI');
        expect($meta['descriptions']['BETA_FEATURES'])->toBe('Enable experimental beta features');
        expect($meta['descriptions']['MAINTENANCE_MODE'])->toBeNull();
    });

    it('always returns four metadata keys', function (): void {
        $meta = EnumMetadataResolver::resolve(UserStatus::class);

        expect(array_keys($meta))->toBe(['labels', 'descriptions', 'colors', 'icons']);
    });
});
