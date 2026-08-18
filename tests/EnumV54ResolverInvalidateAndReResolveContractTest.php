<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Support\EnumMetadataResolver;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\MixedAttributeStatus;
use ZeroBoiler\Enums\Tests\Fixtures\PureSystemState;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumMetadataResolver invalidate/invalidateAll and re-resolve contract', function () {
    beforeEach(function () {
        // Ensure clean cache state for each test
        \ZeroBoiler\Enums\EnumCache::resetInstance();
    });

    afterEach(function () {
        \ZeroBoiler\Enums\EnumCache::resetInstance();
    });

    it('invalidate() forces re-resolution on next access', function () {
        $meta1 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        expect($meta1)->toBeArray();
        expect($meta1)->toHaveKeys(['labels', 'descriptions', 'colors', 'icons']);

        // Invalidate the cache
        EnumMetadataResolver::invalidate(MixedAttributeStatus::class);

        // Re-resolve — should get a fresh result (same content, different call path)
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        expect($cache->has(MixedAttributeStatus::class))->toBeFalse();

        $meta2 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        expect($meta2)->toBe($meta1);
        expect($cache->has(MixedAttributeStatus::class))->toBeTrue();
    });

    it('invalidateAll() flushes all cached entries', function () {
        // Resolve multiple enums to populate cache
        EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        EnumMetadataResolver::resolve(UserStatus::class);
        EnumMetadataResolver::resolve(IntBackedPriority::class);

        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();
        expect($cache->has(MixedAttributeStatus::class))->toBeTrue();
        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();

        // Flush all
        EnumMetadataResolver::invalidateAll();

        expect($cache->has(MixedAttributeStatus::class))->toBeFalse();
        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeFalse();
    });

    it('invalidate() is a no-op for non-cached classes', function () {
        // Should not throw or cause side effects
        EnumMetadataResolver::invalidate('NonExistentClass');
        expect(true)->toBeTrue();
    });

    it('resolve() returns consistent metadata for pure enums after invalidation', function () {
        $meta1 = EnumMetadataResolver::resolve(PureSystemState::class);

        EnumMetadataResolver::invalidate(PureSystemState::class);

        $meta2 = EnumMetadataResolver::resolve(PureSystemState::class);

        // Labels should use case names as keys for pure enums
        expect($meta2['labels'])->toBeArray();
        expect($meta2['labels'])->toHaveKey('INITIALIZING');
        expect($meta2['labels'])->toHaveKey('READY');
        expect($meta2['labels'])->toHaveKey('RUNNING');
        expect($meta2['labels'])->toHaveKey('FAILED');

        // Per-case override for READY
        expect($meta2['labels']['READY'])->toBe('Ready to Serve');

        // Auto-generated for RUNNING (no attribute)
        expect($meta2['labels']['RUNNING'])->toBe('Running');

        // Consistency between two resolutions
        expect($meta1)->toBe($meta2);
    });

    it('resolve() returns consistent metadata for int-backed enums after invalidation', function () {
        $meta1 = EnumMetadataResolver::resolve(IntBackedPriority::class);

        EnumMetadataResolver::invalidate(IntBackedPriority::class);

        $meta2 = EnumMetadataResolver::resolve(IntBackedPriority::class);

        // Colors keyed by int values
        expect($meta2['colors'])->toBeArray();

        // Values should use int keys (backed values)
        expect($meta2)->toBe($meta1);
    });

    it('resolve() caches metadata — second call returns same result without re-reflection', function () {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

        // First call — populates cache
        $meta1 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        expect($cache->has(MixedAttributeStatus::class))->toBeTrue();

        // Second call — should return cached result
        $meta2 = EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        expect($meta1)->toBe($meta2);
    });

    it('multiple classes can be cached and invalidated independently', function () {
        $cache = \ZeroBoiler\Enums\EnumCache::getInstance();

        EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        EnumMetadataResolver::resolve(UserStatus::class);

        // Invalidate only one
        EnumMetadataResolver::invalidate(MixedAttributeStatus::class);

        expect($cache->has(MixedAttributeStatus::class))->toBeFalse();
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Re-resolve the invalidated one
        EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        expect($cache->has(MixedAttributeStatus::class))->toBeTrue();
        expect($cache->has(UserStatus::class))->toBeTrue();
    });
});

describe('EnumMetadataResolver resolution priority after invalidation', function () {
    beforeEach(function () {
        \ZeroBoiler\Enums\EnumCache::resetInstance();
    });

    afterEach(function () {
        \ZeroBoiler\Enums\EnumCache::resetInstance();
    });

    it('per-case Color override wins over class-level EnumColor after re-resolve', function () {
        // MixedAttributeStatus: class-level danger: ['archived'], but no per-case color on ARCHIVED
        // So ARCHIVED should get 'danger' from class-level
        EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        EnumMetadataResolver::invalidate(MixedAttributeStatus::class);

        $meta = EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        expect($meta['colors']['archived'])->toBe('danger');
    });

    it('class-level EnumIcon default applies to cases without per-case icon after re-resolve', function () {
        // OverriddenIconRole: default icon on class, per-case override on ADMIN
        EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole::class);
        EnumMetadataResolver::invalidate(\ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole::class);

        $meta = EnumMetadataResolver::resolve(\ZeroBoiler\Enums\Tests\Fixtures\OverriddenIconRole::class);

        // ADMIN has per-case override
        expect($meta['icons']['admin'])->toBe('heroicon-o-user');
        // VIEWER gets default from class-level
        expect($meta['icons']['viewer'])->toBe('heroicon-o-circle-question-mark');
    });

    it('description metadata is preserved correctly after invalidation', function () {
        EnumMetadataResolver::resolve(MixedAttributeStatus::class);
        EnumMetadataResolver::invalidate(MixedAttributeStatus::class);

        $meta = EnumMetadataResolver::resolve(MixedAttributeStatus::class);

        // Class-level descriptions
        expect($meta['descriptions']['active'])->toBe('Currently active');
        expect($meta['descriptions']['pending'])->toBe('Awaiting review');

        // Cases without description should not have entries
        expect($meta['descriptions'])->not->toHaveKey('archived');
        expect($meta['descriptions'])->not->toHaveKey('deleted');
    });
});
