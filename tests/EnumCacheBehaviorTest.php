<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\Enums\EnumCache;
use ZeroBoiler\Enums\Exceptions\InvalidEnumException;
use ZeroBoiler\Enums\Tests\Fixtures\IntBackedPriority;
use ZeroBoiler\Enums\Tests\Fixtures\PureFeatureFlag;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('EnumCache singleton behavior', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
    });

    it('getInstance always returns the same instance', function (): void {
        $a = EnumCache::getInstance();
        $b = EnumCache::getInstance();

        expect($a)->toBe($b);
    });

    it('returns false when cache is empty', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('stores and retrieves metadata', function (): void {
        $cache = EnumCache::getInstance();
        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => ['active' => 'success'],
            'icons' => [],
        ];

        $cache->set(UserStatus::class, $metadata);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->get(UserStatus::class))->toBe($metadata);
    });

    it('throws OutOfBoundsException when getting non-existent entry', function (): void {
        EnumCache::getInstance()->get(UserStatus::class);
    })->throws(\OutOfBoundsException::class);

    it('TTL expiration works correctly', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(1);

        $metadata = [
            'labels' => ['active' => 'Active'],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ];
        $cache->set(UserStatus::class, $metadata);

        // Immediately should exist
        expect($cache->has(UserStatus::class))->toBeTrue();

        // Wait for TTL to expire
        usleep(1_200_000); // 1.2 seconds

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('TTL of 0 disables caching', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(0);

        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('negative TTL is clamped to 0', function (): void {
        $cache = EnumCache::getInstance();
        $cache->setTtl(-100);

        expect($cache->getTtl())->toBe(0);
    });

    it('clear removes all entries', function (): void {
        $cache = EnumCache::getInstance();

        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(IntBackedPriority::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();

        $cache->clear();

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeFalse();
    });

    it('clearClass removes only the specified class', function (): void {
        $cache = EnumCache::getInstance();

        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);
        $cache->set(IntBackedPriority::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        $cache->clearClass(UserStatus::class);

        expect($cache->has(UserStatus::class))->toBeFalse();
        expect($cache->has(IntBackedPriority::class))->toBeTrue();
    });

    it('flush is a static alias for clear', function (): void {
        $cache = EnumCache::getInstance();

        $cache->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        expect($cache->has(UserStatus::class))->toBeTrue();

        EnumCache::flush();

        expect($cache->has(UserStatus::class))->toBeFalse();
    });

    it('resetInstance allows a fresh singleton', function (): void {
        $first = EnumCache::getInstance();
        $first->set(UserStatus::class, [
            'labels' => [],
            'descriptions' => [],
            'colors' => [],
            'icons' => [],
        ]);

        EnumCache::resetInstance();

        $second = EnumCache::getInstance();

        // Should be a different instance (no cached entries)
        expect($second->has(UserStatus::class))->toBeFalse();
    });
});

describe('EnumCache integration with metadata resolution', function (): void {
    beforeEach(function (): void {
        EnumCache::resetInstance();
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(IntBackedPriority::class);
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(PureFeatureFlag::class);
    });

    afterEach(function (): void {
        EnumCache::resetInstance();
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(IntBackedPriority::class);
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(PureFeatureFlag::class);
    });

    it('metadata is cached after first resolution', function (): void {
        $cache = EnumCache::getInstance();

        expect($cache->has(UserStatus::class))->toBeFalse();

        // Trigger resolution
        $label = UserStatus::ACTIVE->label();

        expect($label)->toBe('Active User');
        expect($cache->has(UserStatus::class))->toBeTrue();
    });

    it('invalidate clears cached metadata for a class', function (): void {
        // First resolution caches the metadata
        UserStatus::ACTIVE->label();
        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();

        // Invalidate
        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidate(UserStatus::class);

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
    });

    it('invalidateAll clears all cached metadata', function (): void {
        UserStatus::ACTIVE->label();
        IntBackedPriority::CRITICAL->label();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeTrue();
        expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeTrue();

        \ZeroBoiler\Enums\Support\EnumMetadataResolver::invalidateAll();

        expect(EnumCache::getInstance()->has(UserStatus::class))->toBeFalse();
        expect(EnumCache::getInstance()->has(IntBackedPriority::class))->toBeFalse();
    });
});
